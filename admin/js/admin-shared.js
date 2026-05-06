// ============================================================
//  SNEHA ENTERPRISES — ADMIN SHARED JS  v2.1
//  Uses api/index.php?route= format — no .htaccess needed
// ============================================================

// ── API BASE ─────────────────────────────────────────────────
// Auto-detects: works on localhost AND on cPanel server
const API_BASE = (() => {
  const loc = window.location;
  // If running on localhost with a local PHP server
  if (loc.hostname === 'localhost' || loc.hostname === '127.0.0.1') {
    return loc.origin + '/sneha-enterprises/api/index.php';
  }
  // On cPanel server — same domain, /api/index.php
  return loc.origin + '/api/index.php';
})();

// ── API CLIENT ────────────────────────────────────────────────
const api = {
  url(path) {
    // Convert /auth/login → ?route=auth/login
    const route = path.replace(/^\//, '');
    return API_BASE + '?route=' + encodeURIComponent(route);
  },
  async request(method, path, body) {
    const opts = {
      method,
      headers: {
        'Content-Type': 'application/json',
        ...(Auth.getToken() ? { 'Authorization': 'Bearer ' + Auth.getToken() } : {})
      }
    };
    if (body) opts.body = JSON.stringify(body);

    // Handle query params in path (e.g. /inquiries?status=new)
    const [cleanPath, qs] = path.split('?');
    let url = API_BASE + '?route=' + encodeURIComponent(cleanPath.replace(/^\//, ''));
    if (qs) url += '&' + qs;

    const res = await fetch(url, opts);
    if (res.status === 401) { Auth.logout(); return; }
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
  },
  get:    (path)       => api.request('GET',    path),
  post:   (path, body) => api.request('POST',   path, body),
  put:    (path, body) => api.request('PUT',    path, body),
  patch:  (path, body) => api.request('PATCH',  path, body),
  delete: (path)       => api.request('DELETE', path),
};

// ── AUTH ──────────────────────────────────────────────────────
const Auth = {
  getToken()   { return sessionStorage.getItem('sneha_token'); },
  getUser()    { return sessionStorage.getItem('sneha_user'); },
  getRole()    { return sessionStorage.getItem('sneha_role'); },
  isLoggedIn() { return !!this.getToken(); },
  logout: async function() {
    try { await api.post('/auth/logout'); } catch(e) {}
    sessionStorage.clear();
    window.location.href = 'login.html';
  },
  guard: function() {
    if (!this.isLoggedIn()) {
      window.location.href = 'login.html';
      return false;
    }
    return true;
  }
};

// ── TOAST ──────────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const colors = { success:'#1B6B2A', error:'#c0392b', warn:'#E67E22', info:'#1A2E6B' };
  const icons  = { success:'✅', error:'❌', warn:'⚠️', info:'ℹ️' };
  const t = document.createElement('div');
  t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;
    background:${colors[type]||colors.info};color:white;padding:13px 22px;
    border-radius:10px;font-size:13px;font-weight:600;
    box-shadow:0 6px 24px rgba(0,0,0,.25);animation:fadeUp .4s ease;
    max-width:340px;line-height:1.5;font-family:'DM Sans',sans-serif;
    display:flex;gap:8px;align-items:center`;
  t.innerHTML = `<span>${icons[type]||'ℹ️'}</span><span>${msg}</span>`;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .4s'; setTimeout(()=>t.remove(),400); }, 3500);
}

// ── SIDEBAR ──────────────────────────────────────────────────
function initSidebar() {
  const page = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(a => {
    const href = (a.getAttribute('href') || '');
    if (href === page || href.endsWith('/' + page)) a.classList.add('active');
  });
  const uel = document.getElementById('sidebar-username');
  const rel = document.getElementById('sidebar-role');
  if (uel) uel.textContent = Auth.getUser() || 'Admin';
  if (rel) rel.textContent = Auth.getRole() || 'Admin';
}
function toggleSidebar() { document.getElementById('sidebar')?.classList.toggle('mob-open'); }

// ── CLOCK ──────────────────────────────────────────────────────
function startClock() {
  const tick = () => {
    const el = document.getElementById('topbar-time');
    if (el) el.textContent = new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) + ' IST';
  };
  tick(); setInterval(tick, 1000);
}

// ── FORMATTERS ────────────────────────────────────────────────
function fmtDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) + ' ' +
         d.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'});
}
function fmtDateShort(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
}

// ── STATUS BADGE ──────────────────────────────────────────────
function statusBadge(s) {
  const map = { new:'status-new', read:'status-read', replied:'status-replied', closed:'status-closed' };
  return `<span class="status-badge ${map[s]||'status-new'}">${s.charAt(0).toUpperCase()+s.slice(1)}</span>`;
}

// ── MODALS ────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open');    document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); document.body.style.overflow=''; }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
});
document.addEventListener('click', e => {
  if (e.target.classList?.contains('modal-overlay')) closeModal(e.target.id);
});

// ── CONFIRM DELETE DIALOG ────────────────────────────────────
function confirmDelete(msg) {
  return new Promise(resolve => {
    const overlay = document.createElement('div');
    overlay.style.cssText = `position:fixed;inset:0;background:rgba(10,22,40,.7);z-index:99990;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px)`;
    overlay.innerHTML = `
      <div style="background:white;border-radius:16px;padding:32px;max-width:380px;width:90%;text-align:center;animation:fadeUp .3s ease">
        <div style="font-size:40px;margin-bottom:16px">🗑️</div>
        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:#1A2E6B;margin-bottom:10px">Confirm Delete</h3>
        <p style="font-size:14px;color:#7A7A7A;margin-bottom:24px;line-height:1.6">${msg}</p>
        <div style="display:flex;gap:10px;justify-content:center">
          <button id="c-no"  style="padding:10px 24px;border-radius:8px;border:1.5px solid #E0D8C8;background:white;cursor:pointer;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif">Cancel</button>
          <button id="c-yes" style="padding:10px 24px;border-radius:8px;border:none;background:#c0392b;color:white;cursor:pointer;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif">Delete</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('#c-no').onclick  = () => { overlay.remove(); resolve(false); };
    overlay.querySelector('#c-yes').onclick = () => { overlay.remove(); resolve(true); };
    overlay.onclick = e => { if(e.target===overlay){ overlay.remove(); resolve(false); } };
  });
}

// ── BADGE UPDATER ──────────────────────────────────────────────
async function updateNewBadge() {
  try {
    const data = await api.get('/dashboard');
    const n = data.inquiries?.new || 0;
    document.querySelectorAll('.new-count').forEach(el => el.textContent = n);
    document.querySelectorAll('.new-count-badge').forEach(el => {
      el.textContent = n;
      el.style.display = n > 0 ? 'inline' : 'none';
    });
  } catch(e) {}
}

// ── LOGO ──────────────────────────────────────────────────────
function injectLogo() {
  const paths = ['../assets/images/logo_b64.txt', 'assets/images/logo_b64.txt', '../../assets/images/logo_b64.txt'];
  const tryNext = (i) => {
    if (i >= paths.length) return;
    const req = new XMLHttpRequest();
    req.open('GET', paths[i], true);
    req.onload = function() {
      if (req.status === 200 && req.responseText.length > 100) {
        const src = 'data:image/png;base64,' + req.responseText.trim();
        document.querySelectorAll('[data-logo]').forEach(el => el.src = src);
      } else { tryNext(i + 1); }
    };
    req.onerror = () => tryNext(i + 1);
    req.send();
  };
  tryNext(0);
}

// ── INIT ──────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  if (!Auth.guard()) return;
  initSidebar();
  startClock();
  injectLogo();
  updateNewBadge();
  setInterval(updateNewBadge, 30000);
});
