<?php
// ============================================================
//  SNEHA ENTERPRISES — API Core Helper
// ============================================================
require_once __DIR__ . '/config.php';

// ── CORS headers (only send if not already sent by index.php) ─
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
}

// ── JSON helpers — only define if index.php hasn't defined them ──
// respond() and body() are already defined in index.php, so we skip them here.
// Only define functions that index.php does NOT have.

// ── Token auth (index.php uses auth(), core.php uses requireAuth()) ──
// These have different names so both are safe.
if (!function_exists('requireAuth')) {
    function requireAuth(): array {
        $auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $auth);
        if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); exit; }
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM sessions WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $session = $stmt->fetch();
        if (!$session) { http_response_code(401); echo json_encode(['error' => 'Invalid or expired token. Please login again.']); exit; }
        return $session;
    }
}

// ── Email ─────────────────────────────────────────────────────
function sendEmail(string $to, string $subject, string $htmlBody): bool {
    if (USE_SMTP) {
        return sendSMTP($to, $subject, $htmlBody);
    }
    return sendEmailFallback($to, $subject, $htmlBody);
}

function sendSMTP(string $to, string $subject, string $html): bool {
    try {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log('[EMAIL] Composer autoload not found. Run: composer require phpmailer/phpmailer');
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
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $html));
        return $mail->send();
    } catch (Exception $e) {
        error_log('[EMAIL SMTP ERROR] ' . $e->getMessage());
        return sendEmailFallback($to, $subject, $html);
    }
}

function sendEmailFallback(string $to, string $subject, string $html): bool {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: " . COMPANY_NAME . " <" . COMPANY_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . COMPANY_EMAIL . "\r\n";
    return @mail($to, $subject, $html, $headers);
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
    $rows = [
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
    ];
    foreach ($rows as [$label, $value]) {
        $adminHtml .= "<tr>
            <td style='padding:10px;border-bottom:1px solid #eee;font-weight:700;color:#1A2E6B;width:140px'>$label</td>
            <td style='padding:10px;border-bottom:1px solid #eee;color:#333'>$value</td>
          </tr>";
    }
    $adminHtml .= '</table>
      <div style="margin-top:20px;text-align:center">
        <a href="https://snehaenterprises.store/admin/login.html"
           style="background:#C8912A;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700">
          View in Admin Panel →
        </a>
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
      <p style="color:#555;line-height:1.7">Thank you for your inquiry about
        <strong>' . htmlspecialchars($inq['product_name'] ?? 'our products') . '</strong>.
        We have received your request and our trade team will review it immediately.
      </p>
      <div style="background:#FFF3E0;border-left:4px solid #C8912A;padding:16px;border-radius:8px;margin:20px 0">
        <strong>Your Reference ID: ' . $inq['id'] . '</strong><br>
        <span style="color:#666;font-size:13px">Please quote this in all future communications.</span>
      </div>
      <p style="color:#555;line-height:1.7">You can expect a detailed response within
        <strong>24 business hours</strong>, including competitive pricing, specifications and sample availability.
      </p>
      <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
      <p style="color:#888;font-size:13px">
        <strong>Sneha Enterprises</strong><br>
        Ward - 04, Basanwara, Alamnagar, Madhepura – 852210, Bihar, India<br>
        📞 +91 76317 11371 | ✉️ ' . COMPANY_EMAIL . '
      </p>
    </div>';
    sendEmail($inq['email'], 'Inquiry Received — Ref: ' . $inq['id'], $buyerHtml);
}