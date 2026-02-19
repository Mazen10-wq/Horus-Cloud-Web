<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Horus Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-brand">
        <a href="index.html" class="auth-logo">
            <svg viewBox="0 0 24 24" fill="currentColor" width="32" height="32">
                <path d="M4 2h16v2H4V2zm0 18h16v2H4v-2zM2 6h20v2H2V6zm0 10h20v2H2v-2zm0-5h20v2H2v-2z"/>
                <rect x="6" y="9" width="4" height="4"/><rect x="14" y="9" width="4" height="4"/>
            </svg>
            Horus Cloud
        </a>
    </div>

    <div class="auth-card">
        <!-- Tabs -->
        <div class="auth-tabs">
            <button class="auth-tab active" onclick="switchTab('login')">Sign In</button>
            <button class="auth-tab" onclick="switchTab('register')">Create Account</button>
        </div>

        <!-- Messages -->
        <?php if (!empty($_GET['verified'])): ?>
        <div class="auth-alert success">✅ Email verified! You can now sign in.</div>
        <?php elseif (!empty($_GET['error'])): ?>
        <div class="auth-alert error">❌ <?= htmlspecialchars($_GET['error'] === 'invalid_token' ? 'Invalid or expired verification link.' : 'Something went wrong.') ?></div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <div id="tab-login" class="auth-form-wrap active">
            <h2>Welcome back</h2>
            <p class="auth-sub">Sign in to track your requests & resources</p>

            <div id="loginAlert" class="auth-alert" style="display:none"></div>

            <div class="auth-field">
                <label>Email Address</label>
                <input type="email" id="loginEmail" placeholder="researcher@university.edu" autocomplete="email">
            </div>
            <div class="auth-field">
                <label>Password</label>
                <div class="pass-wrap">
                    <input type="password" id="loginPass" placeholder="••••••••" autocomplete="current-password">
                    <button type="button" class="pass-toggle" onclick="togglePass('loginPass',this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="auth-row">
                <a href="forgot.php" class="auth-link">Forgot password?</a>
            </div>
            <button class="auth-btn" onclick="doLogin()">
                <span>Sign In</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </div>

        <!-- REGISTER FORM -->
        <div id="tab-register" class="auth-form-wrap">
            <h2>Create Account</h2>
            <p class="auth-sub">Join Horus Cloud to access HPC resources</p>

            <div id="registerAlert" class="auth-alert" style="display:none"></div>

            <div class="auth-field">
                <label>Full Name <span class="req">*</span></label>
                <input type="text" id="regName" placeholder="Dr. Ahmed Mohamed">
            </div>
            <div class="auth-field">
                <label>Email Address <span class="req">*</span></label>
                <input type="email" id="regEmail" placeholder="researcher@university.edu">
            </div>
            <div class="auth-field">
                <label>Institution</label>
                <input type="text" id="regInst" placeholder="Fayoum University">
            </div>
            <div class="auth-field">
                <label>Phone</label>
                <input type="tel" id="regPhone" placeholder="+20 10 08945715">
            </div>
            <div class="auth-field">
                <label>Password <span class="req">*</span></label>
                <div class="pass-wrap">
                    <input type="password" id="regPass" placeholder="Min 8 chars, 1 uppercase, 1 number" oninput="checkStrength(this.value)">
                    <button type="button" class="pass-toggle" onclick="togglePass('regPass',this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="strength-bar"><div id="strengthFill"></div></div>
                <small id="strengthText" style="color:#6b7280;font-size:.78rem"></small>
            </div>
            <div class="auth-field">
                <label>Confirm Password <span class="req">*</span></label>
                <input type="password" id="regPass2" placeholder="Repeat password">
            </div>
            <button class="auth-btn" onclick="doRegister()">
                <span>Create Account</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </button>
            <p class="auth-terms">By registering you agree to our <a href="#" class="auth-link">Terms of Service</a></p>
        </div>
    </div>

    <p class="auth-footer">Need help? <a href="mailto:chep@fayoum.edu.eg" class="auth-link">chep@fayoum.edu.eg</a></p>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.auth-form-wrap').forEach(f => f.classList.remove('active'));
    document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

function togglePass(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.style.opacity = input.type === 'text' ? '1' : '0.5';
}

function showAlert(id, type, msg) {
    const el = document.getElementById(id);
    el.className = 'auth-alert ' + type;
    el.textContent = msg;
    el.style.display = 'block';
}

function setLoading(btn, loading) {
    btn.disabled = loading;
    btn.querySelector('span').textContent = loading ? 'Please wait...' : btn._origText;
    if (!btn._origText) btn._origText = btn.querySelector('span').textContent;
}

function checkStrength(pass) {
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (pass.length >= 8) score++;
    if (/[A-Z]/.test(pass)) score++;
    if (/[0-9]/.test(pass)) score++;
    if (/[^A-Za-z0-9]/.test(pass)) score++;
    const labels = ['','Weak','Fair','Good','Strong'];
    const colors = ['','#ef4444','#f59e0b','#3b82f6','#10b981'];
    fill.style.width = (score * 25) + '%';
    fill.style.background = colors[score] || '#e5e7eb';
    text.textContent = labels[score] || '';
    text.style.color = colors[score];
}

async function doLogin() {
    const btn = document.querySelector('#tab-login .auth-btn');
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPass').value;
    if (!email || !pass) { showAlert('loginAlert','error','Please fill in all fields.'); return; }
    btn.disabled = true; btn.querySelector('span').textContent = 'Signing in...';
    try {
        const res  = await fetch('php/auth/login.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({email, password: pass}) });
        const data = await res.json();
        if (data.success) { window.location.href = data.redirect; }
        else { showAlert('loginAlert','error', data.message); }
    } catch(e) { showAlert('loginAlert','error','Connection error. Please try again.'); }
    btn.disabled = false; btn.querySelector('span').textContent = 'Sign In';
}

async function doRegister() {
    const btn   = document.querySelector('#tab-register .auth-btn');
    const name  = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const inst  = document.getElementById('regInst').value.trim();
    const phone = document.getElementById('regPhone').value.trim();
    const pass  = document.getElementById('regPass').value;
    const pass2 = document.getElementById('regPass2').value;
    if (!name||!email||!pass) { showAlert('registerAlert','error','Please fill required fields.'); return; }
    if (pass !== pass2)        { showAlert('registerAlert','error','Passwords do not match.'); return; }
    btn.disabled = true; btn.querySelector('span').textContent = 'Creating account...';
    try {
        const res  = await fetch('php/auth/register.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({name,email,institution:inst,phone,password:pass}) });
        const data = await res.json();
        if (data.success) { showAlert('registerAlert','success', '✅ ' + data.message); }
        else              { showAlert('registerAlert','error', data.message); }
    } catch(e) { showAlert('registerAlert','error','Connection error. Please try again.'); }
    btn.disabled = false; btn.querySelector('span').textContent = 'Create Account';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        if (document.getElementById('tab-login').classList.contains('active')) doLogin();
        else doRegister();
    }
});
</script>
</body>
</html>
