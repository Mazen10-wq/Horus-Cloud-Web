<?php
require_once __DIR__.'/../php/auth/config.php';
session_start();
if (empty($_SESSION['researcher_id'])) { header('Location: '.SITE_URL.'/login.php'); exit; }

$db  = db();
$rid = (int)$_SESSION['researcher_id'];

// Fetch researcher
$user = $db->prepare("SELECT * FROM researchers WHERE id=?");
$user->execute([$rid]); $user = $user->fetch();

// Fetch requests
$reqs = $db->prepare("SELECT * FROM access_requests WHERE researcher_id=? ORDER BY created_at DESC");
$reqs->execute([$rid]); $reqs = $reqs->fetchAll();

// Fetch resources
$resources = $db->prepare("SELECT * FROM resources WHERE researcher_id=? AND status='active' ORDER BY created_at DESC LIMIT 1");
$resources->execute([$rid]); $resource = $resources->fetch();

// Fetch messages
$msgs = $db->prepare("SELECT * FROM messages WHERE researcher_id=? ORDER BY created_at DESC LIMIT 5");
$msgs->execute([$rid]); $msgs = $msgs->fetchAll();

// Unread count
$unread = $db->prepare("SELECT COUNT(*) FROM messages WHERE researcher_id=? AND is_read=0 AND from_admin=1");
$unread->execute([$rid]); $unreadCount = $unread->fetchColumn();

$initials = strtoupper(substr($user['name'],0,1).(strpos($user['name'],' ')!==false ? substr($user['name'],strpos($user['name'],' ')+1,1) : ''));
$pending  = count(array_filter($reqs, fn($r) => $r['status']==='pending'));
$approved = count(array_filter($reqs, fn($r) => $r['status']==='approved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Horus Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body style="background:#f1f5f9; font-family:'Inter',sans-serif;">
<div class="dash-layout">

    <!-- Sidebar -->
    <aside class="dash-sidebar">
        <a href="../index.html" class="dash-sidebar-brand">
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M4 2h16v2H4V2zm0 18h16v2H4v-2zM2 6h20v2H2V6zm0 10h20v2H2v-2zm0-5h20v2H2v-2z"/><rect x="6" y="9" width="4" height="4"/><rect x="14" y="9" width="4" height="4"/></svg>
            Horus Cloud
        </a>
        <nav class="dash-nav">
            <div class="dash-nav-section">Main</div>
            <a href="dashboard.php" class="active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="requests.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                My Requests
            </a>
            <a href="resources.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="5" rx="1"/><rect x="2" y="10" width="20" height="5" rx="1"/><rect x="2" y="17" width="20" height="5" rx="1"/></svg>
                My Resources
            </a>
            <div class="dash-nav-section">Communication</div>
            <a href="messages.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Messages <?= $unreadCount > 0 ? "<span style='background:#2563eb;color:#fff;border-radius:10px;padding:1px 7px;font-size:.7rem;margin-left:auto;'>$unreadCount</span>" : '' ?>
            </a>
            <a href="../index.html#contact">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                New Request
            </a>
        </nav>
        <div class="dash-sidebar-footer">
            <a href="../php/auth/logout.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Sign Out
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="dash-main">
        <div class="dash-topbar">
            <h1>Welcome back, <?= htmlspecialchars(explode(' ',$user['name'])[0]) ?>! 👋</h1>
            <div class="dash-topbar-user">
                <span><?= htmlspecialchars($user['email']) ?></span>
                <div class="dash-avatar"><?= $initials ?></div>
            </div>
        </div>

        <div class="dash-body">
            <!-- Stats -->
            <div class="dash-stats">
                <div class="dash-stat">
                    <div class="dash-stat-icon blue">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div><div class="dash-stat-num"><?= count($reqs) ?></div><div class="dash-stat-lbl">Total Requests</div></div>
                </div>
                <div class="dash-stat">
                    <div class="dash-stat-icon green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div><div class="dash-stat-num"><?= $approved ?></div><div class="dash-stat-lbl">Approved</div></div>
                </div>
                <div class="dash-stat">
                    <div class="dash-stat-icon orange">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div><div class="dash-stat-num"><?= $pending ?></div><div class="dash-stat-lbl">Pending</div></div>
                </div>
            </div>

            <!-- Active Resource -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>🖥️ Active Resources</h3>
                    <?= $resource ? '<span class="badge badge-approved">Active</span>' : '' ?>
                </div>
                <div class="dash-card-body">
                    <?php if ($resource): ?>
                    <div class="resource-grid">
                        <div class="resource-item"><div class="resource-item-label">CPU</div><div class="resource-item-value"><?= htmlspecialchars($resource['cpu_cores'] ?? '—') ?></div></div>
                        <div class="resource-item"><div class="resource-item-label">RAM</div><div class="resource-item-value"><?= htmlspecialchars($resource['ram'] ?? '—') ?></div></div>
                        <div class="resource-item"><div class="resource-item-label">GPU</div><div class="resource-item-value"><?= htmlspecialchars($resource['gpu'] ?? '—') ?></div></div>
                        <div class="resource-item"><div class="resource-item-label">Storage</div><div class="resource-item-value"><?= htmlspecialchars($resource['storage'] ?? '—') ?></div></div>
                        <?php if ($resource['ip_address']): ?>
                        <div class="resource-item"><div class="resource-item-label">IP Address</div><div class="resource-item-value" style="font-family:monospace"><?= htmlspecialchars($resource['ip_address']) ?></div></div>
                        <?php endif; ?>
                        <?php if ($resource['expires_at']): ?>
                        <div class="resource-item"><div class="resource-item-label">Expires</div><div class="resource-item-value"><?= date('M d, Y', strtotime($resource['expires_at'])) ?></div></div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="dash-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="5" rx="1"/><rect x="2" y="10" width="20" height="5" rx="1"/><rect x="2" y="17" width="20" height="5" rx="1"/></svg>
                        <p>No active resources yet.<br>Submit a request to get started.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>📋 Recent Requests</h3>
                    <a href="requests.php" style="font-size:.85rem;color:#2563eb;font-weight:600;text-decoration:none;">View all →</a>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if ($reqs): ?>
                    <table class="dash-table">
                        <thead><tr><th>Service</th><th>Field</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach(array_slice($reqs,0,5) as $r): ?>
                        <tr>
                            <td style="font-weight:600;text-transform:capitalize"><?= htmlspecialchars($r['service_type']) ?></td>
                            <td><?= htmlspecialchars($r['research_field']) ?></td>
                            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                            <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="dash-empty">
                        <p>No requests yet. <a href="../index.html#contact" class="auth-link">Submit your first request →</a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Messages -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>💬 Messages from Admin</h3>
                    <a href="messages.php" style="font-size:.85rem;color:#2563eb;font-weight:600;text-decoration:none;">View all →</a>
                </div>
                <div class="dash-card-body">
                    <?php if ($msgs): ?>
                    <?php foreach($msgs as $msg): ?>
                    <div class="msg-item <?= !$msg['is_read'] ? 'msg-unread' : '' ?>">
                        <div class="msg-avatar">HC</div>
                        <div style="flex:1">
                            <div style="font-weight:700;font-size:.875rem;" class="msg-subject">
                                <?= !$msg['is_read'] ? '<span class="msg-dot"></span>' : '' ?>
                                <?= htmlspecialchars($msg['subject'] ?? 'Message from Admin') ?>
                            </div>
                            <div class="msg-meta"><?= date('M d, Y • H:i', strtotime($msg['created_at'])) ?></div>
                            <div class="msg-body"><?= nl2br(htmlspecialchars(substr($msg['body'],0,120))) ?>...</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="dash-empty"><p>No messages yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
