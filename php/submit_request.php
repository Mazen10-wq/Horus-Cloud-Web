<?php
require_once __DIR__.'/auth/config.php';
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    jsonOut(['success'=>false,'message'=>'Method not allowed'], 405);
}

// Rate limit
$now = time();
if (!isset($_SESSION['submit_times'])) $_SESSION['submit_times'] = [];
$_SESSION['submit_times'] = array_filter($_SESSION['submit_times'], fn($t) => $now-$t < 600);
if (count($_SESSION['submit_times']) >= 5) {
    jsonOut(['success'=>false,'message'=>'Too many requests. Try again later.'], 429);
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) { jsonOut(['success'=>false,'message'=>'Invalid request'], 400); }

// Fields
$name         = sanitize($data['name']        ?? '', 100);
$email        = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone        = sanitize($data['phone']       ?? '', 20);
$institution  = sanitize($data['institution'] ?? '', 255);
$serviceType  = in_array($data['service_type'] ?? '', ['hosting','compute']) ? $data['service_type'] : '';
$field        = sanitize($data['field']       ?? '', 100);
$cpu          = sanitize($data['cpu']         ?? '', 50);
$ram          = sanitize($data['ram']         ?? '', 50);
$gpu          = sanitize($data['gpu']         ?? '', 100);
$duration     = sanitize($data['duration']    ?? '', 50);
$description  = sanitize($data['description'] ?? '', 2000);
$ip           = $_SERVER['REMOTE_ADDR'] ?? '';

if (!$name || !$email || !$phone || !$serviceType || !$field || !$description)
    jsonOut(['success'=>false,'message'=>'Missing required fields']);
if (strlen(trim($data['description'])) < 30)
    jsonOut(['success'=>false,'message'=>'Description too short']);

try {
    $db = db();

    // Link to researcher account if logged in
    $researcherId = $_SESSION['researcher_id'] ?? null;

    // If not logged in, try to find by email
    if (!$researcherId) {
        $find = $db->prepare("SELECT id FROM researchers WHERE email=? AND status='active'");
        $find->execute([$email]);
        $found = $find->fetch();
        $researcherId = $found['id'] ?? null;
    }

    // If still no researcher, create a guest entry
    if (!$researcherId) {
        $guestPass = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(32));
        $ins = $db->prepare("INSERT INTO researchers (name,email,phone,institution,password,verify_token,status) VALUES (?,?,?,?,?,'','pending_verify')");
        $ins->execute([$name, $email, $phone, $institution, $guestPass]);
        $researcherId = $db->lastInsertId();
    }

    $stmt = $db->prepare("INSERT INTO access_requests
        (researcher_id,service_type,research_field,cpu_cores,ram,gpu,duration,description,status,ip_address)
        VALUES (?,?,?,?,?,?,?,?,'pending',?)");
    $stmt->execute([$researcherId,$serviceType,$field,$cpu,$ram,$gpu,$duration,$description,$ip]);
    $requestId = $db->lastInsertId();

    $_SESSION['submit_times'][] = $now;

    // Admin notification
    $body = "NEW REQUEST #$requestId\n\nService: $serviceType\nResearcher: $name ($email)\nInstitution: $institution\nField: $field\nCPU: $cpu | RAM: $ram | GPU: $gpu | Duration: $duration\n\nDescription:\n$description\n\nReview: ".ADMIN_URL."/index.php";
    sendMail(MAIL_ADMIN, "New Horus Cloud Request #$requestId - $name", $body, $email);

    // Researcher confirmation
    $confirm = "Dear $name,\n\nYour request #$requestId has been received.\nWe'll review and respond within 2-3 business days.\n\nTrack your request: ".SITE_URL."/login.php\n\nHorus Cloud Team";
    sendMail($email, "Request Received #$requestId - Horus Cloud", $confirm);

    jsonOut(['success'=>true,'message'=>'Request submitted successfully','request_id'=>$requestId]);
} catch(PDOException $e) {
    error_log('[HorusCloud] Submit: '.$e->getMessage());
    jsonOut(['success'=>false,'message'=>'Server error. Please try again.'], 500);
}
