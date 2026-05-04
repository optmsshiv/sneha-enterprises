// ============================================================
//  SNEHA ENTERPRISES — ADMIN SHARED JS
//  Auth guard, API client, sidebar, toasts, utilities
// ============================================================

// ── API BASE URL ─────────────────────────────────────────────
// On cPanel: change to your domain, e.g. 'https://snehaenterprises.in/api'
// For local dev with PHP: 'http://localhost/sneha-enterprises/api'
const API_BASE = window.location.hostname === 'localhost'
  ? 'http://localhost/sneha-enterprises/api'
  : window.location.origin + '/api';

// ── AUTH ─────────────────────────────────────────────────────
const Auth = {
  getToken()    { return sessionStorage.getItem('sneha_token'); },
  getUser()     { return sessionStorage.getItem('sneha_user'); },
  getRole()     { return sessionStorage.getItem('sneha_role'); },
  isLoggedIn()  { return !!this.getToken(); },
  logout: async function() {
    try {
      await api.post('/auth/logout');
    } catch(e) {}
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

// ── API CLIENT ───────────────────────────────────────────────
const api = {
  async request(method, path, body) {
    const opts = {
      method,
      headers: {
        'Content-Type': 'application/json',
        ...(Auth.getToken() ? { 'Authorization': 'Bearer ' + Auth.getToken() } : {})
      }
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(API_BASE + path, opts);
    if (res.status === 401) { Auth.logout(); return; }
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
  },
  get:    (path)        => api.request('GET', path),
  post:   (path, body)  => api.request('POST', path, body),
  put:    (path, body)  => api.request('PUT', path, body),
  patch:  (path, body)  => api.request('PATCH', path, body),
  delete: (path)        => api.request('DELETE', path),
};

// ── TOAST ────────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const colors = { success:'#1B6B2A', error:'#c0392b', warn:'#E67E22', info:'#1A2E6B' };
  const t = document.createElement('div');
  t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;
    background:${colors[type]||colors.info};color:white;padding:12px 22px;
    border-radius:10px;font-size:13px;font-weight:600;
    box-shadow:0 6px 24px rgba(0,0,0,.25);animation:fadeUp .4s ease;
    max-width:320px;line-height:1.5;font-family:'DM Sans',sans-serif;display:flex;gap:8px;align-items:center`;
  const icons = { success:'✅', error:'❌', warn:'⚠️', info:'ℹ️' };
  t.innerHTML = `<span>${icons[type]||'ℹ️'}</span><span>${msg}</span>`;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .4s'; setTimeout(()=>t.remove(),400); }, 3500);
}

// ── SIDEBAR NAV ──────────────────────────────────────────────
function initSidebar() {
  // Set active link
  const page = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(a => {
    const href = a.getAttribute('href') || '';
    if (href === page || href.endsWith('/' + page)) {
      a.classList.add('active');
    }
  });
  // Set user info
  const userEl = document.getElementById('sidebar-username');
  if (userEl) userEl.textContent = Auth.getUser() || 'Admin';
  const roleEl = document.getElementById('sidebar-role');
  if (roleEl) roleEl.textContent = Auth.getRole() || 'Admin';
}

function toggleSidebar() {
  document.getElementById('sidebar')?.classList.toggle('mob-open');
}

// ── CLOCK ────────────────────────────────────────────────────
function startClock() {
  function tick() {
    const el = document.getElementById('topbar-time');
    if (el) el.textContent = new Date().toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit'}) + ' IST';
  }
  tick();
  setInterval(tick, 1000);
}

// ── DATE FORMATTER ───────────────────────────────────────────
function fmtDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) +
    ' ' + d.toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit' });
}
function fmtDateShort(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });
}

// ── STATUS BADGE ─────────────────────────────────────────────
function statusBadge(s) {
  const map = { new:'status-new', read:'status-read', replied:'status-replied', closed:'status-closed' };
  return `<span class="status-badge ${map[s]||'status-new'}">${s.charAt(0).toUpperCase()+s.slice(1)}</span>`;
}

// ── MODAL ────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open');    document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); document.body.style.overflow=''; }

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
});
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
});

// ── CONFIRM DIALOG ───────────────────────────────────────────
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
          <button id="conf-no"  style="padding:10px 24px;border-radius:8px;border:1.5px solid #E0D8C8;background:white;cursor:pointer;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif">Cancel</button>
          <button id="conf-yes" style="padding:10px 24px;border-radius:8px;border:none;background:#c0392b;color:white;cursor:pointer;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif">Delete</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('#conf-no').onclick  = () => { overlay.remove(); resolve(false); };
    overlay.querySelector('#conf-yes').onclick = () => { overlay.remove(); resolve(true); };
    overlay.onclick = e => { if(e.target===overlay){ overlay.remove(); resolve(false); } };
  });
}

// ── NEW INQUIRY BADGE UPDATER ────────────────────────────────
async function updateNewBadge() {
  try {
    const data = await api.get('/dashboard');
    const n = data.inquiries.new;
    document.querySelectorAll('.new-count').forEach(el => el.textContent = n);
    document.querySelectorAll('.new-count-badge').forEach(el => {
      el.textContent = n;
      el.style.display = n > 0 ? 'inline' : 'none';
    });
  } catch(e) {}
}

// ── LOGO INJECT ──────────────────────────────────────────────
function injectLogo() {
  const req = new XMLHttpRequest();
  req.open('GET', '../assets/images/logo_b64.txt', true);
  req.onload = function() {
    const src = 'data:image/png;base64,' + req.responseText.trim();
    document.querySelectorAll('[data-logo]').forEach(el => el.src = src);
  };
  req.onerror = () => {
    // fallback: try from same dir
    const req2 = new XMLHttpRequest();
    req2.open('GET', 'assets/images/logo_b64.txt', true);
    req2.onload = function() {
      const src = 'data:image/png;base64,' + req2.responseText.trim();
      document.querySelectorAll('[data-logo]').forEach(el => el.src = src);
    };
    req2.send();
  };
  req.send();
}

// ── SHARED INIT ──────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  if (!Auth.guard()) return;
  initSidebar();
  startClock();
  injectLogo();
  updateNewBadge();
  setInterval(updateNewBadge, 30000);
});
