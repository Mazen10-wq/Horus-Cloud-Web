<?php
require_once __DIR__.'/../php/auth/config.php';
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: '.ADMIN_URL.'/login.php'); exit; }

$db = db();

// Stats
$totalRequests  = $db->query("SELECT COUNT(*) FROM access_requests")->fetchColumn();
$pendingCount   = $db->query("SELECT COUNT(*) FROM access_requests WHERE status='pending'")->fetchColumn();
$totalResearchers = $db->query("SELECT COUNT(*) FROM researchers WHERE status='active'")->fetchColumn();
$approvedCount  = $db->query("SELECT COUNT(*) FROM access_requests WHERE status='approved'")->fetchColumn();

// Recent requests
$recentReqs = $db->query("SELECT ar.*, r.name, r.email, r.institution FROM access_requests ar
                           JOIN researchers r ON r.id=ar.researcher_id
                           ORDER BY ar.created_at DESC LIMIT 8")->fetchAll();

// Monthly chart data (last 6 months)
$chartData = $db->query("SELECT DATE_FORMAT(created_at,'%b') as month, COUNT(*) as count
                          FROM access_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                          GROUP BY MONTH(created_at), DATE_FORMAT(created_at,'%b')
                          ORDER BY created_at")->fetchAll();

$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Horus Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/auth.css">
    <style>
        body{font-family:'Inter',sans-serif;background:#f1f5f9;margin:0;}
        .admin-layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh;}
        .admin-sidebar{background:#0f172a;display:flex;flex-direction:column;}
        .admin-brand{display:flex;align-items:center;gap:10px;padding:24px 20px;color:#fff;font-size:1.1rem;font-weight:800;border-bottom:1px solid rgba(255,255,255,.08);text-decoration:none;}
        .admin-brand svg{color:#06b6d4;}
        .admin-brand small{display:block;font-size:.65rem;font-weight:500;color:rgba(255,255,255,.4);letter-spacing:.06em;margin-top:1px;}
        .admin-nav{padding:16px 10px;flex:1;}
        .admin-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:rgba(255,255,255,.65);font-size:.875rem;font-weight:500;text-decoration:none;transition:all .18s;margin-bottom:2px;}
        .admin-nav a:hover,.admin-nav a.active{background:rgba(255,255,255,.08);color:#fff;}
        .admin-nav a.active{background:rgba(37,99,235,.25);color:#93c5fd;}
        .admin-nav-sec{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.25);padding:16px 12px 6px;}
        .admin-nav .badge-count{margin-left:auto;background:#ef4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:.7rem;}
        .admin-footer{padding:16px 10px;border-top:1px solid rgba(255,255,255,.08);}
        .admin-footer a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:rgba(255,255,255,.5);font-size:.875rem;text-decoration:none;transition:all .18s;}
        .admin-footer a:hover{background:rgba(239,68,68,.15);color:#fca5a5;}
        .admin-main{display:flex;flex-direction:column;overflow:hidden;}
        .admin-topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
        .admin-topbar h1{font-size:1.15rem;font-weight:700;color:#0f172a;}
        .admin-topbar-right{display:flex;align-items:center;gap:12px;font-size:.875rem;color:#64748b;}
        .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;font-weight:700;font-size:.9rem;display:flex;align-items:center;justify-content:center;}
        .admin-body{padding:28px;overflow-y:auto;flex:1;}
        .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;}
        .stat-box{background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;display:flex;gap:14px;align-items:center;}
        .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .ic-blue{background:rgba(37,99,235,.1);color:#2563eb;}
        .ic-green{background:rgba(16,185,129,.1);color:#10b981;}
        .ic-orange{background:rgba(245,158,11,.1);color:#f59e0b;}
        .ic-purple{background:rgba(124,58,237,.1);color:#7c3aed;}
        .stat-num{font-size:1.8rem;font-weight:900;color:#0f172a;line-height:1;}
        .stat-lbl{font-size:.78rem;color:#64748b;font-weight:500;margin-top:3px;}
        .panel{background:#fff;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:24px;overflow:hidden;}
        .panel-head{padding:18px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;}
        .panel-head h3{font-size:.95rem;font-weight:700;color:#0f172a;}
        .panel-head a{font-size:.85rem;color:#2563eb;font-weight:600;text-decoration:none;}
        .tbl{width:100%;border-collapse:collapse;font-size:.85rem;}
        .tbl th{text-align:left;padding:10px 16px;color:#64748b;font-weight:600;font-size:.76rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #f1f5f9;}
        .tbl td{padding:13px 16px;border-bottom:1px solid #f8fafc;color:#374151;}
        .tbl tr:last-child td{border-bottom:none;}
        .tbl tr:hover td{background:#fafafa;}
        .action-btns{display:flex;gap:6px;}
        .btn-approve{padding:5px 12px;background:#d1fae5;color:#065f46;border:none;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;transition:all .2s;}
        .btn-approve:hover{background:#10b981;color:#fff;}
        .btn-reject{padding:5px 12px;background:#fee2e2;color:#991b1b;border:none;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;transition:all .2s;}
        .btn-reject:hover{background:#ef4444;color:#fff;}
        .btn-msg{padding:5px 12px;background:#ede9fe;color:#5b21b6;border:none;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;transition:all .2s;}
        .btn-msg:hover{background:#7c3aed;color:#fff;}
        .two-col{display:grid;grid-template-columns:1.5fr 1fr;gap:24px;}
        .chart-wrap{padding:22px;}
        .chart-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
        .chart-bar-label{width:36px;font-size:.75rem;color:#64748b;font-weight:600;}
        .chart-bar-bg{flex:1;height:28px;background:#f1f5f9;border-radius:6px;overflow:hidden;}
        .chart-bar-fill{height:100%;background:linear-gradient(90deg,#2563eb,#06b6d4);border-radius:6px;transition:width .5s;}
        .chart-bar-val{font-size:.78rem;font-weight:700;color:#374151;width:24px;}
        /* Modal */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;}
        .modal-overlay.open{display:flex;}
        .modal{background:#fff;border-radius:16px;padding:28px;width:460px;max-width:90vw;box-shadow:0 24px 64px rgba(0,0,0,.2);}
        .modal h3{font-size:1.1rem;font-weight:700;margin-bottom:16px;color:#0f172a;}
        .modal textarea{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.9rem;resize:vertical;min-height:100px;outline:none;}
        .modal textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);}
        .modal-btns{display:flex;gap:10px;margin-top:16px;justify-content:flex-end;}
        .modal-cancel{padding:9px 18px;background:#f1f5f9;border:none;border-radius:8px;font-family:inherit;font-weight:600;cursor:pointer;}
        .modal-send{padding:9px 18px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-family:inherit;font-weight:700;cursor:pointer;}
    </style>
</head>
<body>
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="index.php" class="admin-brand">
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M4 2h16v2H4V2zm0 18h16v2H4v-2zM2 6h20v2H2V6zm0 10h20v2H2v-2zm0-5h20v2H2v-2z"/><rect x="6" y="9" width="4" height="4"/><rect x="14" y="9" width="4" height="4"/></svg>
            <div>Horus Cloud<small>ADMIN PANEL</small></div>
        </a>
        <nav class="admin-nav">
            <div class="admin-nav-sec">Overview</div>
            <a href="index.php" class="active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <div class="admin-nav-sec">Management</div>
            <a href="requests.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Requests
                <?php if ($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?>
            </a>
            <a href="researchers.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Researchers
            </a>
            <a href="resources.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="5" rx="1"/><rect x="2" y="10" width="20" height="5" rx="1"/><rect x="2" y="17" width="20" height="5" rx="1"/></svg>
                Resources
            </a>
            <a href="messages.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Messages
            </a>
        </nav>
        <div class="admin-footer">
            <a href="../php/auth/logout.php?admin=1">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Sign Out
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="admin-main">
        <div class="admin-topbar">
            <h1>Dashboard</h1>
            <div class="admin-topbar-right">
                <span><?= date('l, M d Y') ?></span>
                <div class="avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
            </div>
        </div>

        <div class="admin-body">

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-icon ic-blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                    <div><div class="stat-num"><?= $totalRequests ?></div><div class="stat-lbl">Total Requests</div></div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon ic-orange"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                    <div><div class="stat-num"><?= $pendingCount ?></div><div class="stat-lbl">Pending Review</div></div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon ic-green"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div><div class="stat-num"><?= $approvedCount ?></div><div class="stat-lbl">Approved</div></div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon ic-purple"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div><div class="stat-num"><?= $totalResearchers ?></div><div class="stat-lbl">Active Researchers</div></div>
                </div>
            </div>

            <div class="two-col">
                <!-- Recent Requests table -->
                <div class="panel">
                    <div class="panel-head">
                        <h3>📋 Recent Requests</h3>
                        <a href="requests.php">View all →</a>
                    </div>
                    <table class="tbl">
                        <thead><tr><th>Researcher</th><th>Service</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach($recentReqs as $r): ?>
                        <tr id="req-<?= $r['id'] ?>">
                            <td>
                                <strong style="font-size:.875rem"><?= htmlspecialchars($r['name']) ?></strong>
                                <div style="font-size:.76rem;color:#94a3b8"><?= htmlspecialchars($r['institution'] ?? '') ?></div>
                            </td>
                            <td style="text-transform:capitalize;font-weight:600"><?= htmlspecialchars($r['service_type']) ?></td>
                            <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($r['status']==='pending'): ?>
                                    <button class="btn-approve" onclick="updateStatus(<?= $r['id'] ?>,'approved')">✓ Approve</button>
                                    <button class="btn-reject"  onclick="updateStatus(<?= $r['id'] ?>,'rejected')">✗ Reject</button>
                                    <?php endif; ?>
                                    <button class="btn-msg" onclick="openMsg(<?= $r['id'] ?>,<?= $r['researcher_id'] ?>,'<?= addslashes($r['name']) ?>')">✉</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Chart -->
                <div class="panel">
                    <div class="panel-head"><h3>📊 Requests (Last 6 Months)</h3></div>
                    <div class="chart-wrap">
                        <?php
                        $maxVal = max(1, max(array_column($chartData,'count') ?: [1]));
                        foreach ($chartData as $row):
                            $pct = round(($row['count']/$maxVal)*100);
                        ?>
                        <div class="chart-bar-row">
                            <div class="chart-bar-label"><?= $row['month'] ?></div>
                            <div class="chart-bar-bg"><div class="chart-bar-fill" style="width:<?= $pct ?>%"></div></div>
                            <div class="chart-bar-val"><?= $row['count'] ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (!$chartData): ?>
                        <div style="text-align:center;color:#94a3b8;padding:32px">No data yet</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal-overlay" id="msgModal">
    <div class="modal">
        <h3>✉️ Send Message to <span id="msgRecipient"></span></h3>
        <input type="hidden" id="msgResearcherId">
        <input type="hidden" id="msgRequestId">
        <div style="margin-bottom:12px">
            <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">Subject</label>
            <input type="text" id="msgSubject" placeholder="Re: Your access request" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.9rem;outline:none;">
        </div>
        <textarea id="msgBody" placeholder="Write your message here..."></textarea>
        <div class="modal-btns">
            <button class="modal-cancel" onclick="closeMsg()">Cancel</button>
            <button class="modal-send" onclick="sendMsg()">Send Message</button>
        </div>
    </div>
</div>

<script>
function updateStatus(reqId, status) {
    if (!confirm('Set request #' + reqId + ' to ' + status + '?')) return;
    fetch('php/update_request.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id: reqId, status})
    }).then(r => r.json()).then(d => {
        if (d.success) {
            const row = document.getElementById('req-' + reqId);
            row.querySelector('.badge').className = 'badge badge-' + status;
            row.querySelector('.badge').textContent = status;
            row.querySelectorAll('.btn-approve,.btn-reject').forEach(b => b.remove());
        } else { alert('Error: ' + d.message); }
    }).catch(() => alert('Connection error'));
}

function openMsg(reqId, researcherId, name) {
    document.getElementById('msgRecipient').textContent = name;
    document.getElementById('msgResearcherId').value = researcherId;
    document.getElementById('msgRequestId').value = reqId;
    document.getElementById('msgBody').value = '';
    document.getElementById('msgSubject').value = 'Re: Your Access Request #' + reqId;
    document.getElementById('msgModal').classList.add('open');
}
function closeMsg() { document.getElementById('msgModal').classList.remove('open'); }

function sendMsg() {
    const body = document.getElementById('msgBody').value.trim();
    const subject = document.getElementById('msgSubject').value.trim();
    if (!body) { alert('Please write a message.'); return; }
    fetch('php/send_message.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            researcher_id: document.getElementById('msgResearcherId').value,
            request_id:    document.getElementById('msgRequestId').value,
            subject, body
        })
    }).then(r=>r.json()).then(d=>{
        if (d.success) { closeMsg(); alert('✅ Message sent!'); }
        else { alert('Error: '+d.message); }
    }).catch(()=>alert('Connection error'));
}

// Close modal on overlay click
document.getElementById('msgModal').addEventListener('click', function(e){ if(e.target===this) closeMsg(); });
</script>
</body>
</html>
