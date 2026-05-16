<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ============================================================
//  SNEHA ENTERPRISES — SINGLE FILE API ROUTER v2.1
//  ALL requests handled here — NO .htaccess needed
//
//  Usage:
//    api/index.php?route=health
//    api/index.php?route=auth/login       [POST]
//    api/index.php?route=products         [GET]
//    api/index.php?route=inquiries        [POST/GET]
//    api/index.php?route=dashboard        [GET]
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/config.php';

// ── PHPMailer via Composer ────────────────────────────────────
function sendEmailFallback(string $to, string $subject, string $html): bool {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: " . COMPANY_NAME . " <" . COMPANY_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . COMPANY_EMAIL . "\r\n";
    return @mail($to, $subject, $html, $headers);
}

function sendEmail(string $to, string $subject, string $html): bool {
    if (!USE_SMTP) return sendEmailFallback($to, $subject, $html);
    try {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log('[EMAIL] vendor/autoload.php not found. Run: composer require phpmailer/phpmailer');
            return sendEmailFallback($to, $subject, $html);
        }
        require_once $autoload;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(SMTP_USER, COMPANY_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','</p>','</div>'], "\n", $html));
        return $mail->send();
    } catch (\Throwable $ex) {
    error_log('[EMAIL SMTP ERROR] ' . $ex->getMessage());
    return sendEmailFallback($to, $subject, $html);
    }
}

function sendInquiryEmails(array $inq): void {
    // 1. Admin notification
    $adminHtml = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px">
      <div style="background:#1A2E6B;padding:20px;border-radius:10px;text-align:center;margin-bottom:24px">
        <h2 style="color:white;margin:0">🌾 New Trade Inquiry</h2>
        <p style="color:rgba(255,255,255,.7);margin:8px 0 0">Sneha Enterprises — Admin Alert</p>
      </div>
      <table style="width:100%;border-collapse:collapse">';
    foreach ([
        ['Reference ID', $inq['id']],
        ['Name',         $inq['name']],
        ['Company',      $inq['company'] ?: '—'],
        ['Email',        $inq['email']],
        ['Phone',        $inq['phone'] ?: '—'],
        ['Country',      $inq['country'] ?: '—'],
        ['Product',      $inq['product_name'] ?: '—'],
        ['Quantity',     $inq['quantity'] ?: '—'],
        ['Incoterm',     $inq['incoterm'] ?: '—'],
        ['Message',      $inq['message'] ?: '—'],
        ['Date',         $inq['created_at']],
        ['Source',       $inq['source']],
    ] as [$label, $value]) {
        $adminHtml .= "<tr>
          <td style='padding:10px;border-bottom:1px solid #eee;font-weight:700;color:#1A2E6B;width:140px'>$label</td>
          <td style='padding:10px;border-bottom:1px solid #eee;color:#333'>" . htmlspecialchars((string)$value) . "</td>
        </tr>";
    }
    $adminHtml .= '</table>
      <div style="margin-top:20px;text-align:center">
        <a href="https://snehaenterprises.store/admin/login.html" style="background:#C8912A;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700">View in Admin Panel →</a>
      </div>
    </div>';
    sendEmail(ADMIN_EMAIL, 'New Inquiry: ' . ($inq['product_name'] ?? 'Product') . ' — ' . $inq['name'], $adminHtml);

    // 2. Auto-reply to buyer
    $buyerHtml = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px">
      <div style="background:#1A2E6B;padding:20px;border-radius:10px;text-align:center;margin-bottom:24px">
        <h2 style="color:white;margin:0">🌾 Sneha Enterprises</h2>
        <p style="color:rgba(255,255,255,.7);margin:4px 0 0">Import · Export · Quality Assured</p>
      </div>
      <p style="font-size:16px">Dear <strong>' . htmlspecialchars($inq['name']) . '</strong>,</p>
      <p style="color:#555;line-height:1.7">Thank you for your inquiry about <strong>' . htmlspecialchars($inq['product_name'] ?? 'our products') . '</strong>. We have received your request and our trade team will review it immediately.</p>
      <div style="background:#FFF3E0;border-left:4px solid #C8912A;padding:16px;border-radius:8px;margin:20px 0">
        <strong>Your Reference ID: ' . htmlspecialchars($inq['id']) . '</strong><br>
        <span style="color:#666;font-size:13px">Please quote this in all future communications.</span>
      </div>
      <p style="color:#555;line-height:1.7">You can expect a detailed response within <strong>24 business hours</strong>, including competitive pricing, specifications and sample availability.</p>
      <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
      <p style="color:#888;font-size:13px">
        <strong>Sneha Enterprises</strong><br>
        Ward - 04, Basanwara, Alamnagar, Madhepura – 852210, Bihar, India<br>
        📞 +91 76317 11371 | ✉️ ' . COMPANY_EMAIL . '
      </p>
    </div>';
    sendEmail($inq['email'], 'Inquiry Received — Ref: ' . $inq['id'], $buyerHtml);
}

// ── Helpers ──────────────────────────────────────────────────
function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
function apiErr(string $msg, int $code = 400): void { respond(['error' => $msg], $code); }
function body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw ?: '{}', true) ?: [];
}
function M(): string { return strtoupper($_SERVER['REQUEST_METHOD']); }
function genToken(): string { return bin2hex(random_bytes(32)); }
function hashPw(string $pw): string { return hash('sha256', $pw . 'sneha_salt_2024'); }
function auth(): array {
    $hdr   = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['HTTP_X_AUTHORIZATION'] ?? '');
    $token = trim(str_replace('Bearer', '', $hdr));
    if (!$token) apiErr('No token. Please login.', 401);
    $stmt = getDB()->prepare("SELECT * FROM sessions WHERE token=? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$s) apiErr('Token expired or invalid. Please login again.', 401);
    return $s;
}
function decodeProduct(array &$p): void {
    $p['specs']     = is_string($p['specs'])     ? (json_decode($p['specs'],     true) ?: []) : ($p['specs']     ?: []);
    $p['packaging'] = is_string($p['packaging']) ? (json_decode($p['packaging'], true) ?: []) : ($p['packaging'] ?: []);
    $p['active']    = (bool)$p['active'];
}

// ── Route parsing ────────────────────────────────────────────
$route = trim($_GET['route'] ?? '', '/');
if ($route === '') {
    // Try PATH_INFO
    $pi = $_SERVER['PATH_INFO'] ?? '';
    if ($pi) $route = trim($pi, '/');
}
$parts = $route ? array_values(array_filter(explode('/', $route))) : [];
$r0 = $parts[0] ?? '';
$r1 = $parts[1] ?? '';
$r2 = $parts[2] ?? '';

// ════════════════════════════════════════════════
//  HEALTH
// ════════════════════════════════════════════════
if ($r0 === 'health' || $route === '') {
    $dbOk = false; $dbMsg = 'Not tested';
    try {
        $db = getDB();
        $db->query('SELECT 1');
        $np = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $ni = (int)$db->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
        $dbOk  = true;
        $dbMsg = "Connected — {$np} products, {$ni} inquiries";
    } catch(Exception $e) { $dbMsg = 'ERROR: '.$e->getMessage(); }
    respond(['status'=>$dbOk?'ok':'db_error','message'=>'Sneha Enterprises API — PHP + MySQL','version'=>'2.1','php'=>phpversion(),'database'=>$dbMsg,'timestamp'=>date('Y-m-d H:i:s')]);
}

// ════════════════════════════════════════════════
//  AUTH
// ════════════════════════════════════════════════
if ($r0 === 'auth') {
    if ($r1==='login' && M()==='POST') {
        $d=$b=body(); $u=trim($d['username']??''); $p=$d['password']??'';
        if(!$u||!$p) apiErr('Username and password required');
        $db=getDB(); $st=$db->prepare('SELECT * FROM admins WHERE username=? LIMIT 1'); $st->execute([$u]);
        $a=$st->fetch(PDO::FETCH_ASSOC);
        if(!$a||$a['password']!==hashPw($p)) apiErr('Invalid username or password',401);
        $tok=genToken(); $exp=date('Y-m-d H:i:s',time()+TOKEN_EXPIRY);
        $db->prepare('INSERT INTO sessions(token,admin_id,username,expires_at)VALUES(?,?,?,?)')->execute([$tok,$a['id'],$a['username'],$exp]);
        $db->prepare('UPDATE admins SET last_login=NOW() WHERE id=?')->execute([$a['id']]);
        $db->exec("DELETE FROM sessions WHERE expires_at < NOW()");
        respond(['token'=>$tok,'username'=>$a['username'],'role'=>$a['role'],'expires_at'=>$exp,'message'=>'Login successful']);
    }
    if ($r1==='logout' && M()==='POST') {
        $t=trim(str_replace('Bearer','', $_SERVER['HTTP_AUTHORIZATION']??''));
        if($t) getDB()->prepare('DELETE FROM sessions WHERE token=?')->execute([$t]);
        respond(['message'=>'Logged out']);
    }
    if ($r1==='me' && M()==='GET') {
        $s=auth(); $st=getDB()->prepare('SELECT id,username,email,role,last_login,created_at FROM admins WHERE username=?'); $st->execute([$s['username']]);
        $a=$st->fetch(PDO::FETCH_ASSOC); if(!$a) apiErr('Not found',404); respond($a);
    }
    if ($r1==='change-password' && M()==='POST') {
        $s=auth(); $d=body(); $op=$d['old_password']??''; $np=$d['new_password']??'';
        if(strlen($np)<6) apiErr('Min 6 characters');
        $db=getDB(); $st=$db->prepare('SELECT password FROM admins WHERE username=?'); $st->execute([$s['username']]);
        $row=$st->fetch(PDO::FETCH_ASSOC); if(!$row||$row['password']!==hashPw($op)) apiErr('Old password wrong',401);
        $db->prepare('UPDATE admins SET password=? WHERE username=?')->execute([hashPw($np),$s['username']]);
        respond(['message'=>'Password changed']);
    }
    apiErr('Auth endpoint not found',404);
}

// ════════════════════════════════════════════════
//  PRODUCTS
// ════════════════════════════════════════════════
if ($r0 === 'products') {
    $pid = $r1 ?: null; $tog = ($r2==='toggle');

    if (M()==='GET' && !$pid) {
        $db=getDB(); $w=[]; $p=[];
        if(!empty($_GET['category'])){$w[]='category=?';$p[]=$_GET['category'];}
        if(!empty($_GET['active'])){$w[]='active=1';}
        if(!empty($_GET['search'])){$lk='%'.$_GET['search'].'%';$w[]='(name LIKE ? OR origin LIKE ? OR description LIKE ?)';$p[]=$lk;$p[]=$lk;$p[]=$lk;}
        $sql='SELECT * FROM products'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY sort_order ASC, created_at DESC';
        $st=$db->prepare($sql); $st->execute($p); $rows=$st->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as &$r) decodeProduct($r);
        respond(['products'=>$rows,'total'=>count($rows)]);
    }
    if (M()==='GET' && $pid && !$tog) {
        $st=getDB()->prepare('SELECT * FROM products WHERE id=?'); $st->execute([$pid]);
        $r=$st->fetch(PDO::FETCH_ASSOC); if(!$r) apiErr('Not found',404);
        decodeProduct($r); respond($r);
    }
    if (M()==='POST' && !$pid) {
        auth(); $d=body();
        if(empty($d['name'])||empty($d['category'])) apiErr('name and category required');
        $db=getDB();
        $id=$d['id']??strtolower(preg_replace('/[^a-z0-9]+/i','-',$d['name'])).'-'.substr(bin2hex(random_bytes(3)),0,6);
        $chk=$db->prepare('SELECT 1 FROM products WHERE id=?'); $chk->execute([$id]);
        if($chk->fetch()) $id.='-'.substr(bin2hex(random_bytes(2)),0,4);
        $sort=(int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn()+1;
        $db->prepare('INSERT INTO products(id,name,category,emoji,image_url,badge,bg,origin,description,specs,packaging,min_order,active,sort_order)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            $id,$d['name'],$d['category'],$d['emoji']??'🌾',$d['image_url']??'',$d['badge']??'',$d['bg']??'linear-gradient(135deg,#FFF8E1,#FFF0C0)',
            $d['origin']??'',$d['description']??'',
            json_encode($d['specs']??[],JSON_UNESCAPED_UNICODE),json_encode($d['packaging']??[],JSON_UNESCAPED_UNICODE),
            $d['minOrder']??'On Request',isset($d['active'])?($d['active']?1:0):1,$sort]);
        respond(['id'=>$id,'message'=>'Product created'],201);
    }
    if (M()==='PUT' && $pid && !$tog) {
        auth(); $db=getDB();
        $chk=$db->prepare('SELECT 1 FROM products WHERE id=?'); $chk->execute([$pid]);
        if(!$chk->fetch()) apiErr('Not found',404);
        $d=body();
        $db->prepare('UPDATE products SET name=?,category=?,emoji=?,image_url=?,badge=?,bg=?,origin=?,description=?,specs=?,packaging=?,min_order=?,active=?,updated_at=NOW() WHERE id=?')->execute([
            $d['name'],$d['category'],$d['emoji']??'🌾',$d['image_url']??'',$d['badge']??'',$d['bg']??'',$d['origin']??'',$d['description']??'',
            json_encode($d['specs']??[],JSON_UNESCAPED_UNICODE),json_encode($d['packaging']??[],JSON_UNESCAPED_UNICODE),
            $d['minOrder']??'On Request',isset($d['active'])?($d['active']?1:0):1,$pid]);
        respond(['message'=>'Updated','id'=>$pid]);
    }
    if (M()==='PATCH' && $pid && $tog) {
        auth(); $db=getDB();
        $chk=$db->prepare('SELECT active FROM products WHERE id=?'); $chk->execute([$pid]);
        $row=$chk->fetch(PDO::FETCH_ASSOC); if(!$row) apiErr('Not found',404);
        $new=$row['active']?0:1;
        $db->prepare('UPDATE products SET active=?,updated_at=NOW() WHERE id=?')->execute([$new,$pid]);
        respond(['active'=>(bool)$new,'id'=>$pid]);
    }
    if (M()==='DELETE' && $pid) {
        auth(); $db=getDB();
        $chk=$db->prepare('SELECT 1 FROM products WHERE id=?'); $chk->execute([$pid]);
        if(!$chk->fetch()) apiErr('Not found',404);
        $db->prepare('DELETE FROM products WHERE id=?')->execute([$pid]);
        respond(['message'=>'Deleted','id'=>$pid]);
    }
    // ── POST /api/products/upload-image ─────────────────────
    if (M()==='POST' && $r1==='upload-image') {
        auth();
        $imgDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/product_images/';
        $imgUrl = 'https://snehaenterprises.store/assets/product_images/';
        if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
        if (empty($_FILES['image'])) respond(['error'=>'No image file. Use field name "image"'], 400);
        $f = $_FILES['image'];
        if ($f['error'] !== UPLOAD_ERR_OK)   respond(['error'=>'Upload error: '.$f['error']], 400);
        if ($f['size'] > 5*1024*1024)         respond(['error'=>'Image exceeds 5 MB'], 400);
        $mime = mime_content_type($f['tmp_name']);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif']))
            respond(['error'=>'Invalid image type: '.$mime], 400);
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) ?: 'jpg';
        $fn  = 'prod_'.date('Ymd_His').'_'.substr(bin2hex(random_bytes(3)),0,6).'.'.$ext;
        if (!move_uploaded_file($f['tmp_name'], $imgDir.$fn))
            respond(['error'=>'Failed to save file. Check folder permissions.'], 500);
        respond(['url'=>$imgUrl.$fn, 'filename'=>$fn, 'message'=>'Image uploaded successfully'], 201);
    }

    apiErr('Products endpoint not found',404);
}

// ════════════════════════════════════════════════
//  INQUIRIES
// ════════════════════════════════════════════════
if ($r0 === 'inquiries') {
    $iid=$r1&&$r1!=='stats'?$r1:null; $isSt=($r1==='stats');

    if (M()==='POST' && !$iid) {
        $d=body(); $n=trim($d['name']??''); $e=trim($d['email']??'');
        if(!$n||!$e) apiErr('Name and email required');
        $db=getDB();
        $id='INQ-'.date('Ymd-His').'-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
        $now=date('Y-m-d H:i:s');
        $db->prepare("INSERT INTO inquiries(id,name,email,company,country,phone,product_id,product_name,quantity,incoterm,message,source,status,created_at,updated_at)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'new',?,?)")->execute([
            $id,$n,strtolower($e),trim($d['company']??''),trim($d['country']??''),trim($d['phone']??''),
            trim($d['productId']??''),trim($d['productName']??''),trim($d['qty']??''),
            trim($d['incoterm']??'FOB'),trim($d['message']??''),trim($d['source']??'website'),$now,$now]);
        $st=$db->prepare('SELECT * FROM inquiries WHERE id=?'); $st->execute([$id]);
        $inq=$st->fetch(PDO::FETCH_ASSOC);
        try { sendInquiryEmails($inq); } catch(\Throwable $e2){ error_log('[EMAIL] '.$e2->getMessage()); }
        respond(['id'=>$id,'message'=>'Inquiry submitted successfully'],201);
    }
    if (M()==='GET' && $isSt) {
        auth(); $db=getDB();
        respond([
            'total'    =>(int)$db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn(),
            'new'      =>(int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn(),
            'replied'  =>(int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status='replied'")->fetchColumn(),
            'today'    =>(int)$db->query("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
            'byProduct'=>$db->query("SELECT product_name,COUNT(*) as count FROM inquiries GROUP BY product_name ORDER BY count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC),
            'byStatus' =>$db->query("SELECT status,COUNT(*) as count FROM inquiries GROUP BY status")->fetchAll(PDO::FETCH_ASSOC),
            'recent7d' =>$db->query("SELECT DATE(created_at) as day,COUNT(*) as count FROM inquiries WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY day ORDER BY day")->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }
    if (M()==='GET' && !$iid && !$isSt) {
        auth(); $db=getDB();
        $w=[]; $p=[];
        if(!empty($_GET['status'])){$w[]='status=?';$p[]=$_GET['status'];}
        if(!empty($_GET['product'])){$w[]='product_id=?';$p[]=$_GET['product'];}
        if(!empty($_GET['search'])){$lk='%'.$_GET['search'].'%';$w[]='(name LIKE ? OR email LIKE ? OR company LIKE ? OR product_name LIKE ?)';$p[]=$lk;$p[]=$lk;$p[]=$lk;$p[]=$lk;}
        $wSQL=$w?'WHERE '.implode(' AND ',$w):'';
        $pg=max(1,(int)($_GET['page']??1)); $per=min(100,(int)($_GET['per_page']??20)); $off=($pg-1)*$per;
        $cs=$db->prepare("SELECT COUNT(*) FROM inquiries $wSQL"); $cs->execute($p); $tot=(int)$cs->fetchColumn();
        $st=$db->prepare("SELECT * FROM inquiries $wSQL ORDER BY created_at DESC LIMIT $per OFFSET $off"); $st->execute($p);
        respond(['inquiries'=>$st->fetchAll(PDO::FETCH_ASSOC),'total'=>$tot,'page'=>$pg,'per_page'=>$per,'pages'=>max(1,(int)ceil($tot/$per))]);
    }
    if (M()==='GET' && $iid) {
        auth(); $db=getDB();
        $st=$db->prepare('SELECT * FROM inquiries WHERE id=?'); $st->execute([$iid]);
        $inq=$st->fetch(PDO::FETCH_ASSOC); if(!$inq) apiErr('Not found',404);
        if($inq['status']==='new'){$db->prepare("UPDATE inquiries SET status='read',updated_at=NOW() WHERE id=?")->execute([$iid]);$inq['status']='read';}
        respond($inq);
    }
    if (M()==='PATCH' && $iid) {
        auth(); $db=getDB();
        $chk=$db->prepare('SELECT 1 FROM inquiries WHERE id=?'); $chk->execute([$iid]);
        if(!$chk->fetch()) apiErr('Not found',404);
        $d=body(); $sets=[]; $vals=[];
        foreach(['status','notes'] as $f){if(array_key_exists($f,$d)){$sets[]="$f=?";$vals[]=$d[$f];}}
        if(!$sets) apiErr('Nothing to update');
        $sets[]='updated_at=NOW()';
        $db->prepare('UPDATE inquiries SET '.implode(',',$sets).' WHERE id=?')->execute(array_merge($vals,[$iid]));
        respond(['message'=>'Updated','id'=>$iid]);
    }
    if (M()==='DELETE' && $iid) {
        auth(); $db=getDB();
        $chk=$db->prepare('SELECT 1 FROM inquiries WHERE id=?'); $chk->execute([$iid]);
        if(!$chk->fetch()) apiErr('Not found',404);
        $db->prepare('DELETE FROM inquiries WHERE id=?')->execute([$iid]);
        respond(['message'=>'Deleted','id'=>$iid]);
    }
    if (M()==='DELETE' && !$iid) {
        auth(); getDB()->exec('DELETE FROM inquiries'); respond(['message'=>'All deleted']);
    }
    apiErr('Inquiries endpoint not found',404);
}

// ════════════════════════════════════════════════
//  DASHBOARD
// ════════════════════════════════════════════════
if ($r0 === 'dashboard') {
    auth(); $db=getDB();
    respond([
        'inquiries'=>['total'=>(int)$db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn(),'new'=>(int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn(),'replied'=>(int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status='replied'")->fetchColumn(),'today'=>(int)$db->query("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at)=CURDATE()")->fetchColumn()],
        'products' =>['total'=>(int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn(),'active'=>(int)$db->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn()],
        'recent_inquiries'=>$db->query("SELECT id,name,email,company,product_name,country,status,created_at FROM inquiries ORDER BY created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC),
        'top_products'    =>$db->query("SELECT product_name,COUNT(*) as count FROM inquiries GROUP BY product_name ORDER BY count DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC),
        'recent_7d'       =>$db->query("SELECT DATE(created_at) as day,COUNT(*) as count FROM inquiries WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY day ORDER BY day")->fetchAll(PDO::FETCH_ASSOC),
    ]);
}

// ════════════════════════════════════════════════
//  GALLERY
// ════════════════════════════════════════════════
if ($r0 === 'gallery') {
    require_once __DIR__ . '/gallery.php';
}

// FALLBACK
respond(['error'=>'Endpoint not found','tip'=>'Try: api/index.php?route=health','route_received'=>$route],404);