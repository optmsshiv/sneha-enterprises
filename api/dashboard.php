<?php
// ============================================================
//  SNEHA ENTERPRISES — Dashboard API
//  GET /api/dashboard  (auth)
// ============================================================
require_once __DIR__ . '/core.php';

requireAuth();
$db = getDB();

$total   = (int)$db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$new     = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
$replied = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status='replied'")->fetchColumn();
$today   = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$prodTotal  = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$prodActive = (int)$db->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn();

$recentInq = $db->query("
    SELECT id,name,email,company,product_name,country,status,created_at
    FROM inquiries ORDER BY created_at DESC LIMIT 8
")->fetchAll();

$topProducts = $db->query("
    SELECT product_name, COUNT(*) as count
    FROM inquiries GROUP BY product_name
    ORDER BY count DESC LIMIT 8
")->fetchAll();

$recent7d = $db->query("
    SELECT DATE(created_at) as day, COUNT(*) as count
    FROM inquiries
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY day ORDER BY day
")->fetchAll();

respond([
    'inquiries' => [
        'total'   => $total,
        'new'     => $new,
        'replied' => $replied,
        'today'   => $today,
    ],
    'products' => [
        'total'  => $prodTotal,
        'active' => $prodActive,
    ],
    'recent_inquiries' => $recentInq,
    'top_products'     => $topProducts,
    'recent_7d'        => $recent7d,
]);
