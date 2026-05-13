<?php
// ============================================================
//  SNEHA ENTERPRISES — Gallery API  (drop in: api/gallery.php)
//  This file is included by index.php when route starts with 'gallery'
//
//  GET    /api/gallery                 — list all (public)
//  GET    /api/gallery/stats           — counts per category (auth)
//  POST   /api/gallery/upload          — multipart upload (auth)
//  PUT    /api/gallery/{id}            — edit meta (auth)
//  PATCH  /api/gallery/{id}/toggle     — active on/off (auth)
//  DELETE /api/gallery/{id}            — delete image + file (auth)
//
//  Images saved to:  /assets/gallery/   (relative to site root)
//  Public URL:       /assets/gallery/{filename}
// ============================================================

// Upload directory — adjust if your folder structure is different
define('GALLERY_DIR',  $_SERVER['DOCUMENT_ROOT'] . '/assets/gallery/');
define('GALLERY_URL',  'https://snehaenterprises.store/assets/gallery/');
define('MAX_IMG_SIZE', 5 * 1024 * 1024);      // 5 MB
define('ALLOWED_MIME', ['image/jpeg','image/png','image/webp','image/gif']);

// Ensure upload dir exists
if (!is_dir(GALLERY_DIR)) {
    mkdir(GALLERY_DIR, 0755, true);
}

$gid    = $r1 && $r1 !== 'stats' && $r1 !== 'upload' ? $r1 : null;
$isStat = ($r1 === 'stats');
$isUpld = ($r1 === 'upload');
$isToggle = ($r2 === 'toggle');

// ── GET /api/gallery — public list ───────────────────────────
if (M() === 'GET' && !$gid && !$isStat) {
    $db  = getDB();
    $w   = []; $p = [];

    if (!empty($_GET['category'])) { $w[] = 'category=?'; $p[] = $_GET['category']; }
    if (!empty($_GET['active']))   { $w[] = 'active=1'; }
    if (!empty($_GET['search']))   {
        $lk  = '%' . $_GET['search'] . '%';
        $w[] = '(title LIKE ? OR sub_label LIKE ?)';
        array_push($p, $lk, $lk);
    }

    $wSQL = $w ? 'WHERE ' . implode(' AND ', $w) : '';
    $rows = $db->prepare("SELECT * FROM gallery_images $wSQL ORDER BY sort_order ASC, created_at DESC");
    $rows->execute($p);
    $items = $rows->fetchAll(PDO::FETCH_ASSOC);

    // Append public URL
    foreach ($items as &$item) {
        $item['url']    = GALLERY_URL . $item['filename'];
        $item['active'] = (bool)$item['active'];
    }
    respond(['items' => $items, 'total' => count($items)]);
}

// ── GET /api/gallery/stats ────────────────────────────────────
if (M() === 'GET' && $isStat) {
    auth(); $db = getDB();
    $total    = (int)$db->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
    $byCat    = $db->query("SELECT category, COUNT(*) as count FROM gallery_images GROUP BY category")->fetchAll(PDO::FETCH_ASSOC);
    $latest   = $db->query("SELECT created_at FROM gallery_images ORDER BY created_at DESC LIMIT 1")->fetchColumn();
    respond(['total' => $total, 'by_category' => $byCat, 'latest_upload' => $latest ?: null]);
}

// ── POST /api/gallery/upload — multipart (auth) ───────────────
if (M() === 'POST' && $isUpld) {
    auth();

    // Accepts multipart/form-data with field: images[] (multiple files)
    // Plus text fields: title[], sub_label, category, order_start
    if (empty($_FILES['images'])) {
        apiErr('No images uploaded. Use field name "images[]".');
    }

    $db       = getDB();
    $category = $_POST['category']    ?? 'other';
    $subLabel = trim($_POST['sub_label'] ?? '');
    $orderStart = (int)$db->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM gallery_images')->fetchColumn();

    $allowedCats = ['products','facility','shipping','farms','other'];
    if (!in_array($category, $allowedCats)) $category = 'other';

    $files   = $_FILES['images'];
    $count   = is_array($files['name']) ? count($files['name']) : 1;
    $created = [];
    $errors  = [];

    // Normalise to array
    if (!is_array($files['name'])) {
        foreach ($files as $k => $v) $files[$k] = [$v];
    }

    for ($i = 0; $i < $count; $i++) {
        $origName = $files['name'][$i];
        $tmpPath  = $files['tmp_name'][$i];
        $errCode  = $files['error'][$i];
        $size     = $files['size'][$i];
        $clientTitle = trim(($_POST['title'][$i] ?? ''));

        if ($errCode !== UPLOAD_ERR_OK) { $errors[] = "$origName: upload error $errCode"; continue; }
        if ($size > MAX_IMG_SIZE)       { $errors[] = "$origName: exceeds 5 MB";           continue; }

        $mime = mime_content_type($tmpPath);
        if (!in_array($mime, ALLOWED_MIME)) { $errors[] = "$origName: not a valid image type ($mime)"; continue; }

        // Generate unique filename
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION)) ?: 'jpg';
        $uid      = 'img_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        $filename = $uid . '.' . $ext;
        $destPath = GALLERY_DIR . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errors[] = "$origName: failed to save file";
            continue;
        }

        // Auto-title from filename if not provided
        if (!$clientTitle) {
            $clientTitle = ucwords(str_replace(['-','_'], ' ', pathinfo($origName, PATHINFO_FILENAME)));
        }

        $id = $uid;
        $db->prepare("INSERT INTO gallery_images
            (id, title, sub_label, category, filename, mime_type, file_size, sort_order, active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())")
          ->execute([$id, $clientTitle, $subLabel, $category, $filename, $mime, $size, $orderStart + $i]);

        $created[] = [
            'id'       => $id,
            'title'    => $clientTitle,
            'sub_label'=> $subLabel,
            'category' => $category,
            'filename' => $filename,
            'url'      => GALLERY_URL . $filename,
            'file_size'=> $size,
        ];
    }

    respond([
        'uploaded' => count($created),
        'errors'   => $errors,
        'items'    => $created,
        'message'  => count($created) . ' image(s) uploaded successfully'
    ], 201);
}

// ── PUT /api/gallery/{id} — edit meta (auth) ─────────────────
if (M() === 'PUT' && $gid && !$isToggle) {
    auth(); $db = getDB();
    $chk = $db->prepare('SELECT 1 FROM gallery_images WHERE id=?');
    $chk->execute([$gid]);
    if (!$chk->fetch()) apiErr('Image not found', 404);

    $d        = body();
    $allowedCats = ['products','facility','shipping','farms','other'];
    $category = in_array($d['category'] ?? '', $allowedCats) ? $d['category'] : 'other';

    $db->prepare("UPDATE gallery_images SET title=?, sub_label=?, category=?, sort_order=?, updated_at=NOW() WHERE id=?")
       ->execute([
           trim($d['title']     ?? ''),
           trim($d['sub_label'] ?? ''),
           $category,
           (int)($d['sort_order'] ?? 0),
           $gid
       ]);
    respond(['message' => 'Updated', 'id' => $gid]);
}

// ── PATCH /api/gallery/{id}/toggle ───────────────────────────
if (M() === 'PATCH' && $gid && $isToggle) {
    auth(); $db = getDB();
    $row = $db->prepare('SELECT active FROM gallery_images WHERE id=?');
    $row->execute([$gid]);
    $img = $row->fetch(PDO::FETCH_ASSOC);
    if (!$img) apiErr('Image not found', 404);
    $new = $img['active'] ? 0 : 1;
    $db->prepare('UPDATE gallery_images SET active=?, updated_at=NOW() WHERE id=?')->execute([$new, $gid]);
    respond(['active' => (bool)$new, 'id' => $gid]);
}

// ── DELETE /api/gallery/{id} ──────────────────────────────────
if (M() === 'DELETE' && $gid) {
    auth(); $db = getDB();
    $row = $db->prepare('SELECT filename FROM gallery_images WHERE id=?');
    $row->execute([$gid]);
    $img = $row->fetch(PDO::FETCH_ASSOC);
    if (!$img) apiErr('Image not found', 404);

    // Delete physical file
    $filePath = GALLERY_DIR . $img['filename'];
    if (file_exists($filePath)) @unlink($filePath);

    $db->prepare('DELETE FROM gallery_images WHERE id=?')->execute([$gid]);
    respond(['message' => 'Deleted', 'id' => $gid]);
}

apiErr('Gallery endpoint not found', 404);