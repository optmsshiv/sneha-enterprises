# 🌾 Sneha Enterprises — cPanel Hosting Setup Guide
**MySQL (Percona 5.7) + PHP 8.3 + cPanel**

---

## 📋 What You Need
- ✅ cPanel hosting with PHP 8.3 (you have this)
- ✅ MySQL / Percona database (you have this — Percona 5.7.44)
- ✅ phpMyAdmin access (you have this)
- ✅ Domain pointing to your hosting

---

## 🚀 STEP 1 — Create Database in cPanel

1. Login to **cPanel** → **MySQL Databases**
2. Create a new database: e.g. `cpses_sneha`
3. Create a new user: e.g. `cpses_sneha_user` with a strong password
4. Add user to database → grant **ALL PRIVILEGES**
5. Note down:
   - Database: `cpses_sneha`
   - Username: `cpses_sneha_user`
   - Password: `your_password`

---

## 🛠️ STEP 2 — Run Database Setup SQL

1. Open **phpMyAdmin** (from cPanel)
2. Select your new database from the left panel
3. Click the **SQL** tab
4. Open `api/database.sql` from this project
5. Paste the entire contents and click **Go**
6. You should see: `9 products inserted, 1 admin created`

---

## ⚙️ STEP 3 — Configure api/config.php

Open `api/config.php` and update these values:

```php
define('DB_NAME',  'cpses_sneha');          // your database name
define('DB_USER',  'cpses_sneha_user');     // your db username
define('DB_PASS',  'YOUR_DB_PASSWORD');     // ← your password here

define('ADMIN_EMAIL',   'admin@yourdomain.com');
define('COMPANY_EMAIL', 'exports@yourdomain.com');
```

**Email (optional but recommended):**
```php
// For cPanel email (recommended):
define('USE_SMTP', false);   // PHP mail() works on cPanel by default

// For SMTP (if PHP mail() doesn't work):
define('USE_SMTP',   true);
define('SMTP_HOST',  'mail.yourdomain.com');  // your cPanel mail server
define('SMTP_PORT',  587);
define('SMTP_USER',  'exports@yourdomain.com');
define('SMTP_PASS',  'your_email_password');
```

---

## 📤 STEP 4 — Upload Files to cPanel

### Option A — File Manager (cPanel)
1. cPanel → **File Manager** → `public_html`
2. Upload the ZIP file → Extract it
3. You should see: `public_html/sneha-enterprises/`

### Option B — FTP (FileZilla)
```
Host:     your-domain.com
Username: your-cpanel-username
Password: your-cpanel-password
Port:     21
```
Upload entire `sneha-enterprises/` folder to `public_html/`

### Folder structure on server:
```
public_html/
└── sneha-enterprises/        ← or in root if this IS your domain
    ├── index.html
    ├── api/
    │   ├── config.php        ← ⭐ Edit this first!
    │   ├── database.sql      ← Run in phpMyAdmin
    │   ├── .htaccess
    │   ├── index.php
    │   ├── auth.php
    │   ├── inquiries.php
    │   ├── products.php
    │   └── dashboard.php
    ├── admin/
    ├── pages/
    └── ...
```

---

## ✅ STEP 5 — Verify Setup

Visit these URLs in your browser:

```
https://yourdomain.com/sneha-enterprises/api/health
```

You should see:
```json
{
  "status": "ok",
  "message": "Sneha Enterprises API — PHP + MySQL",
  "database": "Connected — 9 products in DB"
}
```

If you see an error, check `api/config.php` database credentials.

---

## 🔐 STEP 6 — Login to Admin Panel

```
URL:      https://yourdomain.com/sneha-enterprises/admin/login.html
Username: admin
Password: sneha2024
```

> ⚠️ **Change password immediately** via Settings → Change Password!

---

## 🌐 STEP 7 — If Domain Root Setup

If Sneha Enterprises IS your main website (e.g. snehaenterprises.in):

1. Upload files directly to `public_html/` (not inside a subfolder)
2. The API will be at `https://snehaenterprises.in/api/health`
3. No config change needed — URLs auto-detect

---

## 🔧 TROUBLESHOOTING

### ❌ "Database connection failed"
- Check `DB_NAME`, `DB_USER`, `DB_PASS` in `api/config.php`
- Make sure user has ALL PRIVILEGES on the database
- Database host is always `localhost` on cPanel

### ❌ "404 Not Found" on /api/
- Check if `mod_rewrite` is enabled (it is on cPanel by default)
- Verify `.htaccess` was uploaded (it may be hidden — show hidden files in File Manager)

### ❌ Emails not sending
- Try PHP mail() first (set `USE_SMTP = false`)
- Check cPanel → Email → Email Deliverability
- Use your cPanel email account for SMTP (not Gmail)

### ❌ Admin login fails
- Visit `api/health` first — check DB is connected
- Run `database.sql` again in phpMyAdmin to reset admin account

### Reset admin password via phpMyAdmin:
```sql
-- In phpMyAdmin → SQL tab:
UPDATE admins
SET password = SHA2(CONCAT('NewPassword123', 'sneha_salt_2024'), 256)
WHERE username = 'admin';
```

---

## 📊 How It All Works

```
Browser (HTML/JS)
      │
      ▼ AJAX fetch()
PHP API (/api/*.php)
      │
      ▼ PDO
MySQL Database (cPanel)
      │
      ├─ inquiries table  ← All buyer inquiries stored here
      ├─ products table   ← Product catalogue
      ├─ admins table     ← Admin accounts
      └─ sessions table   ← Login tokens (24h expiry)
      
Email Flow:
  Buyer submits form
      → PHP saves to MySQL
      → PHP mail() sends to ADMIN_EMAIL
      → PHP mail() sends auto-reply to buyer
```

---

## 🔒 Security Checklist

- [ ] Change admin password from `sneha2024`
- [ ] Set strong `JWT_SECRET` in `api/config.php`
- [ ] Add `api/config.php` to `.gitignore` (never commit passwords)
- [ ] Enable HTTPS (free SSL in cPanel → Let's Encrypt)
- [ ] Restrict CORS in `core.php` line: `header('Access-Control-Allow-Origin: https://yourdomain.com')`

---

## 📞 Support
**Sneha Enterprises** | Patna, Bihar – 800001, India
exports@snehaenterprises.in | +91 98765 43210
