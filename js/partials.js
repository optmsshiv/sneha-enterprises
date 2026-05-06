// ============================================================
//  SNEHA ENTERPRISES — HTML PARTIALS
//  Injects shared nav + footer + ticker into every page
// ============================================================

var TICKER_HTML = `
<div class="ticker">
  <div class="ticker-inner">
    <span class="ticker-item"><span class="ticker-dot"></span>Premium Wheat Exports</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Grade-A Maize</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Organic Turmeric</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Fox Nuts (Makhana)</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Fresh Vegetables</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Premium Paddy &amp; Rice</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Worldwide Shipping</span>
    <span class="ticker-item"><span class="ticker-dot"></span>APEDA Certified Exporter</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Quality Products, Global Trust</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Premium Wheat Exports</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Grade-A Maize</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Organic Turmeric</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Fox Nuts (Makhana)</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Fresh Vegetables</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Premium Paddy &amp; Rice</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Worldwide Shipping</span>
    <span class="ticker-item"><span class="ticker-dot"></span>APEDA Certified Exporter</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Quality Products, Global Trust</span>
  </div>
</div>`;

var NAV_HTML = `
<nav>
  <div class="nav-inner">
    <a class="logo-wrap" href="../index.html">
      <img data-logo src="" alt="Sneha Enterprises">
    </a>
    <ul class="nav-links">
      <li><a href="../index.html">Home</a></li>
      <li><a href="../pages/products.html">Products</a></li>
      <li class="nav-dropdown">
        <a href="../pages/about.html" class="nav-drop-trigger">About ▾</a>
        <div class="nav-drop-menu">
          <a href="../pages/about.html">About Us</a>
          <a href="../pages/about.html#certs">Certifications</a>
          <a href="../pages/export-process.html">Export Process</a>
          <a href="../pages/gallery.html">Gallery</a>
        </div>
      </li>
      <li><a href="../pages/contact.html" class="nav-cta">Get a Quote</a></li>
    </ul>
    <button class="hamburger" id="hamburger" onclick="toggleMenu()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>
<div class="mobile-menu" id="mobile-menu">
  <a href="../index.html" onclick="closeMenu()">Home</a>
  <a href="../pages/products.html" onclick="closeMenu()">Products</a>
  <a href="../pages/about.html" onclick="closeMenu()">About Us</a>
  <a href="../pages/export-process.html" onclick="closeMenu()">Export Process</a>
  <a href="../pages/gallery.html" onclick="closeMenu()">Gallery</a>
  <a href="../pages/contact.html" onclick="closeMenu()" class="m-cta">Get a Quote →</a>
</div>`;

var NAV_ROOT_HTML = NAV_HTML
  .replace(/href="\.\.\/index\.html"/g, 'href="index.html"')
  .replace(/href="\.\.\/pages\//g, 'href="pages/');

var FOOTER_HTML = `
<!-- FONT AWESOME (loaded once globally via partials) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- FLOATING CONTACT BUTTONS -->
<div class="float-btns" id="float-btns">
  <div class="float-options" id="float-opts">
    <a href="https://wa.me/919876543210?text=Hi%20Sneha%20Enterprises%2C%20I%20am%20interested%20in%20your%20products." target="_blank" class="float-option">
      <span class="float-label">WhatsApp Chat</span>
      <div class="float-icon fi-wa"><i class="fa-brands fa-whatsapp"></i></div>
    </a>
    <a href="tel:+919876543210" class="float-option">
      <span class="float-label">Call Now</span>
      <div class="float-icon fi-call"><i class="fa-solid fa-phone"></i></div>
    </a>
    <a href="mailto:exports@snehaenterprises.in" class="float-option">
      <span class="float-label">Send Email</span>
      <div class="float-icon fi-mail"><i class="fa-solid fa-envelope"></i></div>
    </a>
    <a href="https://maps.google.com/?q=25.5941,85.1376" target="_blank" class="float-option">
      <span class="float-label">Find Us</span>
      <div class="float-icon" style="background:#e74c3c"><i class="fa-solid fa-location-dot"></i></div>
    </a>
  </div>
  <button class="float-main" id="float-toggle" onclick="toggleFloat()" title="Contact Us">
    <i class="fa-brands fa-whatsapp"></i>
  </button>
</div>

<footer>
  <div class="sec-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo"><img data-logo src="" alt="Sneha Enterprises"></div>
        <p>India's trusted agri-export partner — premium grains, spices, fox nuts and vegetables delivered to global buyers with certified quality and full documentation.</p>
        <div style="display:flex;gap:8px;align-items:center;margin-top:14px">
          <div style="width:6px;height:6px;background:var(--gold);border-radius:50%"></div>
          <span style="font-size:11px;color:rgba(255,255,255,.4)">IEC · GST · FSSAI · APEDA · Phytosanitary</span>
        </div>
        <div style="display:flex;gap:10px;margin-top:16px">
          <a href="https://wa.me/919876543210" target="_blank" style="width:36px;height:36px;border-radius:8px;background:#25D366;display:flex;align-items:center;justify-content:center;font-size:18px;text-decoration:none;color:white"><i class="fa-brands fa-whatsapp"></i></a>
          <a href="tel:+919876543210" style="width:36px;height:36px;border-radius:8px;background:#233480;display:flex;align-items:center;justify-content:center;font-size:15px;text-decoration:none;color:white"><i class="fa-solid fa-phone"></i></a>
          <a href="mailto:exports@snehaenterprises.in" style="width:36px;height:36px;border-radius:8px;background:#8B6318;display:flex;align-items:center;justify-content:center;font-size:15px;text-decoration:none;color:white"><i class="fa-solid fa-envelope"></i></a>
          <a href="https://maps.google.com/?q=25.5941,85.1376" target="_blank" style="width:36px;height:36px;border-radius:8px;background:#e74c3c;display:flex;align-items:center;justify-content:center;font-size:15px;text-decoration:none;color:white"><i class="fa-solid fa-location-dot"></i></a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Products</h5>
        <ul id="footer-product-links"><li><a href="pages/products.html">View All Products</a></li></ul>
      </div>
      <div class="footer-col">
        <h5>Company</h5>
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="pages/about.html">About Us</a></li>
          <li><a href="pages/about.html#certs">Certifications</a></li>
          <li><a href="pages/export-process.html">Export Process</a></li>
          <li><a href="pages/gallery.html">Gallery</a></li>
          <li><a href="pages/contact.html">Contact</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Contact</h5>
        <ul>
          <li><a><i class="fa-solid fa-location-dot" style="color:var(--gold);margin-right:6px"></i>Patna, Bihar – 800001, India</a></li>
          <li><a href="tel:+919876543210"><i class="fa-solid fa-phone" style="color:var(--gold);margin-right:6px"></i>+91 98765 43210</a></li>
          <li><a href="mailto:exports@snehaenterprises.in"><i class="fa-solid fa-envelope" style="color:var(--gold);margin-right:6px"></i>exports@snehaenterprises.in</a></li>
          <li><a href="https://wa.me/919876543210" target="_blank"><i class="fa-brands fa-whatsapp" style="color:#25D366;margin-right:6px"></i>WhatsApp Available</a></li>
          <li><a><i class="fa-regular fa-clock" style="color:var(--gold);margin-right:6px"></i>Mon–Sat: 9 AM – 6 PM IST</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2024 Sneha Enterprises. All rights reserved. | Registered Exporter from India | Patna, Bihar</p>
      <div class="footer-soc">
        <div class="soc-btn" onclick="window.open('https://wa.me/919876543210','_blank')" title="WhatsApp" style="font-size:16px"><i class="fa-brands fa-whatsapp"></i></div>
        <div class="soc-btn" onclick="window.open('tel:+919876543210')" title="Call" style="font-size:14px"><i class="fa-solid fa-phone"></i></div>
        <div class="soc-btn" onclick="window.open('mailto:exports@snehaenterprises.in')" title="Email" style="font-size:14px"><i class="fa-solid fa-envelope"></i></div>
      </div>
    </div>
  </div>
</footer>`;

// Inject partials
window.addEventListener('DOMContentLoaded', function() {
  var isRoot = !window.location.pathname.includes('/pages/') && !window.location.pathname.includes('/admin/');

  // Ticker
  var tickerTarget = document.getElementById('ticker-placeholder');
  if (tickerTarget) tickerTarget.outerHTML = TICKER_HTML;

  // Nav
  var navTarget = document.getElementById('nav-placeholder');
  if (navTarget) navTarget.outerHTML = isRoot ? NAV_ROOT_HTML : NAV_HTML;

  // Footer - fix paths for sub-pages
  var footerTarget = document.getElementById('footer-placeholder');
  if (footerTarget) {
    var fhtml = FOOTER_HTML;
    if (!isRoot) {
      fhtml = fhtml.replace(/href="index\.html"/g, 'href="../index.html"')
                   .replace(/href="pages\//g, 'href="../pages/');
    }
    footerTarget.outerHTML = fhtml;
  }

  // Populate footer product links dynamically
  var fpLinks = document.getElementById('footer-product-links');
  if (fpLinks && typeof PRODUCTS_DATA !== 'undefined') {
    var prefix = isRoot ? 'pages/' : '../pages/';
    fpLinks.innerHTML = PRODUCTS_DATA.filter(function(p){ return p.active; }).slice(0,6).map(function(p){
      return '<li><a href="' + prefix + 'products.html">' + p.name + '</a></li>';
    }).join('');
  }

  // Re-run logo injection after DOM insert
  if (typeof injectLogos === 'function') injectLogos();

  // Re-run nav active highlighting
  var path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a, .mobile-menu a').forEach(function(a) {
    var href = a.getAttribute('href') || '';
    if (href.includes(path) && path !== 'index.html') a.classList.add('active');
    if (path === 'index.html' && (href === 'index.html' || href.endsWith('../index.html'))) a.classList.add('active');
  });
});
