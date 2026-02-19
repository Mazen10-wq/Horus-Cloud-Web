<?php
require_once __DIR__.'/../../php/auth/config.php';
session_start();
if (empty($_SESSION['admin_id'])) { jsonOut(['success'=>false,'message'=>'Unauthorized'],401); }
$data = json_decode(file_get_contents('php://input'),true);
$id   = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';
$allowed = ['approved','rejected','processing','pending'];
if (!$id || !in_array($status,$allowed,true)) { jsonOut(['success'=>false,'message'=>'Invalid data']); }
try {
    $db = db();
    $notes = sanitize($data['notes'] ?? '', 1000);
    $db->prepare("UPDATE access_requests SET status=?, admin_notes=?, updated_at=NOW() WHERE id=?")
       ->execute([$status, $notes, $id]);
    $req = $db->prepare("SELECT ar.*, r.email, r.name FROM access_requests ar JOIN researchers r ON r.id=ar.researcher_id WHERE ar.id=?");
    $req->execute([$id]); $req = $req->fetch();
    if ($req) {
        $msg = "Dear {$req['name']},\n\nYour access request #$id has been $status.\n";
        if ($notes) $msg .= "\nAdmin notes: $notes\n";
        $msg .= "\nView your requests: ".SITE_URL."/researcher/dashboard.php\n\nHorus Cloud Team";
        sendMail($req['email'], "Your request has been $status - Horus Cloud", $msg);
    }
    jsonOut(['success'=>true]);
} catch(PDOException $e) { error_log($e->getMessage()); jsonOut(['success'=>false,'message'=>'DB error'],500); }
