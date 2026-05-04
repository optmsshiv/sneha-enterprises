<?php
// ============================================================
//  SNEHA ENTERPRISES — Auth API
//  POST /api/auth/login
//  POST /api/auth/logout
//  GET  /api/auth/me
//  POST /api/auth/change-password
// ============================================================
require_once __DIR__ . '/core.php';

$method = getMethod();
$path   = trim(getPath(), '/');

// ── POST /api/auth/login ─────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, 'auth/login')) {
    $data = body();
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    if (!$username || !$password) error('Username and password required');

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || $admin['password'] !== hashPassword($password)) {
        error('Invalid username or password', 401);
    }

    // Create session token
    $token   = generateToken();
    $expires = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY);
    $db->prepare('INSERT INTO sessions (token, admin_id, username, expires_at) VALUES (?, ?, ?, ?)')
       ->execute([$token, $admin['id'], $admin['username'], $expires]);

    // Update last login
    $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")
       ->execute([$admin['id']]);

    // Clean expired sessions
    $db->exec("DELETE FROM sessions WHERE expires_at < NOW()");

    respond([
        'token'      => $token,
        'username'   => $admin['username'],
        'role'       => $admin['role'],
        'expires_at' => $expires,
        'message'    => 'Login successful'
    ]);
}

// ── POST /api/auth/logout ────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, 'auth/logout')) {
    $auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $auth);
    if ($token) {
        getDB()->prepare('DELETE FROM sessions WHERE token = ?')->execute([$token]);
    }
    respond(['message' => 'Logged out successfully']);
}

// ── GET /api/auth/me ─────────────────────────────────────────
if ($method === 'GET' && str_ends_with($path, 'auth/me')) {
    $session = requireAuth();
    $stmt = getDB()->prepare('SELECT id, username, email, role, last_login, created_at FROM admins WHERE username = ?');
    $stmt->execute([$session['username']]);
    $admin = $stmt->fetch();
    if (!$admin) error('Admin not found', 404);
    respond($admin);
}

// ── POST /api/auth/change-password ───────────────────────────
if ($method === 'POST' && str_ends_with($path, 'auth/change-password')) {
    $session = requireAuth();
    $data    = body();
    $oldPw   = $data['old_password'] ?? '';
    $newPw   = $data['new_password'] ?? '';
    if (strlen($newPw) < 6) error('New password must be at least 6 characters');

    $db   = getDB();
    $stmt = $db->prepare('SELECT password FROM admins WHERE username = ?');
    $stmt->execute([$session['username']]);
    $admin = $stmt->fetch();
    if ($admin['password'] !== hashPassword($oldPw)) error('Old password is incorrect', 401);

    $db->prepare('UPDATE admins SET password = ? WHERE username = ?')
       ->execute([hashPassword($newPw), $session['username']]);

    // Invalidate all other sessions
    $authToken = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
    $db->prepare("DELETE FROM sessions WHERE username = ? AND token != ?")
       ->execute([$session['username'], $authToken]);

    respond(['message' => 'Password changed. Other sessions invalidated.']);
}

error('Auth endpoint not found', 404);
