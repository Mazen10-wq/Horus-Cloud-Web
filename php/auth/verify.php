<?php
require_once __DIR__.'/config.php';
$token = trim($_GET['token'] ?? '');
if (!$token) { header('Location: '.SITE_URL.'/login.php?error=invalid_token'); exit; }

try {
    $db   = db();
    $stmt = $db->prepare("SELECT id FROM researchers WHERE verify_token=? AND status='pending_verify'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) { header('Location: '.SITE_URL.'/login.php?error=invalid_token'); exit; }

    $db->prepare("UPDATE researchers SET status='active', verify_token=NULL WHERE id=?")->execute([$user['id']]);
    header('Location: '.SITE_URL.'/login.php?verified=1');
} catch (PDOException $e) {
    error_log('[HorusCloud] Verify: '.$e->getMessage());
    header('Location: '.SITE_URL.'/login.php?error=server');
}
exit;
