<?php
// ============================================================
//  SNEHA ENTERPRISES — API Entry Point / Health Check
//  GET /api/health  → server status
//  GET /api/        → API info
// ============================================================
require_once __DIR__ . '/core.php';

$path = trim(getPath(), '/');

// Health check
if (str_ends_with($path, 'health') || $path === 'api') {
    // Test DB connection
    $dbOk = false;
    $dbMsg = '';
    try {
        $db = getDB();
        $db->query('SELECT 1');
        $count = (int)$db->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
        $dbOk  = true;
        $dbMsg = "Connected — $count inquiries in DB";
    } catch (Exception $e) {
        $dbMsg = $e->getMessage();
    }

    respond([
        'status'    => $dbOk ? 'ok' : 'db_error',
        'message'   => 'Sneha Enterprises API — PHP + MySQL',
        'version'   => '2.0.0',
        'php'       => phpversion(),
        'server'    => $_SERVER['SERVER_SOFTWARE'] ?? 'cPanel',
        'database'  => $dbMsg,
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'POST /api/auth/login',
            'POST /api/auth/logout',
            'GET  /api/auth/me',
            'GET  /api/products',
            'POST /api/products',
            'PUT  /api/products/{id}',
            'POST /api/inquiries',
            'GET  /api/inquiries',
            'GET  /api/inquiries/stats',
            'GET  /api/dashboard',
        ]
    ]);
}

respond(['error' => 'Endpoint not found. See /api/health for available routes.'], 404);
