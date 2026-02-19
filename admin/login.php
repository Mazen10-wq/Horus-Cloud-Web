<?php
require_once __DIR__.'/../php/auth/config.php';
session_start();
if (!empty($_SESSION['admin_id'])) { header('Location: '.ADMIN_URL.'/index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!isset($_SESSION['admin_attempts'])) $_SESSION['admin_attempts'] = [];
    $_SESSION['admin_attempts'] = array_filter($_SESSION['admin_attempts'], fn($t) => time()-$t < 900);
    if (count($_SESSION['admin_attempts']) >= 5) {
        $error = 'Too many attempts. Try again in 15 minutes.';
    } else {
        try {
            $db = db();
            $stmt = $db->prepare("SELECT id,username,password,role FROM admin_users WHERE username=?");
            $stmt->execute([$user]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($pass, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_attempts'] = [];
                $db->prepare("UPDATE admin_users SET last_login=NOW() WHERE id=?")->execute([$admin['id']]);
                header('Location: '.ADMIN_URL.'/index.php'); exit;
            } else {
                $_SESSION['admin_attempts'][] = time();
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) { $error = 'Server error.'; error_log($e->getMessage()); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In - Horus Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/auth.css">
    <style>
        .admin-login-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(124,58,237,.15);color:#7c3aed;border:1px solid rgba(124,58,237,.3);border-radius:20px;padding:4px 12px;font-size:.75rem;font-weight:700;letter-spacing:.04em;margin-bottom:20px;}
    </style>
</head>
<body class="auth-page">
<div class="auth-container" style="max-width:420px">
    <div class="auth-brand">
        <a href="../index.html" class="auth-logo">
            <svg viewBox="0 0 24 24" fill="currentColor" width="32" height="32"><path d="M4 2h16v2H4V2zm0 18h16v2H4v-2zM2 6h20v2H2V6zm0 10h20v2H2v-2zm0-5h20v2H2v-2z"/><rect x="6" y="9" width="4" height="4"/><rect x="14" y="9" width="4" height="4"/></svg>
            Horus Cloud
        </a>
    </div>
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:8px">
            <div class="admin-login-badge">🔒 ADMIN ACCESS ONLY</div>
        </div>
        <h2 style="text-align:center;margin-bottom:6px;font-size:1.4rem;font-weight:800">Admin Sign In</h2>
        <p class="auth-sub" style="text-align:center">Restricted to authorized administrators</p>
        <?php if ($error): ?>
        <div class="auth-alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="auth-field">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="off" placeholder="admin">
            </div>
            <div class="auth-field">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="auth-btn" style="margin-top:8px">
                <span>Sign In to Admin Panel</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </form>
    </div>
    <p class="auth-footer">Issues? Contact <a href="mailto:chep@fayoum.edu.eg" class="auth-link">chep@fayoum.edu.eg</a></p>
</div>
</body>
</html>
