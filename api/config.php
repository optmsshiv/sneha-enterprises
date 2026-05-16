<?php
// ============================================================
//  SNEHA ENTERPRISES — DATABASE CONFIG
//  Fill in your cPanel MySQL credentials here
//  cPanel → MySQL Databases → Create Database & User
// ============================================================

define('DB_HOST',     'localhost');          // Always localhost on cPanel
define('DB_NAME',     'edrppymy_sneha_enterprise');        // Your database name (cpanel_prefix_dbname)
define('DB_USER',     'edrppymy_sneha_enterprise');  // Your DB username from screenshot
define('DB_PASS',     '13579@Admin');   // ← Replace with your password
define('DB_CHARSET',  'utf8mb4');

// JWT / Token
define('JWT_SECRET',  'sneha-secret-key-change-this-2024');
define('TOKEN_EXPIRY', 86400); // 24 hours in seconds

// Email config (cPanel uses PHP mail() or SMTP)
define('ADMIN_EMAIL',    'exports@snehaenterprises.store');   // ← Your admin email
define('COMPANY_EMAIL',  'exports@snehaenterprises.store'); // ← Company email
define('COMPANY_NAME',   'Sneha Enterprises');
define('USE_SMTP',        true); // set true + fill SMTP_ vars to use SMTP
define('SMTP_HOST',       'smtp.gmail.com');   // your cPanel mail server
define('SMTP_PORT',        587);
define('SMTP_USER',       'snehaenterprises310@gmail.com');
define('SMTP_PASS',       'iyqi pbum hnbz hjuy');        // ← Replace

// ── DB Connection ─────────────────────────────────────────
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
