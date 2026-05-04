<?php
// ============================================================
//  SNEHA ENTERPRISES — Products API
//  GET    /api/products           (public)
//  GET    /api/products/{id}      (public)
//  POST   /api/products           (auth)
//  PUT    /api/products/{id}      (auth)
//  PATCH  /api/products/{id}/toggle (auth)
//  DELETE /api/products/{id}      (auth)
// ============================================================
require_once __DIR__ . '/core.php';

$method  = getMethod();
$pathStr = trim(getPath(), '/');
$parts   = explode('/', $pathStr);
// parts: [api, products] or [api, products, {id}] or [api, products, {id}, toggle]
$prodId   = $parts[2] ?? null;
$isToggle = isset($parts[3]) && $parts[3] === 'toggle';

// ── GET /api/products ─────────────────────────────────────────
if ($method === 'GET' && !$prodId) {
    $db       = getDB();
    $category = $_GET['category'] ?? '';
    $active   = $_GET['active']   ?? '';
    $search   = $_GET['search']   ?? '';

    $where  = [];
    $params = [];
    if ($category) { $where[] = 'category = ?'; $params[] = $category; }
    if ($active)   { $where[] = 'active = 1'; }
    if ($search)   {
        $where[]  = '(name LIKE ? OR origin LIKE ? OR description LIKE ?)';
        $like     = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("SELECT * FROM products $whereSQL ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Decode JSON fields
    foreach ($products as &$p) {
        $p['specs']    = json_decode($p['specs']    ?? '{}', true) ?: [];
        $p['packaging']= json_decode($p['packaging'] ?? '[]', true) ?: [];
        $p['active']   = (bool)$p['active'];
    }
    respond(['products' => $products, 'total' => count($products)]);
}

// ── GET /api/products/{id} ────────────────────────────────────
if ($method === 'GET' && $prodId && !$isToggle) {
    $stmt = getDB()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$prodId]);
    $p = $stmt->fetch();
    if (!$p) error('Product not found', 404);
    $p['specs']     = json_decode($p['specs']     ?? '{}', true) ?: [];
    $p['packaging'] = json_decode($p['packaging'] ?? '[]', true) ?: [];
    $p['active']    = (bool)$p['active'];
    respond($p);
}

// ── POST /api/products ────────────────────────────────────────
if ($method === 'POST' && !$prodId) {
    requireAuth();
    $data = body();
    if (empty($data['name']) || empty($data['category'])) error('name and category required');

    $db  = getDB();
    $id  = $data['id'] ?? (strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name'])) . '-' . substr(bin2hex(random_bytes(3)), 0, 6));
    // Ensure unique ID
    $check = $db->prepare('SELECT 1 FROM products WHERE id = ?');
    $check->execute([$id]);
    if ($check->fetch()) $id .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);

    // Count for sort order
    $sortOrder = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn() + 1;

    $db->prepare("
        INSERT INTO products (id, name, category, emoji, badge, bg, origin, description, specs, packaging, min_order, active, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $id,
        $data['name'],
        $data['category'],
        $data['emoji']      ?? '🌾',
        $data['badge']      ?? '',
        $data['bg']         ?? 'linear-gradient(135deg,#FFF8E1,#FFF0C0)',
        $data['origin']     ?? '',
        $data['description']?? '',
        json_encode($data['specs']     ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($data['packaging'] ?? [], JSON_UNESCAPED_UNICODE),
        $data['minOrder']   ?? 'On Request',
        isset($data['active']) ? ($data['active'] ? 1 : 0) : 1,
        $sortOrder
    ]);
    respond(['id' => $id, 'message' => 'Product created'], 201);
}

// ── PUT /api/products/{id} ────────────────────────────────────
if ($method === 'PUT' && $prodId && !$isToggle) {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('SELECT 1 FROM products WHERE id = ?');
    $stmt->execute([$prodId]);
    if (!$stmt->fetch()) error('Product not found', 404);

    $data = body();
    $db->prepare("
        UPDATE products SET
        name=?, category=?, emoji=?, badge=?, bg=?, origin=?, description=?,
        specs=?, packaging=?, min_order=?, active=?, updated_at=NOW()
        WHERE id=?
    ")->execute([
        $data['name'],
        $data['category'],
        $data['emoji']      ?? '🌾',
        $data['badge']      ?? '',
        $data['bg']         ?? '',
        $data['origin']     ?? '',
        $data['description']?? '',
        json_encode($data['specs']     ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($data['packaging'] ?? [], JSON_UNESCAPED_UNICODE),
        $data['minOrder']   ?? 'On Request',
        isset($data['active']) ? ($data['active'] ? 1 : 0) : 1,
        $prodId
    ]);
    respond(['message' => 'Updated', 'id' => $prodId]);
}

// ── PATCH /api/products/{id}/toggle ──────────────────────────
if ($method === 'PATCH' && $prodId && $isToggle) {
    requireAuth();
    $db  = getDB();
    $row = $db->prepare('SELECT active FROM products WHERE id = ?');
    $row->execute([$prodId]);
    $prod = $row->fetch();
    if (!$prod) error('Product not found', 404);
    $newActive = $prod['active'] ? 0 : 1;
    $db->prepare('UPDATE products SET active=?, updated_at=NOW() WHERE id=?')
       ->execute([$newActive, $prodId]);
    respond(['active' => (bool)$newActive, 'id' => $prodId]);
}

// ── DELETE /api/products/{id} ─────────────────────────────────
if ($method === 'DELETE' && $prodId) {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('SELECT 1 FROM products WHERE id = ?');
    $stmt->execute([$prodId]);
    if (!$stmt->fetch()) error('Product not found', 404);
    $db->prepare('DELETE FROM products WHERE id = ?')->execute([$prodId]);
    respond(['message' => 'Deleted', 'id' => $prodId]);
}

error('Products endpoint not found', 404);
