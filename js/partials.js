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
      <li><a href="../pages/about.html">About Us</a></li>
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
  <a href="../pages/contact.html" onclick="closeMenu()" class="m-cta">Get a Quote →</a>
</div>`;

var NAV_ROOT_HTML = NAV_HTML
  .replace(/href="\.\.\/index\.html"/g, 'href="index.html"')
  .replace(/href="\.\.\/pages\//g, 'href="pages/')
  .replace(/href="\.\.\/pages\/contact\.html"/g, 'href="pages/contact.html"');

var FOOTER_HTML = `
<footer>
  <div class="sec-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo"><img data-logo src="" alt="Sneha Enterprises"></div>
        <p>India's trusted agri-export partner. Premium grains, spices, fox nuts and fresh vegetables — delivered to global buyers with certified quality.</p>
        <div style="display:flex;gap:8px;align-items:center;margin-top:14px">
          <div style="width:6px;height:6px;background:var(--gold);border-radius:50%"></div>
          <span style="font-size:11px;color:rgba(255,255,255,.4)">APEDA · ISO 9001 · FSSAI · DGFT Registered</span>
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
          <li><a href="pages/about.html#team">Leadership</a></li>
          <li><a href="pages/about.html#certs">Certifications</a></li>
          <li><a href="pages/contact.html">Contact</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Contact</h5>
        <ul>
          <li><a>Patna, Bihar – 800001, India</a></li>
          <li><a href="tel:+919876543210">+91 98765 43210</a></li>
          <li><a href="mailto:exports@snehaenterprises.in">exports@snehaenterprises.in</a></li>
          <li><a>Mon–Sat: 9 AM – 6 PM IST</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2024 Sneha Enterprises. All rights reserved. | Export &amp; Import of Agricultural Products | Patna, Bihar</p>
      <div class="footer-soc">
        <div class="soc-btn">in</div>
        <div class="soc-btn">wa</div>
        <div class="soc-btn">fb</div>
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
