<?php
require_once __DIR__.'/config.php';
session_start();
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['success'=>false,'message'=>'Method not allowed'], 405); }

// Rate limit: 10 attempts / 15 min
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];
$_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], fn($t) => time()-$t < 900);
if (count($_SESSION['login_attempts']) >= 10) {
    jsonOut(['success'=>false,'message'=>'Too many attempts. Try again in 15 minutes.'], 429);
}

$data  = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$pass  = $data['password'] ?? '';

if (!$email || !$pass) { jsonOut(['success'=>false,'message'=>'Email and password required']); }

try {
    $db   = db();
    $stmt = $db->prepare("SELECT id,name,password,status FROM researchers WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        $_SESSION['login_attempts'][] = time();
        jsonOut(['success'=>false,'message'=>'Invalid email or password']);
    }
    if ($user['status'] === 'pending_verify') {
        jsonOut(['success'=>false,'message'=>'Please verify your email first.']);
    }
    if ($user['status'] === 'suspended') {
        jsonOut(['success'=>false,'message'=>'Account suspended. Contact support.']);
    }

    // Regenerate session for security
    session_regenerate_id(true);
    $_SESSION['researcher_id']   = $user['id'];
    $_SESSION['researcher_name'] = $user['name'];
    $_SESSION['login_attempts']  = [];

    $db->prepare("UPDATE researchers SET last_login=NOW() WHERE id=?")->execute([$user['id']]);

    jsonOut(['success'=>true,'redirect'=>SITE_URL.'/researcher/dashboard.php']);
} catch (PDOException $e) {
    error_log('[HorusCloud] Login: '.$e->getMessage());
    jsonOut(['success'=>false,'message'=>'Server error.'], 500);
}
