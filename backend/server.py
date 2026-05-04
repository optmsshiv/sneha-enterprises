"""
============================================================
  SNEHA ENTERPRISES — BACKEND API
  Flask + SQLite3 | JWT Auth | Email Notifications
  Run: python3 server.py
  API: http://localhost:5000/api
============================================================
"""

import sqlite3, os, json, hashlib, secrets, smtplib
from datetime import datetime, timedelta
from functools import wraps
from flask import Flask, request, jsonify, g
from flask_cors import CORS

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'sneha-secret-key-change-in-production-2024')
CORS(app, origins=['*'])  # In production: restrict to your domain

# ── CONFIG ──────────────────────────────────────────────────
DB_PATH      = os.path.join(os.path.dirname(__file__), 'sneha.db')
JWT_SECRET   = os.environ.get('JWT_SECRET', 'sneha-jwt-secret-2024')
JWT_EXPIRE_H = 24  # hours

# Email config (set via environment variables or edit here)
EMAIL_HOST    = os.environ.get('EMAIL_HOST', 'smtp.gmail.com')
EMAIL_PORT    = int(os.environ.get('EMAIL_PORT', '587'))
EMAIL_USER    = os.environ.get('EMAIL_USER', '')       # your Gmail
EMAIL_PASS    = os.environ.get('EMAIL_PASS', '')       # app password
ADMIN_EMAIL   = os.environ.get('ADMIN_EMAIL', 'admin@snehaenterprises.in')
COMPANY_EMAIL = 'exports@snehaenterprises.in'

# ── DATABASE ─────────────────────────────────────────────────
def get_db():
    if 'db' not in g:
        g.db = sqlite3.connect(DB_PATH, detect_types=sqlite3.PARSE_DECLTYPES)
        g.db.row_factory = sqlite3.Row
        g.db.execute('PRAGMA journal_mode=WAL')
        g.db.execute('PRAGMA foreign_keys=ON')
    return g.db

@app.teardown_appcontext
def close_db(e=None):
    db = g.pop('db', None)
    if db: db.close()

def init_db():
    with app.app_context():
        db = get_db()
        db.executescript("""
        CREATE TABLE IF NOT EXISTS admins (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            username  TEXT UNIQUE NOT NULL,
            password  TEXT NOT NULL,
            email     TEXT,
            role      TEXT DEFAULT 'admin',
            last_login TEXT,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS inquiries (
            id           TEXT PRIMARY KEY,
            name         TEXT NOT NULL,
            email        TEXT NOT NULL,
            company      TEXT,
            country      TEXT,
            phone        TEXT,
            product_id   TEXT,
            product_name TEXT,
            quantity     TEXT,
            incoterm     TEXT,
            message      TEXT,
            source       TEXT DEFAULT 'website',
            status       TEXT DEFAULT 'new',
            notes        TEXT,
            created_at   TEXT DEFAULT (datetime('now')),
            updated_at   TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS products (
            id          TEXT PRIMARY KEY,
            name        TEXT NOT NULL,
            category    TEXT NOT NULL,
            emoji       TEXT,
            badge       TEXT,
            bg          TEXT,
            origin      TEXT,
            description TEXT,
            specs       TEXT DEFAULT '{}',
            packaging   TEXT DEFAULT '[]',
            min_order   TEXT,
            active      INTEGER DEFAULT 1,
            created_at  TEXT DEFAULT (datetime('now')),
            updated_at  TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS sessions (
            token      TEXT PRIMARY KEY,
            admin_id   INTEGER NOT NULL,
            username   TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_inquiries_status ON inquiries(status);
        CREATE INDEX IF NOT EXISTS idx_inquiries_product ON inquiries(product_id);
        CREATE INDEX IF NOT EXISTS idx_inquiries_created ON inquiries(created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
        """)

        # Seed default admin (password: sneha2024)
        pw_hash = hash_password('sneha2024')
        db.execute("""
            INSERT OR IGNORE INTO admins (username, password, email, role)
            VALUES (?, ?, ?, ?)
        """, ('admin', pw_hash, ADMIN_EMAIL, 'superadmin'))

        # Seed products if empty
        if db.execute('SELECT COUNT(*) FROM products').fetchone()[0] == 0:
            seed_products(db)

        db.commit()
        print('✅ Database initialized:', DB_PATH)

def seed_products(db):
    products = [
        ('wheat-001','Premium Wheat','grains','🌾','Best Seller','linear-gradient(135deg,#FFF8E1,#FFF0C0)','Madhya Pradesh & Rajasthan','High-gluten, low-moisture wheat for flour milling, bread and pasta manufacturing.','{"Moisture":"Max 12%","Protein":"Min 10%","Gluten":"Min 26%","Purity":"99.5%"}','["50kg PP Bags","25kg PP Bags","Bulk Container"]','25 Metric Tons',1),
        ('maize-001','Yellow Maize','grains','🌽','','linear-gradient(135deg,#FFFDE7,#FFF59D)','Maharashtra & Bihar','Grade-A yellow dent maize for animal feed, starch extraction and industrial use.','{"Moisture":"Max 14%","Aflatoxin":"Max 10 ppb","Starch":"Min 68%","Purity":"98%"}','["50kg PP Bags","Bulk Container"]','20 Metric Tons',1),
        ('paddy-001','Raw Paddy & Rice','grains','🍚','Export Grade','linear-gradient(135deg,#E8F5E9,#C8E6C9)','West Bengal & Punjab','Long-grain raw paddy and processed rice — Basmati, Sona Masuri, Parboiled.','{"Moisture":"Max 13%","Broken Grains":"Max 5%","Purity":"99%","Varieties":"Basmati / Sona Masuri"}','["25kg Jute Bags","50kg PP Bags","Vacuum Sealed"]','10 Metric Tons',1),
        ('turmeric-001','Turmeric (Haldi)','spices','🟡','Organic','linear-gradient(135deg,#FFF3E0,#FFCC80)','Erode & Nizamabad','High curcumin finger and bulb turmeric — whole, polished or as fine powder.','{"Curcumin":"Min 3.5%","Moisture":"Max 10%","Purity":"99%","Form":"Finger / Bulb / Powder"}','["25kg PP Bags","50kg PP Bags","10kg Vacuum Pouches"]','5 Metric Tons',1),
        ('foxnuts-001','Fox Nuts (Makhana)','foxnuts','🌿','Superfood','linear-gradient(135deg,#F3E5F5,#E1BEE7)','Darbhanga & Madhubani, Bihar','Premium grade lotus seeds — crispy, white and nutritious. Ideal for health food brands.','{"Grade":"Premium / A-Grade","Moisture":"Max 8%","Purity":"99.5%","Size":"6-8mm / 8-10mm"}','["5kg Vacuum Bags","10kg Cartons","25kg PP Bags"]','1 Metric Ton',1),
        ('vegetables-001','Fresh Vegetables','vegetables','🥦','','linear-gradient(135deg,#E8F5E9,#A5D6A7)','Pan India','Seasonal and year-round fresh produce — onions, potatoes, bitter gourd, drumstick.','{"Availability":"Year-round","Grading":"A-Grade Sorted","Certification":"FSSAI Compliant","Packaging":"Custom"}','["10kg Mesh Bags","25kg Cartons","Custom"]','5 Metric Tons',1),
        ('onion-001','Red & White Onions','vegetables','🧅','High Demand','linear-gradient(135deg,#FFF3E0,#FFCC80)','Nashik, Maharashtra','Fresh onions, properly cured and sorted by size. Exported to Middle East, SE Asia and Europe.','{"Moisture":"Max 85%","Size":"40-80mm / 60-80mm","Skin":"Dry & Tight","Purity":"95% min"}','["25kg Mesh Bags","10kg Crates"]','20 Metric Tons',1),
        ('chilli-001','Dried Red Chilli','spices','🌶','','linear-gradient(135deg,#FFEBEE,#FFCDD2)','Andhra Pradesh & Karnataka','Byadgi, Teja and Guntur varieties sorted by colour, heat and moisture.','{"Moisture":"Max 12%","Colour":"ASTA 80+","Heat":"5000-150000 SHU","Varieties":"Byadgi / Teja / Guntur"}','["25kg PP Bags","50kg Bales","Powder Pouches"]','5 Metric Tons',1),
        ('sorghum-001','Sorghum (Jowar)','grains','🌾','','linear-gradient(135deg,#EFEBE9,#D7CCC8)','Karnataka & Maharashtra','Gluten-free sorghum grain for food, feed and industrial markets.','{"Moisture":"Max 13%","Purity":"98%","Protein":"Min 8%","Colour":"White / Red"}','["25kg PP Bags","50kg PP Bags","Bulk"]','20 Metric Tons',1),
    ]
    db.executemany("""
        INSERT OR IGNORE INTO products
        (id,name,category,emoji,badge,bg,origin,description,specs,packaging,min_order,active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    """, products)

# ── AUTH HELPERS ─────────────────────────────────────────────
def hash_password(pw):
    return hashlib.sha256((pw + 'sneha_salt_2024').encode()).hexdigest()

def generate_token():
    return secrets.token_hex(32)

def require_auth(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        token = request.headers.get('Authorization','').replace('Bearer ','')
        if not token:
            return jsonify({'error':'No token provided'}), 401
        db = get_db()
        session = db.execute(
            "SELECT * FROM sessions WHERE token=? AND expires_at > datetime('now')",
            (token,)
        ).fetchone()
        if not session:
            return jsonify({'error':'Invalid or expired token'}), 401
        g.admin_username = session['username']
        g.admin_id = session['admin_id']
        return f(*args, **kwargs)
    return decorated

# ── EMAIL ────────────────────────────────────────────────────
def send_email(to, subject, body_html):
    """Send email notification. Set EMAIL_USER and EMAIL_PASS env vars."""
    if not EMAIL_USER or not EMAIL_PASS:
        print(f'[EMAIL SKIPPED] To: {to} | Subject: {subject}')
        return False
    try:
        import smtplib
        from email.mime.text import MIMEText
        from email.mime.multipart import MIMEMultipart
        msg = MIMEMultipart('alternative')
        msg['Subject'] = subject
        msg['From'] = f'Sneha Enterprises <{EMAIL_USER}>'
        msg['To'] = to
        msg.attach(MIMEText(body_html, 'html'))
        with smtplib.SMTP(EMAIL_HOST, EMAIL_PORT) as server:
            server.starttls()
            server.login(EMAIL_USER, EMAIL_PASS)
            server.sendmail(EMAIL_USER, to, msg.as_string())
        print(f'[EMAIL SENT] To: {to}')
        return True
    except Exception as e:
        print(f'[EMAIL ERROR] {e}')
        return False

def send_inquiry_notifications(inquiry):
    """Send email to admin and auto-reply to buyer."""
    # 1. Notify admin
    admin_html = f"""
    <div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:20px">
      <div style="background:#1A2E6B;padding:20px;border-radius:10px;text-align:center;margin-bottom:24px">
        <h2 style="color:white;margin:0">🌾 New Trade Inquiry</h2>
        <p style="color:rgba(255,255,255,.7);margin:8px 0 0">Sneha Enterprises Admin Alert</p>
      </div>
      <table style="width:100%;border-collapse:collapse">
        {''.join(f'<tr><td style="padding:10px;border-bottom:1px solid #eee;font-weight:700;color:#1A2E6B;width:140px">{k}</td><td style="padding:10px;border-bottom:1px solid #eee;color:#333">{v}</td></tr>' for k,v in [
          ('Reference ID', inquiry['id']),
          ('Name', inquiry['name']),
          ('Company', inquiry.get('company','—')),
          ('Email', inquiry['email']),
          ('Phone', inquiry.get('phone','—')),
          ('Country', inquiry.get('country','—')),
          ('Product', inquiry.get('product_name','—')),
          ('Quantity', inquiry.get('quantity','—')),
          ('Incoterm', inquiry.get('incoterm','—')),
          ('Message', inquiry.get('message','—')),
          ('Date', inquiry.get('created_at','—')),
        ])}
      </table>
      <div style="margin-top:20px;text-align:center">
        <a href="http://localhost:5000" style="background:#C8912A;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700">View in Admin Panel</a>
      </div>
    </div>"""
    send_email(ADMIN_EMAIL, f"New Inquiry: {inquiry.get('product_name','Product')} — {inquiry['name']}", admin_html)

    # 2. Auto-reply to buyer
    buyer_html = f"""
    <div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:20px">
      <div style="background:#1A2E6B;padding:20px;border-radius:10px;text-align:center;margin-bottom:24px">
        <h2 style="color:white;margin:0">🌾 Sneha Enterprises</h2>
        <p style="color:rgba(255,255,255,.7);margin:4px 0 0">Import · Export · Quality Assured</p>
      </div>
      <p style="font-size:16px">Dear <strong>{inquiry['name']}</strong>,</p>
      <p style="color:#555;line-height:1.7">Thank you for your inquiry about <strong>{inquiry.get('product_name','our products')}</strong>. We have received your request and our trade team will review it immediately.</p>
      <div style="background:#F5EFE0;border-left:4px solid #C8912A;padding:16px;border-radius:8px;margin:20px 0">
        <strong>Your Reference ID: {inquiry['id']}</strong><br>
        <span style="color:#666;font-size:13px">Please quote this ID in all future communications.</span>
      </div>
      <p style="color:#555;line-height:1.7">You can expect a detailed response within <strong>24 business hours</strong>, including competitive pricing, product specifications, and sample availability.</p>
      <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
      <p style="color:#888;font-size:13px">
        <strong>Sneha Enterprises</strong><br>
        Patna, Bihar – 800001, India<br>
        📞 +91 98765 43210 | ✉️ {COMPANY_EMAIL}
      </p>
    </div>"""
    send_email(inquiry['email'], f"Inquiry Received — Reference {inquiry['id']}", buyer_html)

# ── API ROUTES ────────────────────────────────────────────────

# ── Health Check ──
@app.route('/api/health')
def health():
    return jsonify({'status':'ok','message':'Sneha Enterprises API running','version':'1.0.0'})

# ─────────────────────────────────────────────────────────────
# AUTH
# ─────────────────────────────────────────────────────────────

@app.route('/api/auth/login', methods=['POST'])
def login():
    data = request.get_json() or {}
    username = data.get('username','').strip()
    password = data.get('password','')
    if not username or not password:
        return jsonify({'error':'Username and password required'}), 400

    db = get_db()
    admin = db.execute(
        'SELECT * FROM admins WHERE username=?', (username,)
    ).fetchone()

    if not admin or admin['password'] != hash_password(password):
        return jsonify({'error':'Invalid username or password'}), 401

    token = generate_token()
    expires = (datetime.utcnow() + timedelta(hours=JWT_EXPIRE_H)).strftime('%Y-%m-%d %H:%M:%S')
    db.execute(
        'INSERT INTO sessions (token, admin_id, username, expires_at) VALUES (?,?,?,?)',
        (token, admin['id'], admin['username'], expires)
    )
    db.execute(
        "UPDATE admins SET last_login=datetime('now') WHERE id=?",
        (admin['id'],)
    )
    db.commit()
    return jsonify({
        'token': token,
        'username': admin['username'],
        'role': admin['role'],
        'expires_at': expires,
        'message': 'Login successful'
    })

@app.route('/api/auth/logout', methods=['POST'])
@require_auth
def logout():
    token = request.headers.get('Authorization','').replace('Bearer ','')
    get_db().execute('DELETE FROM sessions WHERE token=?', (token,))
    get_db().commit()
    return jsonify({'message':'Logged out successfully'})

@app.route('/api/auth/me', methods=['GET'])
@require_auth
def me():
    db = get_db()
    admin = db.execute(
        'SELECT id, username, email, role, last_login, created_at FROM admins WHERE username=?',
        (g.admin_username,)
    ).fetchone()
    if not admin:
        return jsonify({'error':'Admin not found'}), 404
    return jsonify(dict(admin))

@app.route('/api/auth/change-password', methods=['POST'])
@require_auth
def change_password():
    data = request.get_json() or {}
    old_pw = data.get('old_password','')
    new_pw = data.get('new_password','')
    if len(new_pw) < 6:
        return jsonify({'error':'Password must be at least 6 characters'}), 400
    db = get_db()
    admin = db.execute('SELECT * FROM admins WHERE username=?', (g.admin_username,)).fetchone()
    if admin['password'] != hash_password(old_pw):
        return jsonify({'error':'Old password incorrect'}), 401
    db.execute('UPDATE admins SET password=? WHERE username=?', (hash_password(new_pw), g.admin_username))
    db.commit()
    return jsonify({'message':'Password changed successfully'})

# ─────────────────────────────────────────────────────────────
# INQUIRIES
# ─────────────────────────────────────────────────────────────

@app.route('/api/inquiries', methods=['POST'])
def create_inquiry():
    """Public endpoint — called from website forms."""
    data = request.get_json() or {}
    required = ['name','email']
    for f in required:
        if not data.get(f,'').strip():
            return jsonify({'error':f'{f} is required'}), 400

    inq_id = 'INQ-' + datetime.utcnow().strftime('%Y%m%d%H%M%S') + '-' + secrets.token_hex(3).upper()
    db = get_db()
    db.execute("""
        INSERT INTO inquiries
        (id,name,email,company,country,phone,product_id,product_name,quantity,incoterm,message,source)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    """, (
        inq_id,
        data.get('name','').strip(),
        data.get('email','').strip().lower(),
        data.get('company','').strip(),
        data.get('country','').strip(),
        data.get('phone','').strip(),
        data.get('productId',''),
        data.get('productName',''),
        data.get('qty',''),
        data.get('incoterm','FOB'),
        data.get('message','').strip(),
        data.get('source','website')
    ))
    db.commit()

    # Get full inquiry for email
    inquiry_row = db.execute('SELECT * FROM inquiries WHERE id=?', (inq_id,)).fetchone()
    inquiry_dict = dict(inquiry_row)
    send_inquiry_notifications(inquiry_dict)

    return jsonify({'id': inq_id, 'message': 'Inquiry submitted successfully'}), 201

@app.route('/api/inquiries', methods=['GET'])
@require_auth
def get_inquiries():
    db = get_db()
    status  = request.args.get('status','')
    product = request.args.get('product','')
    search  = request.args.get('search','')
    page    = int(request.args.get('page', 1))
    per     = int(request.args.get('per_page', 50))
    offset  = (page - 1) * per

    conditions, params = [], []
    if status:  conditions.append('status=?');  params.append(status)
    if product: conditions.append('product_id=?'); params.append(product)
    if search:
        conditions.append('(name LIKE ? OR email LIKE ? OR company LIKE ? OR product_name LIKE ?)')
        like = f'%{search}%'
        params.extend([like,like,like,like])

    where = ('WHERE ' + ' AND '.join(conditions)) if conditions else ''
    total = db.execute(f'SELECT COUNT(*) FROM inquiries {where}', params).fetchone()[0]
    rows  = db.execute(
        f'SELECT * FROM inquiries {where} ORDER BY created_at DESC LIMIT ? OFFSET ?',
        params + [per, offset]
    ).fetchall()

    return jsonify({
        'inquiries': [dict(r) for r in rows],
        'total': total, 'page': page, 'per_page': per,
        'pages': (total + per - 1) // per
    })

@app.route('/api/inquiries/stats', methods=['GET'])
@require_auth
def inquiry_stats():
    db = get_db()
    total   = db.execute('SELECT COUNT(*) FROM inquiries').fetchone()[0]
    new     = db.execute("SELECT COUNT(*) FROM inquiries WHERE status='new'").fetchone()[0]
    replied = db.execute("SELECT COUNT(*) FROM inquiries WHERE status='replied'").fetchone()[0]
    today   = db.execute("SELECT COUNT(*) FROM inquiries WHERE date(created_at)=date('now')").fetchone()[0]
    by_product = db.execute("""
        SELECT product_name, COUNT(*) as count
        FROM inquiries GROUP BY product_name
        ORDER BY count DESC LIMIT 10
    """).fetchall()
    by_status = db.execute("""
        SELECT status, COUNT(*) as count FROM inquiries GROUP BY status
    """).fetchall()
    recent_7d = db.execute("""
        SELECT date(created_at) as day, COUNT(*) as count
        FROM inquiries
        WHERE created_at >= datetime('now','-7 days')
        GROUP BY day ORDER BY day
    """).fetchall()
    return jsonify({
        'total':total,'new':new,'replied':replied,'today':today,
        'by_product':[dict(r) for r in by_product],
        'by_status':[dict(r) for r in by_status],
        'recent_7d':[dict(r) for r in recent_7d]
    })

@app.route('/api/inquiries/<inq_id>', methods=['GET'])
@require_auth
def get_inquiry(inq_id):
    row = get_db().execute('SELECT * FROM inquiries WHERE id=?', (inq_id,)).fetchone()
    if not row: return jsonify({'error':'Not found'}), 404
    # Auto-mark as read
    if row['status'] == 'new':
        get_db().execute("UPDATE inquiries SET status='read',updated_at=datetime('now') WHERE id=?", (inq_id,))
        get_db().commit()
    return jsonify(dict(row))

@app.route('/api/inquiries/<inq_id>', methods=['PATCH'])
@require_auth
def update_inquiry(inq_id):
    data = request.get_json() or {}
    db = get_db()
    if not db.execute('SELECT 1 FROM inquiries WHERE id=?', (inq_id,)).fetchone():
        return jsonify({'error':'Not found'}), 404
    allowed = {'status','notes'}
    updates, vals = [], []
    for k in allowed:
        if k in data:
            updates.append(f'{k}=?')
            vals.append(data[k])
    if not updates:
        return jsonify({'error':'Nothing to update'}), 400
    updates.append("updated_at=datetime('now')")
    db.execute(f"UPDATE inquiries SET {','.join(updates)} WHERE id=?", vals + [inq_id])
    db.commit()
    return jsonify({'message':'Updated', 'id': inq_id})

@app.route('/api/inquiries/<inq_id>', methods=['DELETE'])
@require_auth
def delete_inquiry(inq_id):
    db = get_db()
    if not db.execute('SELECT 1 FROM inquiries WHERE id=?',(inq_id,)).fetchone():
        return jsonify({'error':'Not found'}), 404
    db.execute('DELETE FROM inquiries WHERE id=?', (inq_id,))
    db.commit()
    return jsonify({'message':'Deleted'})

@app.route('/api/inquiries', methods=['DELETE'])
@require_auth
def delete_all_inquiries():
    get_db().execute('DELETE FROM inquiries')
    get_db().commit()
    return jsonify({'message':'All inquiries deleted'})

# ─────────────────────────────────────────────────────────────
# PRODUCTS
# ─────────────────────────────────────────────────────────────

@app.route('/api/products', methods=['GET'])
def get_products():
    db = get_db()
    cat    = request.args.get('category','')
    active_only = request.args.get('active','')
    search = request.args.get('search','')
    conditions, params = [], []
    if cat:         conditions.append('category=?'); params.append(cat)
    if active_only: conditions.append('active=1')
    if search:
        conditions.append('(name LIKE ? OR origin LIKE ? OR description LIKE ?)')
        like=f'%{search}%'; params.extend([like,like,like])
    where = ('WHERE ' + ' AND '.join(conditions)) if conditions else ''
    rows = db.execute(f'SELECT * FROM products {where} ORDER BY created_at DESC', params).fetchall()
    products = []
    for r in rows:
        p = dict(r)
        try: p['specs'] = json.loads(p['specs'])
        except: p['specs'] = {}
        try: p['packaging'] = json.loads(p['packaging'])
        except: p['packaging'] = []
        p['active'] = bool(p['active'])
        products.append(p)
    return jsonify({'products': products, 'total': len(products)})

@app.route('/api/products', methods=['POST'])
@require_auth
def create_product():
    data = request.get_json() or {}
    if not data.get('name') or not data.get('category'):
        return jsonify({'error':'name and category required'}), 400
    prod_id = data.get('id') or (data['name'].lower().replace(' ','-') + '-' + secrets.token_hex(3))
    db = get_db()
    if db.execute('SELECT 1 FROM products WHERE id=?', (prod_id,)).fetchone():
        prod_id += '-' + secrets.token_hex(2)
    db.execute("""
        INSERT INTO products (id,name,category,emoji,badge,bg,origin,description,specs,packaging,min_order,active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    """, (
        prod_id, data.get('name'), data.get('category'),
        data.get('emoji','🌾'), data.get('badge',''),
        data.get('bg','linear-gradient(135deg,#FFF8E1,#FFF0C0)'),
        data.get('origin',''), data.get('description',''),
        json.dumps(data.get('specs',{})),
        json.dumps(data.get('packaging',[])),
        data.get('minOrder','On Request'),
        1 if data.get('active', True) else 0
    ))
    db.commit()
    return jsonify({'id': prod_id, 'message': 'Product created'}), 201

@app.route('/api/products/<prod_id>', methods=['GET'])
def get_product(prod_id):
    row = get_db().execute('SELECT * FROM products WHERE id=?', (prod_id,)).fetchone()
    if not row: return jsonify({'error':'Not found'}), 404
    p = dict(row)
    try: p['specs'] = json.loads(p['specs'])
    except: p['specs'] = {}
    try: p['packaging'] = json.loads(p['packaging'])
    except: p['packaging'] = []
    p['active'] = bool(p['active'])
    return jsonify(p)

@app.route('/api/products/<prod_id>', methods=['PUT'])
@require_auth
def update_product(prod_id):
    data = request.get_json() or {}
    db = get_db()
    if not db.execute('SELECT 1 FROM products WHERE id=?',(prod_id,)).fetchone():
        return jsonify({'error':'Not found'}), 404
    db.execute("""
        UPDATE products SET
        name=?,category=?,emoji=?,badge=?,bg=?,origin=?,description=?,
        specs=?,packaging=?,min_order=?,active=?,updated_at=datetime('now')
        WHERE id=?
    """, (
        data.get('name'), data.get('category'),
        data.get('emoji','🌾'), data.get('badge',''),
        data.get('bg',''), data.get('origin',''), data.get('description',''),
        json.dumps(data.get('specs',{})), json.dumps(data.get('packaging',[])),
        data.get('minOrder','On Request'),
        1 if data.get('active', True) else 0,
        prod_id
    ))
    db.commit()
    return jsonify({'message':'Updated', 'id': prod_id})

@app.route('/api/products/<prod_id>', methods=['DELETE'])
@require_auth
def delete_product(prod_id):
    db = get_db()
    if not db.execute('SELECT 1 FROM products WHERE id=?',(prod_id,)).fetchone():
        return jsonify({'error':'Not found'}), 404
    db.execute('DELETE FROM products WHERE id=?', (prod_id,))
    db.commit()
    return jsonify({'message':'Deleted'})

@app.route('/api/products/<prod_id>/toggle', methods=['PATCH'])
@require_auth
def toggle_product(prod_id):
    db = get_db()
    row = db.execute('SELECT active FROM products WHERE id=?',(prod_id,)).fetchone()
    if not row: return jsonify({'error':'Not found'}), 404
    new_active = 0 if row['active'] else 1
    db.execute("UPDATE products SET active=?,updated_at=datetime('now') WHERE id=?",(new_active,prod_id))
    db.commit()
    return jsonify({'active': bool(new_active), 'id': prod_id})

# ─────────────────────────────────────────────────────────────
# DASHBOARD SUMMARY
# ─────────────────────────────────────────────────────────────
@app.route('/api/dashboard', methods=['GET'])
@require_auth
def dashboard():
    db = get_db()
    return jsonify({
        'inquiries': {
            'total':   db.execute('SELECT COUNT(*) FROM inquiries').fetchone()[0],
            'new':     db.execute("SELECT COUNT(*) FROM inquiries WHERE status='new'").fetchone()[0],
            'replied': db.execute("SELECT COUNT(*) FROM inquiries WHERE status='replied'").fetchone()[0],
            'today':   db.execute("SELECT COUNT(*) FROM inquiries WHERE date(created_at)=date('now')").fetchone()[0],
        },
        'products': {
            'total':  db.execute('SELECT COUNT(*) FROM products').fetchone()[0],
            'active': db.execute('SELECT COUNT(*) FROM products WHERE active=1').fetchone()[0],
        },
        'recent_inquiries': [dict(r) for r in db.execute(
            'SELECT id,name,email,company,product_name,country,status,created_at FROM inquiries ORDER BY created_at DESC LIMIT 8'
        ).fetchall()],
        'top_products': [dict(r) for r in db.execute("""
            SELECT product_name, COUNT(*) as count FROM inquiries
            GROUP BY product_name ORDER BY count DESC LIMIT 8
        """).fetchall()]
    })

# ── MAIN ─────────────────────────────────────────────────────
if __name__ == '__main__':
    init_db()
    print('🌾 Sneha Enterprises API starting...')
    print('📍 http://localhost:5000/api')
    print('🔐 Admin: admin / sneha2024')
    app.run(debug=True, host='0.0.0.0', port=5000)
