# 🌾 Sneha Enterprises — Full Stack Website
International Agri Export & Import | Patna, Bihar, India

## 🚀 Quick Start

### 1. Install Python dependencies
  pip3 install flask flask-cors

### 2. Start backend server
  bash start-server.sh
  # OR: cd backend && python3 server.py
  # API runs at: http://localhost:5000/api

### 3. Open website
  - Website: open index.html in browser
  - Admin:   open admin/login.html

## 🔐 Admin Login
  URL:      admin/login.html
  Username: admin
  Password: sneha2024
  ⚠️  Change password in Settings after first login!

## 📧 Enable Email Alerts (optional)
  export EMAIL_USER=your@gmail.com
  export EMAIL_PASS=your-app-password
  export ADMIN_EMAIL=admin@snehaenterprises.in
  python3 backend/server.py

## 📁 Key Files
  backend/server.py      - Flask API + SQLite
  backend/sneha.db       - Database (auto-created)
  admin/login.html       - Admin login page
  admin/dashboard.html   - Stats & overview
  admin/inquiries.html   - Manage inquiries
  admin/products.html    - Add/edit/delete products
  data/products.js       - Static fallback products
  .env.example           - Environment variables template

## 🌐 API Reference
  POST /api/auth/login             - Login
  POST /api/auth/logout            - Logout (auth)
  GET  /api/products               - List products
  POST /api/inquiries              - Submit inquiry (public)
  GET  /api/inquiries              - List inquiries (auth)
  GET  /api/dashboard              - Dashboard stats (auth)

  Full docs: See server.py for all endpoints

## 🗄️ Database Tables
  admins     - Admin accounts
  sessions   - Login tokens
  inquiries  - Buyer inquiries
  products   - Product catalogue
