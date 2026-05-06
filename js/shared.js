// ============================================================
//  SNEHA ENTERPRISES — SHARED JS
//  Nav, scroll reveal, progress bar, animations, particles
// ============================================================

// ── SCROLL PROGRESS BAR ──
(function() {
  var bar = document.createElement('div');
  bar.id = 'progress-bar';
  document.body.prepend(bar);
  window.addEventListener('scroll', function() {
    var pct = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
    bar.style.width = pct + '%';
  });
})();

// ── NAV ACTIVE LINK ──
(function() {
  var path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a, .mobile-menu a').forEach(function(a) {
    if (a.getAttribute('href') && a.getAttribute('href').includes(path)) {
      a.classList.add('active');
    }
  });
})();

// ── HAMBURGER MENU ──
function toggleMenu() {
  var h = document.getElementById('hamburger');
  var m = document.getElementById('mobile-menu');
  if (!h || !m) return;
  h.classList.toggle('open');
  m.classList.toggle('open');
  document.body.style.overflow = m.classList.contains('open') ? 'hidden' : '';
}
function closeMenu() {
  var h = document.getElementById('hamburger');
  var m = document.getElementById('mobile-menu');
  if (h) h.classList.remove('open');
  if (m) m.classList.remove('open');
  document.body.style.overflow = '';
}

// ── SCROLL REVEAL ──
var revealObserver = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      entry.target.querySelectorAll('.counter').forEach(animateCounter);
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

function initReveal() {
  document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom').forEach(function(el) {
    revealObserver.observe(el);
  });
}

// ── COUNTER ANIMATION ──
function animateCounter(el) {
  var target = parseInt(el.getAttribute('data-target') || el.textContent);
  if (isNaN(target)) return;
  var suffix = el.getAttribute('data-suffix') || '+';
  var duration = 1500;
  var steps = 50;
  var increment = target / steps;
  var count = 0;
  var timer = setInterval(function() {
    count += increment;
    if (count >= target) { el.textContent = target + suffix; clearInterval(timer); }
    else el.textContent = Math.floor(count) + suffix;
  }, duration / steps);
}

// ── HERO PARTICLES ──
function initParticles(heroEl) {
  if (!heroEl) return;
  for (var i = 0; i < 14; i++) {
    var p = document.createElement('div');
    p.style.cssText = [
      'position:absolute', 'border-radius:50%', 'pointer-events:none', 'z-index:0',
      'background:rgba(255,255,255,' + (Math.random() * 0.07 + 0.02) + ')',
      'width:' + (Math.random() * 70 + 15) + 'px',
      'height:' + (Math.random() * 70 + 15) + 'px',
      'top:' + (Math.random() * 100) + '%',
      'left:' + (Math.random() * 100) + '%',
      'animation:floatY ' + (Math.random() * 4 + 3) + 's ' + (Math.random() * 2) + 's ease-in-out infinite'
    ].join(';');
    heroEl.appendChild(p);
  }
}

// ── HERO STAT COUNTER ──
function animateHeroStats() {
  document.querySelectorAll('.stat-n').forEach(function(el, i) {
    var orig = el.textContent;
    var num = parseInt(orig);
    if (!num) return;
    var suffix = orig.replace(/[0-9]/g, '');
    el.textContent = '0' + suffix;
    setTimeout(function() {
      var count = 0, steps = 40, inc = num / steps;
      var t = setInterval(function() {
        count += inc;
        if (count >= num) { el.textContent = orig; clearInterval(t); }
        else el.textContent = Math.floor(count) + suffix;
      }, 40);
    }, 900 + i * 150);
  });
}

// ── TOAST NOTIFICATION ──
function showToast(msg, type) {
  var t = document.createElement('div');
  t.style.cssText = [
    'position:fixed', 'bottom:28px', 'right:28px', 'z-index:9999',
    'background:' + (type === 'error' ? '#c0392b' : type === 'warn' ? '#e67e22' : '#1B5E20'),
    'color:white', 'padding:14px 24px', 'border-radius:10px', 'font-size:14px',
    'font-weight:600', 'font-family:DM Sans,sans-serif',
    'box-shadow:0 8px 28px rgba(0,0,0,.2)',
    'animation:fadeUp .4s ease both', 'max-width:320px', 'line-height:1.5'
  ].join(';');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(function() {
    t.style.opacity = '0'; t.style.transition = 'opacity .4s';
    setTimeout(function() { t.remove(); }, 400);
  }, 3500);
}

// ── INIT ON LOAD ──
window.addEventListener('DOMContentLoaded', function() {
  initReveal();
  animateHeroStats();
  initParticles(document.querySelector('.hero'));
});

// ── FLOATING CONTACT TOGGLE ───────────────────────────────────
var floatOpen = false;
function toggleFloat() {
  floatOpen = !floatOpen;
  var opts = document.getElementById('float-opts');
  var btn  = document.getElementById('float-toggle');
  if (opts) opts.classList.toggle('open', floatOpen);
  if (btn)  btn.textContent = floatOpen ? '✕' : '💬';
}
// Close on outside click
document.addEventListener('click', function(e) {
  if (!e.target.closest('#float-btns') && floatOpen) toggleFloat();
});
