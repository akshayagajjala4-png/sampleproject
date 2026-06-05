<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php"); exit;
}

$success = '';
$error   = '';
$step    = $_SESSION['reg_step'] ?? 'form';

// Admin removed from role_map
$role_map = ['teacher'=>2,'student'=>3,'parent'=>4];
$pre_role = in_array($_GET['role'] ?? '', array_keys($role_map)) ? $_GET['role'] : '';

function isStrongPassword($pw) {
    return strlen($pw) >= 8
        && preg_match('/[A-Z]/', $pw)
        && preg_match('/[0-9]/', $pw)
        && preg_match('/[^A-Za-z0-9]/', $pw);
}

/* ── GO BACK — clear session and return to form ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'go_back') {
    unset($_SESSION['reg_step'], $_SESSION['reg_otp'], $_SESSION['reg_otp_expiry']);
    // Keep reg_pending so form fields can be pre-filled
    $step = 'form';
}

/* ── STEP 1 — Register form ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $role     = trim($_POST['role']     ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\+?[0-9\s\-().]{7,20}$/', $phone) || strlen(preg_replace('/\D/','',$phone)) < 7) {
        $error = 'Please enter a valid phone number (at least 7 digits).';
    } elseif (!isStrongPassword($password)) {
        $error = 'Password must be at least 8 characters and include an uppercase letter, a number, and a symbol.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!array_key_exists($role, $role_map)) {
        $error = 'Invalid role selected.';
    } else {
        $role_id   = $role_map[$role];
        $email_esc = $conn->real_escape_string($email);

        $check = $conn->query(
            "SELECT id FROM users WHERE email='$email_esc' AND role_id=$role_id LIMIT 1");

        if ($check && $check->num_rows > 0) {
            $error = 'An account with this email already exists for ' . ucfirst($role) . '.';
        } else {
            $_SESSION['reg_pending'] = [
                'name'    => $name,  'email'   => $email,
                'phone'   => $phone, 'password'=> $password,
                'role'    => $role,  'role_id' => $role_id,
            ];
            $otp = sendOtpEmail($email, $name);
            if ($otp) {
                $_SESSION['reg_otp']        = $otp;
                $_SESSION['reg_otp_expiry'] = time() + 600;
                $_SESSION['reg_step']       = 'verify';
                $step = 'verify';
            } else {
                $error = 'Failed to send verification email. Please try again.';
            }
        }
    }
    $pre_role = $_POST['role'] ?? '';
}

/* ── STEP 2 — Verify OTP ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify') {
    $entered = trim(implode('', $_POST['otp'] ?? []));

    if (empty($entered) || strlen($entered) !== 6 || !ctype_digit($entered)) {
        $error = 'Please enter the 6-digit code.'; $step = 'verify';
    } elseif (!isset($_SESSION['reg_otp'], $_SESSION['reg_otp_expiry'], $_SESSION['reg_pending'])) {
        $error = 'Session expired. Please start again.';
        unset($_SESSION['reg_step'],$_SESSION['reg_otp'],$_SESSION['reg_otp_expiry'],$_SESSION['reg_pending']);
        $step = 'form';
    } elseif (time() > $_SESSION['reg_otp_expiry']) {
        $error = 'Your verification code has expired. Please register again.';
        unset($_SESSION['reg_step'],$_SESSION['reg_otp'],$_SESSION['reg_otp_expiry'],$_SESSION['reg_pending']);
        $step = 'form';
    } elseif ($entered !== $_SESSION['reg_otp']) {
        $error = 'Incorrect code. Please try again.'; $step = 'verify';
    } else {
        $p      = $_SESSION['reg_pending'];
        $hashed = password_hash($p['password'], PASSWORD_DEFAULT);
        $name_e = $conn->real_escape_string($p['name']);
        $email_e= $conn->real_escape_string($p['email']);
        $phone_e= $conn->real_escape_string($p['phone']);
        $rid    = (int)$p['role_id'];

        $cols     = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
        $hasPhone = ($cols && $cols->num_rows > 0);

        if ($hasPhone) {
            $insert = $conn->query(
                "INSERT INTO users (name,email,phone,password,role_id,email_verified)
                 VALUES ('$name_e','$email_e','$phone_e','$hashed',$rid,1)");
        } else {
            $insert = $conn->query(
                "INSERT INTO users (name,email,password,role_id)
                 VALUES ('$name_e','$email_e','$hashed',$rid)");
        }

        unset($_SESSION['reg_step'],$_SESSION['reg_otp'],$_SESSION['reg_otp_expiry'],$_SESSION['reg_pending']);

        if ($insert) {
            $success = 'Email verified! Your account is ready. You can now <a href="login.php">sign in here</a>.';
            $step = 'done';
        } else {
            $error = 'Something went wrong. Please try again.'; $step = 'form';
        }
    }
}

/* ── STEP 2 — Resend OTP ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend') {
    if (!isset($_SESSION['reg_pending'])) {
        $error = 'Session expired. Please start registration again.'; $step = 'form';
    } else {
        $p = $_SESSION['reg_pending'];
        $otp = sendOtpEmail($p['email'], $p['name']);
        if ($otp) {
            $_SESSION['reg_otp']        = $otp;
            $_SESSION['reg_otp_expiry'] = time() + 600;
            $success = 'A new code has been sent to your email.';
        } else {
            $error = 'Failed to resend. Please try again in a moment.';
        }
        $step = 'verify';
    }
}

// Pre-fill form from pending session data (restored after going back)
$pending      = $_SESSION['reg_pending'] ?? [];
$prefill_name  = htmlspecialchars($pending['name']  ?? ($_POST['name']  ?? ''));
$prefill_email = htmlspecialchars($pending['email'] ?? ($_POST['email'] ?? ''));
$prefill_phone = htmlspecialchars($pending['phone'] ?? ($_POST['phone'] ?? ''));
$prefill_role  = $pending['role'] ?? ($pre_role ?: ($_POST['role'] ?? ''));

$pendingEmail = $_SESSION['reg_pending']['email'] ?? '';
$pendingName  = $_SESSION['reg_pending']['name']  ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — MindMerge SmartCampus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --sand:#f5f0e8; --sand-2:#ede6d6; --sand-3:#d9cebc; --sand-4:#b8a98c;
  --brown:#4a3b20; --brown-2:#6b5430; --amber:#b07020;
  --green:#2d5c3e; --red:#8c2e2e;
  --ink:#2b2015; --ink-2:#4a3b28; --ink-3:#7a6a55; --white:#fdfaf5;
}
html,body { font-family:'DM Sans',sans-serif; background:var(--sand); color:var(--ink); min-height:100svh; }
body { display:flex; align-items:center; justify-content:center; padding:28px 16px; min-height:100svh; }

.wrap { width:100%; max-width:440px; animation:rise .5s cubic-bezier(.16,1,.3,1) both; }
@keyframes rise { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }

/* Logo */
.logo-row { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
.logo-box  { width:46px; height:46px; border-radius:13px; background:var(--brown); display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0; }
.logo-name { font-family:'Fraunces',serif; font-size:22px; font-weight:600; color:var(--ink); line-height:1; letter-spacing:-0.3px; }
.logo-sub  { font-size:10.5px; font-weight:500; letter-spacing:2.5px; text-transform:uppercase; color:var(--ink-3); margin-top:3px; }

/* Progress */
.progress-row { display:flex; align-items:center; margin-bottom:24px; }
.prog-step    { display:flex; align-items:center; gap:7px; }
.prog-circle  { width:28px; height:28px; border-radius:50%; border:2px solid var(--sand-3); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:var(--ink-3); transition:all .3s; }
.prog-circle.active { background:var(--brown); border-color:var(--brown); color:#fff; }
.prog-circle.done   { background:var(--green); border-color:var(--green); color:#fff; }
.prog-label  { font-size:12px; color:var(--ink-3); font-weight:500; }
.prog-label.active { color:var(--brown); font-weight:700; }
.prog-line   { flex:1; height:2px; background:var(--sand-3); margin:0 8px; }
.prog-line.done { background:var(--green); }

/* Page head */
.page-head  { margin-bottom:22px; }
.page-title { font-family:'Fraunces',serif; font-size:32px; font-weight:700; color:var(--ink); letter-spacing:-0.5px; line-height:1.1; margin-bottom:5px; }
.page-sub   { font-size:14px; font-weight:300; color:var(--ink-3); line-height:1.5; }

/* Alerts */
.alert { display:flex; align-items:flex-start; gap:10px; border-radius:10px; padding:12px 14px; font-size:14px; line-height:1.5; margin-bottom:16px; animation:drop .3s cubic-bezier(.16,1,.3,1) both; }
@keyframes drop { from{opacity:0;transform:translateY(-5px)} to{opacity:1;transform:none} }
.alert i { font-size:14px; flex-shrink:0; margin-top:1px; }
.alert-success { background:#eaf4ed; border:1px solid #aed6b8; color:var(--green); }
.alert-success a { color:var(--green); font-weight:600; }
.alert-error   { background:#faeaea; border:1px solid #e0aaaa; color:var(--red); }

/* Fields */
.fields { display:flex; flex-direction:column; gap:14px; }
.field-label { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:600; letter-spacing:1.3px; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px; }
.field-label i { font-size:10px; }
.input-wrap  { position:relative; display:flex; align-items:center; }
.input-icon  { position:absolute; left:14px; z-index:2; font-size:14px; color:var(--sand-4); pointer-events:none; transition:color .2s; }
.input-wrap:focus-within .input-icon { color:var(--amber); }

.field-input,.field-select {
  width:100%; background:var(--white); border:1.5px solid var(--sand-3); border-radius:10px;
  padding:12px 44px 12px 42px; font-family:'DM Sans',sans-serif; font-size:15px; font-weight:400;
  color:#2b2015 !important; -webkit-text-fill-color:#2b2015 !important;
  outline:none; transition:border-color .2s, box-shadow .2s; caret-color:var(--amber);
}
.field-input::placeholder { color:#b8a98c !important; -webkit-text-fill-color:#b8a98c !important; font-size:13.5px; }
.field-input:focus,.field-select:focus { border-color:var(--amber); box-shadow:0 0 0 3px rgba(176,112,32,.10); }
.field-input.valid   { border-color:#2d7a4f; }
.field-input.invalid { border-color:var(--red); }
.field-input:-webkit-autofill { -webkit-text-fill-color:#2b2015 !important; -webkit-box-shadow:0 0 0 100px #fdfaf5 inset !important; transition:background-color 9999s ease 0s; }

.field-select { appearance:none; -webkit-appearance:none; cursor:pointer; }
.field-select option { color:#2b2015; background:#fdfaf5; }
.select-caret { position:absolute; right:14px; z-index:2; font-size:11px; color:var(--sand-4); pointer-events:none; }

.eye-btn { position:absolute; right:13px; z-index:2; background:none; border:none; cursor:pointer; color:var(--sand-4); font-size:14px; padding:4px; transition:color .2s; }
.eye-btn:hover { color:var(--amber); }

.val-icon { position:absolute; right:13px; z-index:3; font-size:13px; pointer-events:none; opacity:0; transition:opacity .2s; }
.val-icon.show { opacity:1; }
.val-icon.ok  { color:#2d7a4f; }
.val-icon.err { color:var(--red); }

.field-hint { font-size:11.5px; margin-top:4px; min-height:15px; transition:color .2s; }
.field-hint.ok  { color:#2d7a4f; }
.field-hint.err { color:var(--red); }

/* Password strength */
.strength-wrap { margin-top:8px; }
.strength-track { height:4px; border-radius:3px; background:var(--sand-2); overflow:hidden; }
.strength-bar   { height:100%; width:0; border-radius:3px; transition:width .35s, background .35s; }
.strength-label { font-size:11.5px; color:var(--ink-3); margin-top:4px; font-weight:500; }

/* Password rules checklist */
.pw-rules { display:flex; flex-direction:column; gap:4px; margin-top:9px; }
.pw-rule  { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--ink-3); transition:color .2s; }
.pw-rule i { font-size:11px; color:var(--sand-4); transition:color .2s; width:13px; text-align:center; }
.pw-rule.met   { color:var(--green); }
.pw-rule.met i { color:var(--green); }
.pw-rule.unmet i { color:var(--red); }
.pw-rule.unmet { color:var(--red); }

/* Submit button */
.btn-create {
  width:100%; margin-top:20px; background:var(--brown); border:none; border-radius:10px; padding:14px;
  font-family:'DM Sans',sans-serif; font-size:16px; font-weight:600; color:var(--sand); cursor:pointer;
  display:flex; align-items:center; justify-content:center; gap:9px;
  transition:background .2s, transform .15s; letter-spacing:0.1px;
}
.btn-create:hover  { background:var(--ink); transform:translateY(-1px); }
.btn-create:active { transform:scale(.99); }
.btn-create:disabled { opacity:.6; cursor:not-allowed; transform:none; }

/* OTP */
.otp-info { background:#fdf5e8; border:1px solid #ddc890; border-radius:12px; padding:18px; margin-bottom:20px; text-align:center; }
.otp-info p { font-size:14px; color:var(--ink-2); line-height:1.7; margin:0; }
.otp-info strong { color:var(--ink); }
.otp-inputs { display:flex; justify-content:center; gap:10px; margin:20px 0; }
.otp-digit  { width:52px; height:60px; border:2px solid var(--sand-3); border-radius:12px; background:var(--white); font-family:'DM Sans',sans-serif; font-size:26px; font-weight:700; color:var(--ink); text-align:center; outline:none; transition:border-color .2s, box-shadow .2s; caret-color:var(--amber); }
.otp-digit:focus  { border-color:var(--amber); box-shadow:0 0 0 3px rgba(176,112,32,.12); }
.otp-digit.filled { border-color:var(--brown-2); background:var(--sand); }
.otp-digit.error  { border-color:var(--red); background:#faeaea; }
.resend-row  { text-align:center; margin-top:16px; }
.resend-hint { font-size:13px; color:var(--ink-3); margin-bottom:8px; }
.resend-btn  { background:none; border:none; cursor:pointer; color:var(--brown-2); font-size:13.5px; font-weight:600; text-decoration:underline; padding:0; display:inline-flex; align-items:center; gap:6px; transition:color .2s; }
.resend-btn:hover { color:var(--ink); }
.resend-btn:disabled { opacity:.5; cursor:not-allowed; }
#countdown { font-weight:700; color:var(--amber); }

/* Divider / back */
.divider  { display:flex; align-items:center; gap:12px; margin:18px 0 14px; }
.div-line { flex:1; height:1px; background:var(--sand-3); }
.div-txt  { font-size:12.5px; color:var(--ink-3); white-space:nowrap; }
.btn-login { width:100%; background:transparent; border:1.5px solid var(--sand-3); border-radius:10px; padding:13px; font-family:'DM Sans',sans-serif; font-size:15px; font-weight:600; color:var(--brown); text-decoration:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .2s; }
.btn-login:hover { background:var(--sand-2); border-color:var(--brown-2); }
.btn-back  { width:100%; background:transparent; border:1.5px solid var(--sand-3); border-radius:10px; padding:11px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; color:var(--ink-3); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:7px; transition:all .2s; margin-top:10px; }
.btn-back:hover { background:var(--sand-2); color:var(--ink); }

@media (max-width:460px) { .page-title{font-size:27px;} .otp-digit{width:42px;height:52px;font-size:22px;} .otp-inputs{gap:7px;} }
</style>
</head>
<body>
<div class="wrap">

  <!-- Logo -->
  <div class="logo-row">
    <div class="logo-box">🧠</div>
    <div>
      <div class="logo-name">MindMerge</div>
      <div class="logo-sub">SmartCampus</div>
    </div>
  </div>

  <!-- Progress -->
  <div class="progress-row">
    <div class="prog-step">
      <div class="prog-circle <?= $step==='form' ? 'active' : 'done' ?>">
        <?= $step==='form' ? '1' : '<i class="fas fa-check" style="font-size:10px"></i>' ?>
      </div>
      <span class="prog-label <?= $step==='form'?'active':'' ?>">Details</span>
    </div>
    <div class="prog-line <?= $step!=='form'?'done':'' ?>"></div>
    <div class="prog-step">
      <div class="prog-circle <?= $step==='verify'?'active':($step==='done'?'done':'') ?>">
        <?= $step==='done' ? '<i class="fas fa-check" style="font-size:10px"></i>' : '2' ?>
      </div>
      <span class="prog-label <?= $step==='verify'?'active':'' ?>">Verify Email</span>
    </div>
    <div class="prog-line <?= $step==='done'?'done':'' ?>"></div>
    <div class="prog-step">
      <div class="prog-circle <?= $step==='done'?'done':'' ?>">
        <?= $step==='done' ? '<i class="fas fa-check" style="font-size:10px"></i>' : '3' ?>
      </div>
      <span class="prog-label <?= $step==='done'?'active':'' ?>">Done</span>
    </div>
  </div>

  <!-- Page heading -->
  <div class="page-head">
    <?php if ($step==='verify'): ?>
      <div class="page-title">Check your email</div>
      <div class="page-sub">We sent a 6-digit code to verify your identity.</div>
    <?php elseif ($step==='done'): ?>
      <div class="page-title">You're all set! 🎉</div>
      <div class="page-sub">Your account has been verified and created.</div>
    <?php else: ?>
      <div class="page-title">Create Account</div>
      <div class="page-sub">Join MindMerge SmartCampus today.</div>
    <?php endif; ?>
  </div>

  <!-- Alerts -->
  <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i><span><?= $success ?></span></div>
  <?php endif; ?>

  <?php if ($step==='form'): ?>
  <!-- ══ STEP 1 — Form ══ -->
  <form method="POST" id="regForm" autocomplete="on" novalidate>
    <input type="hidden" name="action" value="register">
    <div class="fields">

      <!-- Full Name -->
      <div>
        <div class="field-label"><i class="fas fa-user"></i> Full Name</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-user"></i>
          <input class="field-input" type="text" name="name" id="fName"
            placeholder="Enter your full name"
            value="<?= $prefill_name ?>"
            required autocomplete="name">
          <i class="val-icon fas fa-circle-check ok" id="nameOk"></i>
        </div>
        <div class="field-hint" id="nameHint"></div>
      </div>

      <!-- Role — No Admin -->
      <div>
        <div class="field-label"><i class="fas fa-id-badge"></i> Select Role</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-id-badge"></i>
          <select class="field-select" name="role" id="roleSelect" required>
            <option value="" disabled <?= !$prefill_role?'selected':'' ?>>— Choose your role —</option>
            <option value="teacher" <?= $prefill_role==='teacher'?'selected':'' ?>>Teacher</option>
            <option value="student" <?= $prefill_role==='student'?'selected':'' ?>>Student</option>
            <option value="parent"  <?= $prefill_role==='parent' ?'selected':'' ?>>Parent</option>
          </select>
          <i class="select-caret fas fa-chevron-down"></i>
        </div>
      </div>

      <!-- Email -->
      <div>
        <div class="field-label"><i class="fas fa-envelope"></i> Email Address</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-at"></i>
          <input class="field-input" type="email" name="email" id="fEmail"
            placeholder="your@email.com"
            value="<?= $prefill_email ?>"
            required autocomplete="email" spellcheck="false">
          <i class="val-icon fas fa-circle-check ok" id="emailOk"></i>
        </div>
        <div class="field-hint" id="emailHint"></div>
      </div>

      <!-- Phone — Required -->
      <div>
        <div class="field-label"><i class="fas fa-phone"></i> Phone Number</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-mobile-screen"></i>
          <input class="field-input" type="tel" name="phone" id="fPhone"
            placeholder="+91 98765 43210"
            value="<?= $prefill_phone ?>"
            required autocomplete="tel">
          <i class="val-icon fas fa-circle-check ok" id="phoneOk"></i>
        </div>
        <div class="field-hint" id="phoneHint"></div>
      </div>

      <!-- Password -->
      <div>
        <div class="field-label"><i class="fas fa-lock"></i> Password</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-key"></i>
          <input class="field-input" type="password" name="password" id="pw1"
            placeholder="Create a strong password"
            required autocomplete="new-password">
          <button type="button" class="eye-btn" onclick="togglePw('pw1','ei1')">
            <i class="fas fa-eye" id="ei1"></i>
          </button>
        </div>
        <!-- Strength bar -->
        <div class="strength-wrap">
          <div class="strength-track"><div class="strength-bar" id="strengthBar"></div></div>
          <div class="strength-label" id="strengthLabel"></div>
        </div>
        <!-- Rules checklist -->
        <div class="pw-rules">
          <div class="pw-rule" id="rule-len"><i class="fas fa-circle-xmark"></i> At least 8 characters</div>
          <div class="pw-rule" id="rule-cap"><i class="fas fa-circle-xmark"></i> At least one uppercase letter (A–Z)</div>
          <div class="pw-rule" id="rule-num"><i class="fas fa-circle-xmark"></i> At least one number (0–9)</div>
          <div class="pw-rule" id="rule-sym"><i class="fas fa-circle-xmark"></i> At least one symbol (!@#$…)</div>
        </div>
      </div>

      <!-- Confirm Password -->
      <div>
        <div class="field-label"><i class="fas fa-lock"></i> Confirm Password</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-key"></i>
          <input class="field-input" type="password" name="confirm" id="pw2"
            placeholder="Re-enter your password"
            required autocomplete="new-password">
          <button type="button" class="eye-btn" onclick="togglePw('pw2','ei2')">
            <i class="fas fa-eye" id="ei2"></i>
          </button>
          <i class="val-icon fas fa-circle-check ok" id="pw2Ok"></i>
        </div>
        <div class="field-hint" id="pw2Hint"></div>
      </div>

    </div>

    <button type="submit" class="btn-create" id="btnSubmit">
      <i class="fas fa-paper-plane" id="btnIcon"></i>
      <span id="btnText">Send Verification Code</span>
    </button>
  </form>

  <div class="divider"><div class="div-line"></div><span class="div-txt">Already have an account?</span><div class="div-line"></div></div>
  <a href="login.php" class="btn-login"><i class="fas fa-arrow-right-to-bracket"></i> Sign In Instead</a>

  <?php elseif ($step==='verify'): ?>
  <!-- ══ STEP 2 — OTP ══ -->
  <div class="otp-info">
    <p>We sent a 6-digit code to<br>
    <strong><?= htmlspecialchars($pendingEmail) ?></strong><br>
    It expires in 10 minutes.</p>
  </div>

  <form method="POST" id="otpForm" autocomplete="off" novalidate>
    <input type="hidden" name="action" value="verify">
    <div class="otp-inputs" id="otpInputs">
      <?php for ($i=0;$i<6;$i++): ?>
        <input class="otp-digit" type="text" name="otp[]"
          maxlength="1" inputmode="numeric" pattern="[0-9]"
          id="otp<?=$i?>" autocomplete="one-time-code"
          <?= $i===0?'autofocus':'' ?>>
      <?php endfor; ?>
    </div>
    <button type="submit" class="btn-create" id="otpBtn">
      <i class="fas fa-shield-halved" id="otpBtnIcon"></i>
      <span id="otpBtnText">Verify & Create Account</span>
    </button>
  </form>

  <div class="resend-row">
    <p class="resend-hint">Didn't get it? Resend in <span id="countdown">60</span>s</p>
    <form method="POST" style="display:inline">
      <input type="hidden" name="action" value="resend">
      <button class="resend-btn" id="resendBtn" type="submit" disabled>
        <i class="fas fa-rotate-right"></i> Resend Code
      </button>
    </form>
  </div>

  <!-- ✅ FIX: POST form to clear session before going back -->
  <form method="POST" style="margin-top:10px;">
    <input type="hidden" name="action" value="go_back">
    <button type="submit" class="btn-back">
      <i class="fas fa-arrow-left"></i> Go back &amp; change details
    </button>
  </form>

  <?php elseif ($step==='done'): ?>
  <!-- ══ STEP 3 — Done ══ -->
  <div style="text-align:center;padding:16px 0 8px;">
    <div style="width:72px;height:72px;background:#eaf4ed;border:2px solid #aed6b8;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;">✅</div>
    <p style="font-size:15px;color:var(--ink-2);line-height:1.7;margin-bottom:22px;">
      Welcome to MindMerge, <strong><?= htmlspecialchars($pendingName) ?></strong>!<br>
      Your account has been verified and is ready to use.
    </p>
  </div>
  <a href="login.php" class="btn-create" style="text-decoration:none;margin-top:0;">
    <i class="fas fa-arrow-right-to-bracket"></i> Sign In Now
  </a>
  <?php endif; ?>

</div>

<script>
/* ── Toggle password ── */
function togglePw(id, iconId) {
  var inp = document.getElementById(id);
  var ico = document.getElementById(iconId);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

/* ── Validation helper ── */
function setValid(inputEl, iconEl, hintEl, valid, msg) {
  if (valid === null) {
    inputEl.classList.remove('valid','invalid');
    if (iconEl) iconEl.className = 'val-icon fas fa-circle-check ok';
    if (hintEl) { hintEl.textContent=''; hintEl.className='field-hint'; }
    return;
  }
  inputEl.classList.toggle('valid', valid);
  inputEl.classList.toggle('invalid', !valid);
  if (iconEl) iconEl.className = 'val-icon fas show ' + (valid ? 'fa-circle-check ok' : 'fa-circle-xmark err');
  if (hintEl) { hintEl.textContent = msg||''; hintEl.className = 'field-hint '+(valid?'ok':'err'); }
}

/* ── Password strength + rules ── */
function updatePassword(val) {
  var hasLen = val.length >= 8;
  var hasCap = /[A-Z]/.test(val);
  var hasNum = /[0-9]/.test(val);
  var hasSym = /[^A-Za-z0-9]/.test(val);

  function setRule(id, met) {
    var el = document.getElementById(id);
    if (!el) return;
    el.className = 'pw-rule ' + (val ? (met ? 'met' : 'unmet') : '');
    el.querySelector('i').className = 'fas ' + (met ? 'fa-circle-check' : 'fa-circle-xmark');
  }
  setRule('rule-len', hasLen);
  setRule('rule-cap', hasCap);
  setRule('rule-num', hasNum);
  setRule('rule-sym', hasSym);

  var score = [hasLen, hasCap, hasNum, hasSym].filter(Boolean).length;
  var bar   = document.getElementById('strengthBar');
  var lbl   = document.getElementById('strengthLabel');
  if (!bar) return;

  var levels = [
    {w:'0%',  c:'',        t:''},
    {w:'25%', c:'#c0392b', t:'Weak'},
    {w:'50%', c:'#b07020', t:'Fair'},
    {w:'75%', c:'#9c6a1a', t:'Good'},
    {w:'100%',c:'#1e5c3a', t:'Strong ✓'},
  ];
  var lv = val ? levels[score] : levels[0];
  bar.style.width      = lv.w;
  bar.style.background = lv.c;
  lbl.textContent      = lv.t;
  lbl.style.color      = lv.c;
}

(function() {
  var name  = document.getElementById('fName');
  var email = document.getElementById('fEmail');
  var phone = document.getElementById('fPhone');
  var pw1   = document.getElementById('pw1');
  var pw2   = document.getElementById('pw2');
  if (!name) return;

  name.addEventListener('input', function() {
    var v = this.value.trim();
    if (!v) { setValid(this, document.getElementById('nameOk'), document.getElementById('nameHint'), null); return; }
    var ok = v.length >= 2 && /^[a-zA-Z\s'\-]+$/.test(v);
    setValid(this, document.getElementById('nameOk'), document.getElementById('nameHint'),
      ok, ok ? 'Looks good!' : 'At least 2 letters, no special characters.');
  });

  email.addEventListener('input', function() {
    var v = this.value.trim();
    if (!v) { setValid(this, document.getElementById('emailOk'), document.getElementById('emailHint'), null); return; }
    var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    setValid(this, document.getElementById('emailOk'), document.getElementById('emailHint'),
      ok, ok ? 'Valid email.' : 'Enter a valid email address.');
  });

  phone.addEventListener('input', function() {
    var v = this.value.trim();
    if (!v) { setValid(this, document.getElementById('phoneOk'), document.getElementById('phoneHint'), null); return; }
    var digits = v.replace(/\D/g,'').length;
    var ok = digits >= 7 && digits <= 15;
    setValid(this, document.getElementById('phoneOk'), document.getElementById('phoneHint'),
      ok, ok ? 'Valid phone number.' : 'Enter 7–15 digits. You may include + or country code.');
  });

  pw1.addEventListener('input', function() {
    updatePassword(this.value);
    if (pw2.value) pw2.dispatchEvent(new Event('input'));
  });

  pw2.addEventListener('input', function() {
    var v = this.value;
    if (!v) { setValid(this, document.getElementById('pw2Ok'), document.getElementById('pw2Hint'), null); return; }
    var ok = v === pw1.value;
    setValid(this, document.getElementById('pw2Ok'), document.getElementById('pw2Hint'),
      ok, ok ? 'Passwords match!' : 'Passwords do not match.');
  });

  document.getElementById('regForm').addEventListener('submit', function(e) {
    var errs = [];
    var n = name.value.trim();
    var em = email.value.trim();
    var ph = phone.value.trim();
    var p1 = pw1.value;
    var p2 = pw2.value;
    var role = document.getElementById('roleSelect').value;

    if (!n || n.length < 2)                              errs.push('Full name is required (min 2 characters).');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em))         errs.push('Valid email is required.');
    if (!ph || ph.replace(/\D/g,'').length < 7)          errs.push('Phone number is required (min 7 digits).');
    if (!role)                                           errs.push('Please select a role.');
    if (p1.length < 8)                                   errs.push('Password must be at least 8 characters.');
    if (!/[A-Z]/.test(p1))                              errs.push('Password must contain an uppercase letter.');
    if (!/[0-9]/.test(p1))                              errs.push('Password must contain a number.');
    if (!/[^A-Za-z0-9]/.test(p1))                       errs.push('Password must contain a symbol.');
    if (p1 !== p2)                                       errs.push('Passwords do not match.');

    if (errs.length) { e.preventDefault(); alert(errs.join('\n')); return; }

    var btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    document.getElementById('btnIcon').className = 'fas fa-spinner fa-spin';
    document.getElementById('btnText').textContent = 'Sending code…';
  });
})();

/* ── OTP navigation ── */
(function() {
  var inputs = document.querySelectorAll('.otp-digit');
  if (!inputs.length) return;

  inputs.forEach(function(inp, i) {
    inp.addEventListener('input', function() {
      var v = this.value.replace(/[^0-9]/g,'');
      this.value = v ? v[v.length-1] : '';
      if (v) { this.classList.add('filled'); if (i < inputs.length-1) inputs[i+1].focus(); }
      else     this.classList.remove('filled');
    });
    inp.addEventListener('keydown', function(e) {
      if (e.key==='Backspace' && !this.value && i>0) {
        inputs[i-1].focus(); inputs[i-1].value=''; inputs[i-1].classList.remove('filled');
      }
      if (e.key==='ArrowLeft'  && i>0)               inputs[i-1].focus();
      if (e.key==='ArrowRight' && i<inputs.length-1) inputs[i+1].focus();
    });
    inp.addEventListener('paste', function(e) {
      e.preventDefault();
      var pasted = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'');
      inputs.forEach(function(b,j){ if (pasted[j]) { b.value=pasted[j]; b.classList.add('filled'); } });
      var last = Math.min(pasted.length, inputs.length)-1;
      if (last>=0) inputs[last].focus();
    });
  });

  var otpForm = document.getElementById('otpForm');
  otpForm && otpForm.addEventListener('submit', function(e) {
    var val = Array.from(inputs).map(function(b){return b.value;}).join('');
    if (val.length!==6 || !/^\d{6}$/.test(val)) {
      e.preventDefault();
      inputs.forEach(function(b){b.classList.add('error');});
      setTimeout(function(){inputs.forEach(function(b){b.classList.remove('error');});},700);
      return;
    }
    var btn = document.getElementById('otpBtn');
    btn.disabled = true;
    document.getElementById('otpBtnIcon').className = 'fas fa-spinner fa-spin';
    document.getElementById('otpBtnText').textContent = 'Verifying…';
  });

  /* Countdown */
  var count=60, display=document.getElementById('countdown'), resend=document.getElementById('resendBtn');
  var timer = setInterval(function() {
    count--;
    if (display) display.textContent = count;
    if (count<=0) {
      clearInterval(timer);
      if (display) display.parentElement.textContent = 'You can resend now.';
      if (resend)  resend.disabled = false;
    }
  }, 1000);
})();
</script>
</body>
</html>