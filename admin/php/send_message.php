<?php
require_once __DIR__.'/../../php/auth/config.php';
session_start();
if (empty($_SESSION['admin_id'])) { jsonOut(['success'=>false,'message'=>'Unauthorized'],401); }
$data = json_decode(file_get_contents('php://input'),true);
$rid  = (int)($data['researcher_id'] ?? 0);
$body = sanitize($data['body'] ?? '', 2000);
$subj = sanitize($data['subject'] ?? 'Message from Admin', 255);
if (!$rid || !$body) { jsonOut(['success'=>false,'message'=>'Missing data']); }
try {
    $db = db();
    $db->prepare("INSERT INTO messages (researcher_id,subject,body,from_admin) VALUES (?,?,?,1)")
       ->execute([$rid, $subj, $body]);
    $r = $db->prepare("SELECT email,name FROM researchers WHERE id=?");
    $r->execute([$rid]); $r = $r->fetch();
    if ($r) {
        $email_body = "Dear {$r['name']},\n\nNew message from Horus Cloud Admin.\n\nSubject: $subj\n\n$body\n\nSign in to reply: ".SITE_URL."/login.php\n\nHorus Cloud Team";
        sendMail($r['email'], $subj, $email_body, MAIL_ADMIN);
    }
    jsonOut(['success'=>true]);
} catch(PDOException $e) { error_log($e->getMessage()); jsonOut(['success'=>false,'message'=>'DB error'],500); }
