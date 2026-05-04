<?php
// ============================================================
//  SNEHA ENTERPRISES — Inquiries API
//  POST   /api/inquiries          (public - submit inquiry)
//  GET    /api/inquiries          (auth   - list all)
//  GET    /api/inquiries/stats    (auth   - stats)
//  GET    /api/inquiries/{id}     (auth   - single)
//  PATCH  /api/inquiries/{id}     (auth   - update status/notes)
//  DELETE /api/inquiries/{id}     (auth   - delete one)
//  DELETE /api/inquiries          (auth   - delete all)
// ============================================================
require_once __DIR__ . '/core.php';

$method  = getMethod();
$pathStr = trim(getPath(), '/');
$parts   = explode('/', $pathStr);
// parts: [api, inquiries] or [api, inquiries, {id_or_stats}]
$inqId   = isset($parts[2]) && $parts[2] !== 'stats' ? $parts[2] : null;
$isStats = isset($parts[2]) && $parts[2] === 'stats';

// ── POST /api/inquiries — Submit new (public) ─────────────────
if ($method === 'POST' && !$inqId) {
    $data = body();
    $name  = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    if (!$name || !$email) error('Name and email are required');

    $db  = getDB();
    $id  = 'INQ-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $now = date('Y-m-d H:i:s');

    $db->prepare("
        INSERT INTO inquiries
        (id, name, email, company, country, phone, product_id, product_name,
         quantity, incoterm, message, source, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?)
    ")->execute([
        $id,
        $name,
        strtolower($email),
        trim($data['company']     ?? ''),
        trim($data['country']     ?? ''),
        trim($data['phone']       ?? ''),
        trim($data['productId']   ?? ''),
        trim($data['productName'] ?? ''),
        trim($data['qty']         ?? ''),
        trim($data['incoterm']    ?? 'FOB'),
        trim($data['message']     ?? ''),
        trim($data['source']      ?? 'website'),
        $now, $now
    ]);

    // Fetch full row for email
    $stmt = $db->prepare('SELECT * FROM inquiries WHERE id = ?');
    $stmt->execute([$id]);
    $inq = $stmt->fetch();

    // Send email notifications (non-blocking)
    try { sendInquiryEmails($inq); } catch (Exception $e) { /* log but don't fail */ error_log('[EMAIL] ' . $e->getMessage()); }

    respond(['id' => $id, 'message' => 'Inquiry submitted successfully'], 201);
}

// ── GET /api/inquiries/stats ──────────────────────────────────
if ($method === 'GET' && $isStats) {
    requireAuth();
    $db = getDB();

    $total   = $db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
    $new     = $db->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
    $replied = $db->query("SELECT COUNT(*) FROM inquiries WHERE status='replied'")->fetchColumn();
    $today   = $db->query("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at)=CURDATE()")->fetchColumn();

    $byProduct = $db->query("SELECT product_name, COUNT(*) as count FROM inquiries GROUP BY product_name ORDER BY count DESC LIMIT 10")->fetchAll();
    $byStatus  = $db->query("SELECT status, COUNT(*) as count FROM inquiries GROUP BY status")->fetchAll();
    $recent7d  = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count FROM inquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY day ORDER BY day")->fetchAll();

    respond(compact('total','new','replied','today','byProduct','byStatus','recent7d'));
}

// ── GET /api/inquiries — List (auth) ──────────────────────────
if ($method === 'GET' && !$inqId) {
    requireAuth();
    $db     = getDB();
    $status  = $_GET['status']  ?? '';
    $product = $_GET['product'] ?? '';
    $search  = $_GET['search']  ?? '';
    $page    = max(1, (int)($_GET['page']     ?? 1));
    $perPage = min(100, (int)($_GET['per_page'] ?? 20));
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $params = [];
    if ($status)  { $where[] = 'status = ?';       $params[] = $status; }
    if ($product) { $where[] = 'product_id = ?';   $params[] = $product; }
    if ($search)  {
        $where[]  = '(name LIKE ? OR email LIKE ? OR company LIKE ? OR product_name LIKE ?)';
        $like     = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = $db->prepare("SELECT COUNT(*) FROM inquiries $whereSQL");
    $total->execute($params);
    $total = (int)$total->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM inquiries $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();

    respond([
        'inquiries' => $inquiries,
        'total'     => $total,
        'page'      => $page,
        'per_page'  => $perPage,
        'pages'     => max(1, (int)ceil($total / $perPage))
    ]);
}

// ── GET /api/inquiries/{id} ───────────────────────────────────
if ($method === 'GET' && $inqId) {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM inquiries WHERE id = ?');
    $stmt->execute([$inqId]);
    $inq  = $stmt->fetch();
    if (!$inq) error('Inquiry not found', 404);

    // Auto-mark as read if new
    if ($inq['status'] === 'new') {
        $db->prepare("UPDATE inquiries SET status='read', updated_at=NOW() WHERE id=?")
           ->execute([$inqId]);
        $inq['status'] = 'read';
    }
    respond($inq);
}

// ── PATCH /api/inquiries/{id} — Update status/notes ───────────
if ($method === 'PATCH' && $inqId) {
    requireAuth();
    $data    = body();
    $db      = getDB();
    $stmt    = $db->prepare('SELECT id FROM inquiries WHERE id = ?');
    $stmt->execute([$inqId]);
    if (!$stmt->fetch()) error('Inquiry not found', 404);

    $allowed = ['status', 'notes'];
    $sets    = [];
    $vals    = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $sets[] = "$field = ?";
            $vals[] = $data[$field];
        }
    }
    if (!$sets) error('Nothing to update');
    $sets[] = 'updated_at = NOW()';
    $db->prepare('UPDATE inquiries SET ' . implode(', ', $sets) . ' WHERE id = ?')
       ->execute(array_merge($vals, [$inqId]));
    respond(['message' => 'Updated', 'id' => $inqId]);
}

// ── DELETE /api/inquiries/{id} ────────────────────────────────
if ($method === 'DELETE' && $inqId) {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM inquiries WHERE id = ?');
    $stmt->execute([$inqId]);
    if (!$stmt->fetch()) error('Inquiry not found', 404);
    $db->prepare('DELETE FROM inquiries WHERE id = ?')->execute([$inqId]);
    respond(['message' => 'Deleted', 'id' => $inqId]);
}

// ── DELETE /api/inquiries — Delete all ────────────────────────
if ($method === 'DELETE' && !$inqId) {
    requireAuth();
    getDB()->exec('DELETE FROM inquiries');
    respond(['message' => 'All inquiries deleted']);
}

error('Inquiries endpoint not found', 404);
