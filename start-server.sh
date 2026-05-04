#!/bin/bash
# ============================================================
#  SNEHA ENTERPRISES — SERVER STARTER
#  Run this to start the backend API + open website
# ============================================================

echo ""
echo "🌾 ============================================"
echo "   SNEHA ENTERPRISES — Starting Backend API"
echo "============================================ 🌾"
echo ""

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 not found. Please install Python 3.8+"
    exit 1
fi

# Check Flask
if ! python3 -c "import flask" &> /dev/null; then
    echo "📦 Installing dependencies..."
    pip3 install flask flask-cors --break-system-packages
fi

echo "✅ Starting API server at http://localhost:5000"
echo "🔐 Admin Panel: Open admin/login.html in your browser"
echo "🌐 Website:     Open index.html in your browser"
echo ""
echo "📌 Default credentials: admin / sneha2024"
echo "📁 Database: backend/sneha.db (auto-created)"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

cd backend
python3 server.py
