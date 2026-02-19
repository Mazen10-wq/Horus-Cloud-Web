<?php
require_once __DIR__.'/config.php';
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonOut(['success'=>false,'message'=>'Method not allowed'], 405); }

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { jsonOut(['success'=>false,'message'=>'Invalid request'], 400); }

// Validate
$name  = sanitize($data['name']  ?? '', 100);
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$pass  = $data['password'] ?? '';
$inst  = sanitize($data['institution'] ?? '', 255);
$phone = sanitize($data['phone'] ?? '', 20);

if (!$name || strlen($name) < 2)        jsonOut(['success'=>false,'message'=>'Name must be at least 2 characters']);
if (!$email)                            jsonOut(['success'=>false,'message'=>'Invalid email address']);
if (strlen($pass) < 8)                  jsonOut(['success'=>false,'message'=>'Password must be at least 8 characters']);
if (!preg_match('/[A-Z]/', $pass))      jsonOut(['success'=>false,'message'=>'Password must contain an uppercase letter']);
if (!preg_match('/[0-9]/', $pass))      jsonOut(['success'=>false,'message'=>'Password must contain a number']);

try {
    $db = db();
    // Check duplicate
    $check = $db->prepare("SELECT id FROM researchers WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) { jsonOut(['success'=>false,'message'=>'Email already registered']); }

    $hash  = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
    $token = bin2hex(random_bytes(32));

    $stmt = $db->prepare("INSERT INTO researchers (name,email,password,institution,phone,verify_token,status)
                          VALUES (?,?,?,?,?,?,'pending_verify')");
    $stmt->execute([$name, $email, $hash, $inst, $phone, $token]);

    // Send verification email
    $link = SITE_URL."/verify.php?token=$token";
    $body = "Dear $name,\n\nWelcome to Horus Cloud!\nPlease verify your email:\n$link\n\nThe link expires in 24 hours.\n\nHorus Cloud Team";
    sendMail($email, 'Verify your Horus Cloud account', $body);

    jsonOut(['success'=>true,'message'=>'Account created! Please check your email to verify.']);
} catch (PDOException $e) {
    error_log('[HorusCloud] Register: '.$e->getMessage());
    jsonOut(['success'=>false,'message'=>'Server error. Please try again.'], 500);
}
