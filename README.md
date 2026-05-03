# 🌾 Sneha Enterprises — Website Project
**International Agri Export & Import | Patna, Bihar, India**

---

## 📁 Folder Structure

```
sneha-enterprises/
│
├── index.html                  ← Homepage
├── README.md                   ← This file
│
├── css/
│   └── shared.css              ← Global styles (nav, footer, cards, animations)
│
├── js/
│   ├── logo.js                 ← Logo base64 injector
│   ├── partials.js             ← Shared nav + footer HTML templates
│   └── shared.js               ← Animations, scroll reveal, toast, particles
│
├── data/
│   ├── products.js             ← ⭐ PRODUCTS DATABASE (edit to add/remove products)
│   └── inquiries.js            ← Inquiry localStorage store
│
├── pages/
│   ├── products.html           ← 🌾 Fully dynamic product catalogue
│   ├── about.html              ← About Us + Team + Certifications
│   └── contact.html            ← Contact form (saves to admin)
│
├── admin/
│   ├── index.html              ← 🔐 Admin Panel (login: admin / sneha2024)
│   ├── css/
│   │   └── admin.css           ← Admin panel styles
│   └── js/
│       └── (admin JS is inline in admin/index.html)
│
└── assets/
    └── images/
        └── logo_b64.txt        ← Logo image as base64
```

---

## 🚀 How to Run

**Option 1 — Just open in browser:**
```
Double-click: index.html
```

**Option 2 — Local server (recommended):**
```bash
cd sneha-enterprises
npx serve .
# OR
python3 -m http.server 3000
```
Then open: http://localhost:3000

---

## 🔐 Admin Panel

**URL:** `admin/index.html`
**Login:** `admin` / `sneha2024`

### Admin Features:
| Feature | Description |
|---------|-------------|
| 📊 Dashboard | Stats, recent inquiries, enquiry-by-product chart |
| 📨 Inquiries | View, filter, search, mark status, reply by email, delete |
| 🌾 Products | Add, edit, toggle active/hidden, delete products |
| ⚙️ Settings | Configure email, password, contact details |

---

## 🌾 Managing Products

### Method 1 — Admin Panel (Recommended)
1. Open `admin/index.html`
2. Login with admin / sneha2024
3. Click **Products** → **Add New Product**
4. Fill in name, category, origin, specs, packaging
5. Click **Save Product**

> ✅ Changes saved to browser localStorage and reflected on website instantly.

### Method 2 — Edit data file directly
Edit `data/products.js` and add a product object:
```js
{
  id: "myproduct-001",          // Unique ID
  name: "My Product",           // Display name
  category: "grains",           // grains | spices | foxnuts | vegetables
  emoji: "🌾",                  // Display emoji
  badge: "New",                 // Optional: Best Seller, Organic, etc.
  bg: "linear-gradient(...)",   // Card background
  origin: "State, India",       // Origin region
  description: "...",           // Product description
  specs: { "Moisture": "Max 12%", "Purity": "99%" },
  packaging: ["25kg PP Bags"],  // Packaging options
  minOrder: "10 Metric Tons",   // Minimum order
  active: true                  // true = visible, false = hidden
}
```

---

## 📧 Email Integration (Production Setup)

Currently, inquiries are stored in **browser localStorage**. To send real email alerts:

### Option A — EmailJS (No backend needed)
```js
// In admin/index.html, find sendEmailAlert() and replace with:
emailjs.init("YOUR_PUBLIC_KEY");
emailjs.send("service_id", "template_id", {
  to_email: "admin@snehaenterprises.in",
  from_name: inquiry.name,
  product: inquiry.productName,
  // ... other fields
});
```

### Option B — Formspree
```js
fetch('https://formspree.io/f/YOUR_FORM_ID', {
  method: 'POST',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify(inquiry)
});
```

### Option C — Backend API (PHP/Node.js)
Replace the `setTimeout` in `submitEnquiry()` with a real `fetch()` call to your server.

---

## 🔧 Customization Guide

### Change Company Details
- Phone, email, address → `js/partials.js` (FOOTER_HTML section)
- Hero content → `index.html`
- Contact page → `pages/contact.html`

### Change Colors / Branding
Edit CSS variables in `css/shared.css`:
```css
:root {
  --navy: #1A2E6B;     /* Primary color */
  --gold: #C8912A;     /* Accent color */
  --green: #1B6B2A;    /* Secondary accent */
}
```

### Replace Logo
1. Convert your logo PNG to base64: `base64 -w0 logo.png > assets/images/logo_b64.txt`
2. The site auto-injects it everywhere via `js/logo.js`

### Change Admin Password
In `admin/index.html` find:
```js
var ADMIN_USER = 'admin', ADMIN_PASS = 'sneha2024';
```
Change to your own credentials.

---

## 🌐 Going Live

1. Upload all files to your web hosting (cPanel, Hostinger, etc.)
2. Point your domain (snehaenterprises.in) to the folder
3. Set up EmailJS or backend for real email delivery
4. Replace localStorage with a real database (MongoDB/MySQL)
5. Add SSL certificate (free via Let's Encrypt)

---

## 📞 Support
**Sneha Enterprises** | Patna, Bihar, India
exports@snehaenterprises.in | +91 98765 43210
