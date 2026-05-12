// ============================================================
//  SNEHA ENTERPRISES — ADMIN LAYOUT PARTIAL
//  Injects sidebar + topbar into every admin page
// ============================================================

const SIDEBAR_HTML = `
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img data-logo src="" alt="SE" style="height:40px;width:auto;filter:brightness(0) invert(1)">
    <div>
      <div class="sidebar-logo-text">Sneha Enterprises</div>
      <div class="sidebar-logo-sub">Admin Panel</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a class="nav-item" href="dashboard.html"><span class="ni-icon">📊</span>Dashboard</a>
    <div class="nav-section-label">Inquiries</div>
    <a class="nav-item" href="inquiries.html">
      <span class="ni-icon">📨</span>All Inquiries
      <span class="nav-badge new-count-badge" style="display:none">0</span>
    </a>
    <div class="nav-section-label">Catalogue</div>
    <a class="nav-item" href="products.html"><span class="ni-icon">🌾</span>Products</a>
    <a class="nav-item" href="products.html?add=1"><span class="ni-icon">➕</span>Add Product</a>
    <div class="nav-section-label">Account</div>
    <a class="nav-item" href="settings.html"><span class="ni-icon">⚙️</span>Settings</a>
    <a class="nav-item" href="../index.html" target="_blank"><span class="ni-icon">🌐</span>View Website</a>
    <a class="nav-item" href="../pages/export-process.html" target="_blank"><span class="ni-icon">🚢</span>Export Process</a>
    <a class="nav-item" href="../pages/gallery.html" target="_blank"><span class="ni-icon">🖼️</span>Gallery</a>
    <a class="nav-item" href="#" onclick="Auth.logout();return false;" style="color:rgba(255,100,100,.8)">
      <span class="ni-icon">🚪</span>Logout
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="su-av">A</div>
      <div>
        <div class="su-name" id="sidebar-username">Admin</div>
        <div class="su-role" id="sidebar-role">Super Admin</div>
      </div>
      <button class="logout-btn" onclick="Auth.logout()">Exit</button>
    </div>
  </div>
</aside>`;

const TOPBAR_HTML = (title) => `
<div class="topbar">
  <div class="topbar-left">
    <button class="mob-menu-btn" onclick="toggleSidebar()">
      <span></span><span></span><span></span>
    </button>
    <h1 class="topbar-title">${title}</h1>
  </div>
  <div class="topbar-right">
    <div class="tb-new-badge" onclick="window.location='inquiries.html'" style="cursor:pointer">
      <span>📨</span>
      <span class="new-count">0</span> New
    </div>
    <span id="topbar-time" style="font-size:12px;color:var(--text-light)"></span>
  </div>
</div>`;

// Inject into page
function injectLayout(title) {
  const sidebarTarget = document.getElementById('sidebar-placeholder');
  if (sidebarTarget) sidebarTarget.outerHTML = SIDEBAR_HTML;
  const topbarTarget = document.getElementById('topbar-placeholder');
  if (topbarTarget) topbarTarget.outerHTML = TOPBAR_HTML(title || document.title.split('—')[0].trim());
}
