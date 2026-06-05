<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php"); exit;
}

$error   = '';
$success = '';
$step    = $_SESSION['fp_step'] ?? 'form';

/* ── STEP 1 — Request OTP ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $email_esc = $conn->real_escape_string($email);
        $result    = $conn->query("SELECT id, name FROM users WHERE email = '$email_esc' LIMIT 1");

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $otp  = sendOtpEmail($email, $user['name']);
            if ($otp) {
                $_SESSION['fp_step']       = 'verify';
                $_SESSION['fp_email']      = $email;
                $_SESSION['fp_name']       = $user['name'];
                $_SESSION['fp_otp']        = $otp;
                $_SESSION['fp_otp_expiry'] = time() + 600;
                $step = 'verify';
            } else {
                $error = 'Failed to send email. Please try again.';
            }
        } else {
            // Show verify step anyway (prevent email enumeration)
            $_SESSION['fp_step']       = 'verify';
            $_SESSION['fp_email']      = $email;
            $_SESSION['fp_name']       = '';
            $_SESSION['fp_otp']        = null;
            $_SESSION['fp_otp_expiry'] = time() + 600;
            $step = 'verify';
        }
    }
}

/* ── STEP 2 — Verify OTP ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify') {
    $entered = trim(implode('', $_POST['otp'] ?? []));

    if (empty($entered) || strlen($entered) !== 6 || !ctype_digit($entered)) {
        $error = 'Please enter the 6-digit code.';
        $step  = 'verify';
    } elseif (!isset($_SESSION['fp_otp'], $_SESSION['fp_otp_expiry'])) {
        $error = 'Session expired. Please start again.';
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name'],
              $_SESSION['fp_otp'], $_SESSION['fp_otp_expiry']);
        $step = 'form';
    } elseif (time() > $_SESSION['fp_otp_expiry']) {
        $error = 'Your code has expired. Please request a new one.';
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name'],
              $_SESSION['fp_otp'], $_SESSION['fp_otp_expiry']);
        $step = 'form';
    } elseif ($_SESSION['fp_otp'] === null || $entered !== $_SESSION['fp_otp']) {
        $error = 'Incorrect code. Please try again.';
        $step  = 'verify';
    } else {
        $_SESSION['fp_step']     = 'reset';
        $_SESSION['fp_verified'] = true;
        $step = 'reset';
    }
}

/* ── STEP 2b — Resend OTP ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend') {
    if (!isset($_SESSION['fp_email'])) {
        $error = 'Session expired. Please start again.';
        unset($_SESSION['fp_step']);
        $step = 'form';
    } else {
        $otp = sendOtpEmail($_SESSION['fp_email'], $_SESSION['fp_name'] ?: 'User');
        if ($otp) {
            $_SESSION['fp_otp']        = $otp;
            $_SESSION['fp_otp_expiry'] = time() + 600;
            $success = 'A new code has been sent to your email.';
        } else {
            $error = 'Failed to resend. Please try again.';
        }
        $step = 'verify';
    }
}

/* ── STEP 3 — Reset Password ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    if (empty($_SESSION['fp_verified']) || empty($_SESSION['fp_email'])) {
        $error = 'Session expired. Please start again.';
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name'],
              $_SESSION['fp_otp'], $_SESSION['fp_otp_expiry'], $_SESSION['fp_verified']);
        $step = 'form';
    } else {
        $new  = $_POST['password'] ?? '';
        $conf = $_POST['confirm']  ?? '';

        if (strlen($new) < 8) {
            $error = 'Password must be at least 8 characters.'; $step = 'reset';
        } elseif (!preg_match('/[A-Z]/', $new)) {
            $error = 'Must contain at least one uppercase letter.'; $step = 'reset';
        } elseif (!preg_match('/[0-9]/', $new)) {
            $error = 'Must contain at least one number.'; $step = 'reset';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $new)) {
            $error = 'Must contain at least one symbol.'; $step = 'reset';
        } elseif ($new !== $conf) {
            $error = 'Passwords do not match.'; $step = 'reset';
        } else {
            $hash    = password_hash($new, PASSWORD_DEFAULT);
            $h       = $conn->real_escape_string($hash);
            $email_e = $conn->real_escape_string($_SESSION['fp_email']);
            $conn->query("UPDATE users SET password = '$h' WHERE email = '$email_e'");
            unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name'],
                  $_SESSION['fp_otp'], $_SESSION['fp_otp_expiry'], $_SESSION['fp_verified']);
            $step = 'done';
        }
    }
}

$pendingEmail = $_SESSION['fp_email'] ?? '';
$pendingName  = $_SESSION['fp_name']  ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — MindMerge SmartCampus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --sand:#f5f0e8; --sand-2:#ede6d6; --sand-3:#d9cebc; --sand-4:#b8a98c;
  --brown:#4a3b20; --brown-2:#6b5430; --amber:#b07020;
  --green:#2d5c3e; --red:#8c2e2e; --ink:#2b2015; --ink-2:#4a3b28; --ink-3:#7a6a55; --white:#fdfaf5;
}
html,body { font-family:'DM Sans',sans-serif; background:var(--sand); color:var(--ink); min-height:100svh; }
body { display:flex; align-items:center; justify-content:center; padding:28px 16px; min-height:100svh; }
.wrap { width:100%; max-width:440px; animation:rise .5s cubic-bezier(.16,1,.3,1) both; }
@keyframes rise { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }
.logo-row { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
.logo-box  { width:46px; height:46px; border-radius:13px; background:var(--brown); display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0; }
.logo-name { font-family:'Fraunces',serif; font-size:22px; font-weight:600; color:var(--ink); line-height:1; letter-spacing:-0.3px; }
.logo-sub  { font-size:10.5px; font-weight:500; letter-spacing:2.5px; text-transform:uppercase; color:var(--ink-3); margin-top:3px; }
.progress-row { display:flex; align-items:center; margin-bottom:24px; }
.prog-step { display:flex; align-items:center; gap:7px; }
.prog-circle { width:28px; height:28px; border-radius:50%; border:2px solid var(--sand-3); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:var(--ink-3); transition:all .3s; }
.prog-circle.active { background:var(--brown); border-color:var(--brown); color:#fff; }
.prog-circle.done   { background:var(--green); border-color:var(--green); color:#fff; }
.prog-label { font-size:12px; color:var(--ink-3); font-weight:500; }
.prog-label.active { color:var(--brown); font-weight:700; }
.prog-line { flex:1; height:2px; background:var(--sand-3); margin:0 8px; }
.prog-line.done { background:var(--green); }
.page-title { font-family:'Fraunces',serif; font-size:32px; font-weight:700; color:var(--ink); letter-spacing:-0.5px; line-height:1.1; margin-bottom:5px; }
.page-sub   { font-size:14px; font-weight:300; color:var(--ink-3); line-height:1.5; margin-bottom:22px; }
.alert { display:flex; align-items:flex-start; gap:10px; border-radius:10px; padding:12px 14px; font-size:14px; line-height:1.5; margin-bottom:16px; animation:drop .3s cubic-bezier(.16,1,.3,1) both; }
@keyframes drop { from{opacity:0;transform:translateY(-5px)} to{opacity:1;transform:none} }
.alert i { font-size:14px; flex-shrink:0; margin-top:1px; }
.alert-success { background:#eaf4ed; border:1px solid #aed6b8; color:var(--green); }
.alert-error   { background:#faeaea; border:1px solid #e0aaaa; color:var(--red); }
.fields { display:flex; flex-direction:column; gap:14px; }
.field-label { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:600; letter-spacing:1.3px; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px; }
.input-wrap { position:relative; display:flex; align-items:center; }
.input-icon { position:absolute; left:14px; z-index:2; font-size:14px; color:var(--sand-4); pointer-events:none; transition:color .2s; }
.input-wrap:focus-within .input-icon { color:var(--amber); }
.field-input {
  width:100%; background:var(--white); border:1.5px solid var(--sand-3); border-radius:10px;
  padding:12px 44px 12px 42px; font-family:'DM Sans',sans-serif; font-size:15px;
  color:#2b2015 !important; -webkit-text-fill-color:#2b2015 !important;
  outline:none; transition:border-color .2s,box-shadow .2s; caret-color:var(--amber);
}
.field-input::placeholder { color:#b8a98c !important; font-size:13.5px; }
.field-input:focus { border-color:var(--amber); box-shadow:0 0 0 3px rgba(176,112,32,.10); }
.eye-btn { position:absolute; right:13px; z-index:2; background:none; border:none; cursor:pointer; color:var(--sand-4); font-size:14px; padding:4px; transition:color .2s; }
.eye-btn:hover { color:var(--amber); }
.field-hint { font-size:11.5px; margin-top:4px; min-height:15px; }
.field-hint.ok  { color:#2d7a4f; }
.field-hint.err { color:var(--red); }
.strength-wrap  { margin-top:8px; }
.strength-track { height:4px; border-radius:3px; background:var(--sand-2); overflow:hidden; }
.strength-bar   { height:100%; width:0; border-radius:3px; transition:width .35s,background .35s; }
.strength-label { font-size:11.5px; color:var(--ink-3); margin-top:4px; font-weight:500; }
.pw-rules { display:flex; flex-direction:column; gap:4px; margin-top:9px; }
.pw-rule  { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--ink-3); }
.pw-rule i { font-size:11px; color:var(--sand-4); width:13px; text-align:center; }
.pw-rule.met   { color:var(--green); }
.pw-rule.met i { color:var(--green); }
.pw-rule.unmet { color:var(--red); }
.pw-rule.unmet i { color:var(--red); }
.btn-main {
  width:100%; margin-top:20px; background:var(--brown); border:none; border-radius:10px; padding:14px;
  font-family:'DM Sans',sans-serif; font-size:16px; font-weight:600; color:var(--sand); cursor:pointer;
  display:flex; align-items:center; justify-content:center; gap:9px; transition:background .2s,transform .15s;
}
.btn-main:hover  { background:var(--ink); transform:translateY(-1px); }
.btn-main:active { transform:scale(.99); }
.btn-main:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.btn-back { width:100%; margin-top:10px; background:transparent; border:1.5px solid var(--sand-3); border-radius:10px; padding:11px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; color:var(--ink-3); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:7px; transition:all .2s; }
.btn-back:hover { background:var(--sand-2); color:var(--ink); }
.otp-info { background:#fdf5e8; border:1px solid #ddc890; border-radius:12px; padding:18px; margin-bottom:20px; text-align:center; }
.otp-info p { font-size:14px; color:var(--ink-2); line-height:1.7; margin:0; }
.otp-info strong { color:var(--ink); }
.otp-inputs { display:flex; justify-content:center; gap:10px; margin:20px 0; }
.otp-digit  { width:52px; height:60px; border:2px solid var(--sand-3); border-radius:12px; background:var(--white); font-family:'DM Sans',sans-serif; font-size:26px; font-weight:700; color:var(--ink); text-align:center; outline:none; transition:border-color .2s,box-shadow .2s; }
.otp-digit:focus  { border-color:var(--amber); box-shadow:0 0 0 3px rgba(176,112,32,.12); }
.otp-digit.filled { border-color:var(--brown-2); background:var(--sand); }
.otp-digit.error  { border-color:var(--red); background:#faeaea; }
.resend-row  { text-align:center; margin-top:16px; }
.resend-hint { font-size:13px; color:var(--ink-3); margin-bottom:8px; }
.resend-btn  { background:none; border:none; cursor:pointer; color:var(--brown-2); font-size:13.5px; font-weight:600; text-decoration:underline; padding:0; display:inline-flex; align-items:center; gap:6px; }
.resend-btn:disabled { opacity:.5; cursor:not-allowed; }
#countdown { font-weight:700; color:var(--amber); }
.done-box  { text-align:center; padding:16px 0 8px; }
.done-icon { width:72px; height:72px; background:#eaf4ed; border:2px solid #aed6b8; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:32px; }
@media(max-width:460px) { .page-title{font-size:27px;} .otp-digit{width:42px;height:52px;font-size:22px;} .otp-inputs{gap:7px;} }
</style>
</head>
<body>
<div class="wrap">

  <div class="logo-row">
    <div class="logo-box">🧠</div>
    <div>
      <div class="logo-name">MindMerge</div>
      <div class="logo-sub">SmartCampus</div>
    </div>
  </div>

  <!-- Progress bar -->
  <div class="progress-row">
    <div class="prog-step">
      <div class="prog-circle <?= in_array($step,['verify','reset','done'])?'done':($step==='form'?'active':'') ?>">
        <?= in_array($step,['verify','reset','done']) ? '<i class="fas fa-check" style="font-size:10px"></i>' : '1' ?>
      </div>
      <span class="prog-label <?= $step==='form'?'active':'' ?>">Email</span>
    </div>
    <div class="prog-line <?= in_array($step,['reset','done'])?'done':'' ?>"></div>
    <div class="prog-step">
      <div class="prog-circle <?= in_array($step,['reset','done'])?'done':($step==='verify'?'active':'') ?>">
        <?= in_array($step,['reset','done']) ? '<i class="fas fa-check" style="font-size:10px"></i>' : '2' ?>
      </div>
      <span class="prog-label <?= $step==='verify'?'active':'' ?>">Verify</span>
    </div>
    <div class="prog-line <?= $step==='done'?'done':'' ?>"></div>
    <div class="prog-step">
      <div class="prog-circle <?= $step==='done'?'done':($step==='reset'?'active':'') ?>">
        <?= $step==='done' ? '<i class="fas fa-check" style="font-size:10px"></i>' : '3' ?>
      </div>
      <span class="prog-label <?= in_array($step,['reset','done'])?'active':'' ?>">Reset</span>
    </div>
  </div>

  <!-- Heading -->
  <?php if ($step==='form'): ?>
    <div class="page-title">Forgot password?</div>
    <div class="page-sub">Enter your registered email and we'll send you a 6-digit code.</div>
  <?php elseif ($step==='verify'): ?>
    <div class="page-title">Check your email</div>
    <div class="page-sub">Enter the 6-digit code we sent to your inbox.</div>
  <?php elseif ($step==='reset'): ?>
    <div class="page-title">New password</div>
    <div class="page-sub">Choose a strong new password for your account.</div>
  <?php else: ?>
    <div class="page-title">All done! 🎉</div>
    <div class="page-sub">Your password has been reset successfully.</div>
  <?php endif; ?>

  <!-- Alerts -->
  <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i><span><?= htmlspecialchars($success) ?></span></div>
  <?php endif; ?>


  <?php if ($step === 'form'): ?>
  <!-- ══ STEP 1 ══ -->
  <form method="POST" id="fpForm" novalidate>
    <input type="hidden" name="action" value="request">
    <div>
      <div class="field-label"><i class="fas fa-envelope"></i> Email Address</div>
      <div class="input-wrap">
        <i class="input-icon fas fa-at"></i>
        <input class="field-input" type="email" name="email" id="fpEmail"
          placeholder="your@email.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required autocomplete="email" spellcheck="false">
      </div>
      <div class="field-hint" id="emailHint"></div>
    </div>
    <button type="submit" class="btn-main" id="fpBtn">
      <i class="fas fa-paper-plane" id="btnIcon"></i>
      <span id="btnText">Send Verification Code</span>
    </button>
  </form>
  <a href="login.php" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px;font-size:14px;color:var(--ink-3);text-decoration:none;"
     onmouseover="this.style.color='var(--amber)'" onmouseout="this.style.color='var(--ink-3)'">
    <i class="fas fa-arrow-left"></i> Back to Sign In
  </a>

  <?php elseif ($step === 'verify'): ?>
  <!-- ══ STEP 2 ══ -->
  <div class="otp-info">
    <p>We sent a 6-digit code to<br>
    <strong><?= htmlspecialchars($pendingEmail) ?></strong><br>
    It expires in 10 minutes.</p>
  </div>
  <form method="POST" id="otpForm" autocomplete="off" novalidate>
    <input type="hidden" name="action" value="verify">
    <div class="otp-inputs">
      <?php for ($i=0; $i<6; $i++): ?>
        <input class="otp-digit" type="text" name="otp[]"
          maxlength="1" inputmode="numeric" pattern="[0-9]"
          id="otp<?=$i?>" <?=$i===0?'autofocus':''?>>
      <?php endfor; ?>
    </div>
    <button type="submit" class="btn-main" id="otpBtn">
      <i class="fas fa-shield-halved" id="otpBtnIcon"></i>
      <span id="otpBtnText">Verify Code</span>
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
  <button type="button" class="btn-back"
    onclick="fetch('',{method:'POST',body:new URLSearchParams({action:'_clear'})}).then(()=>location.href='forgot-password.php')">
    <i class="fas fa-arrow-left"></i> Go back &amp; change email
  </button>

  <?php elseif ($step === 'reset'): ?>
  <!-- ══ STEP 3 ══ -->
  <form method="POST" id="resetForm" novalidate>
    <input type="hidden" name="action" value="reset">
    <div class="fields">
      <div>
        <div class="field-label"><i class="fas fa-lock"></i> New Password</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-key"></i>
          <input class="field-input" type="password" name="password" id="pw1"
            placeholder="Create a strong password" required autocomplete="new-password">
          <button type="button" class="eye-btn" onclick="togglePw('pw1','ei1')">
            <i class="fas fa-eye" id="ei1"></i>
          </button>
        </div>
        <div class="strength-wrap">
          <div class="strength-track"><div class="strength-bar" id="strengthBar"></div></div>
          <div class="strength-label" id="strengthLabel"></div>
        </div>
        <div class="pw-rules">
          <div class="pw-rule" id="rule-len"><i class="fas fa-circle-xmark"></i> At least 8 characters</div>
          <div class="pw-rule" id="rule-cap"><i class="fas fa-circle-xmark"></i> At least one uppercase letter</div>
          <div class="pw-rule" id="rule-num"><i class="fas fa-circle-xmark"></i> At least one number</div>
          <div class="pw-rule" id="rule-sym"><i class="fas fa-circle-xmark"></i> At least one symbol</div>
        </div>
      </div>
      <div>
        <div class="field-label"><i class="fas fa-lock"></i> Confirm Password</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-key"></i>
          <input class="field-input" type="password" name="confirm" id="pw2"
            placeholder="Re-enter your password" required autocomplete="new-password">
          <button type="button" class="eye-btn" onclick="togglePw('pw2','ei2')">
            <i class="fas fa-eye" id="ei2"></i>
          </button>
        </div>
        <div class="field-hint" id="pw2Hint"></div>
      </div>
    </div>
    <button type="submit" class="btn-main" id="resetBtn">
      <i class="fas fa-check" id="resetIcon"></i>
      <span id="resetTxt">Set New Password</span>
    </button>
  </form>

  <?php else: ?>
  <!-- ══ STEP 4 — Done ══ -->
  <div class="done-box">
    <div class="done-icon">✅</div>
    <p style="font-size:15px;color:var(--ink-2);line-height:1.7;margin-bottom:22px;">
      Your password has been updated.<br>You can now sign in with your new password.
    </p>
  </div>
  <a href="login.php" class="btn-main" style="text-decoration:none;margin-top:0;">
    <i class="fas fa-arrow-right-to-bracket"></i> Sign In Now
  </a>
  <?php endif; ?>

</div>
<script>
function togglePw(id, iconId) {
  var inp = document.getElementById(id);
  var ico = document.getElementById(iconId);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

/* Step 1 */
(function(){
  var el = document.getElementById('fpEmail');
  if (!el) return;
  el.addEventListener('input', function(){
    var h = document.getElementById('emailHint');
    var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim());
    h.textContent = this.value && !ok ? 'Enter a valid email address.' : '';
    h.className = 'field-hint' + (this.value && !ok ? ' err' : '');
  });
  document.getElementById('fpForm').addEventListener('submit', function(e){
    var v = el.value.trim();
    if (!v || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
      e.preventDefault();
      var h = document.getElementById('emailHint');
      h.textContent = 'Please enter a valid email address.';
      h.className = 'field-hint err';
      el.focus(); return;
    }
    var btn = document.getElementById('fpBtn');
    btn.disabled = true;
    document.getElementById('btnIcon').className = 'fas fa-spinner fa-spin';
    document.getElementById('btnText').textContent = 'Sending…';
  });
})();

/* Step 2 — OTP */
(function(){
  var inputs = document.querySelectorAll('.otp-digit');
  if (!inputs.length) return;
  inputs.forEach(function(inp, i){
    inp.addEventListener('input', function(){
      var v = this.value.replace(/[^0-9]/g,'');
      this.value = v ? v[v.length-1] : '';
      if (v) { this.classList.add('filled'); if (i<inputs.length-1) inputs[i+1].focus(); }
      else this.classList.remove('filled');
    });
    inp.addEventListener('keydown', function(e){
      if (e.key==='Backspace' && !this.value && i>0) {
        inputs[i-1].focus(); inputs[i-1].value=''; inputs[i-1].classList.remove('filled');
      }
      if (e.key==='ArrowLeft'  && i>0)              inputs[i-1].focus();
      if (e.key==='ArrowRight' && i<inputs.length-1) inputs[i+1].focus();
    });
    inp.addEventListener('paste', function(e){
      e.preventDefault();
      var p = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'');
      inputs.forEach(function(b,j){ if(p[j]){b.value=p[j];b.classList.add('filled');} });
      var last = Math.min(p.length,inputs.length)-1;
      if (last>=0) inputs[last].focus();
    });
  });
  var otpForm = document.getElementById('otpForm');
  otpForm && otpForm.addEventListener('submit', function(e){
    var val = Array.from(inputs).map(function(b){return b.value;}).join('');
    if (val.length!==6||!/^\d{6}$/.test(val)){
      e.preventDefault();
      inputs.forEach(function(b){b.classList.add('error');});
      setTimeout(function(){inputs.forEach(function(b){b.classList.remove('error');});},700);
      return;
    }
    var btn=document.getElementById('otpBtn');
    btn.disabled=true;
    document.getElementById('otpBtnIcon').className='fas fa-spinner fa-spin';
    document.getElementById('otpBtnText').textContent='Verifying…';
  });
  var count=60, disp=document.getElementById('countdown'), rbtn=document.getElementById('resendBtn');
  var t=setInterval(function(){
    count--;
    if(disp) disp.textContent=count;
    if(count<=0){
      clearInterval(t);
      if(disp) disp.parentElement.textContent='You can resend now.';
      if(rbtn) rbtn.disabled=false;
    }
  },1000);
})();

/* Step 3 — Password */
(function(){
  var pw1=document.getElementById('pw1'), pw2=document.getElementById('pw2');
  if (!pw1) return;
  pw1.addEventListener('input', function(){
    var v=this.value;
    var r=[v.length>=8,/[A-Z]/.test(v),/[0-9]/.test(v),/[^A-Za-z0-9]/.test(v)];
    ['rule-len','rule-cap','rule-num','rule-sym'].forEach(function(id,i){
      var el=document.getElementById(id); if(!el)return;
      el.className='pw-rule '+(v?(r[i]?'met':'unmet'):'');
      el.querySelector('i').className='fas '+(r[i]?'fa-circle-check':'fa-circle-xmark');
    });
    var score=r.filter(Boolean).length;
    var levels=[{w:'0%',c:'',t:''},{w:'25%',c:'#c0392b',t:'Weak'},{w:'50%',c:'#b07020',t:'Fair'},{w:'75%',c:'#9c6a1a',t:'Good'},{w:'100%',c:'#1e5c3a',t:'Strong ✓'}];
    var lv=v?levels[score]:levels[0];
    var bar=document.getElementById('strengthBar'),lbl=document.getElementById('strengthLabel');
    bar.style.width=lv.w; bar.style.background=lv.c; lbl.textContent=lv.t; lbl.style.color=lv.c;
    if(pw2.value) pw2.dispatchEvent(new Event('input'));
  });
  pw2.addEventListener('input', function(){
    var h=document.getElementById('pw2Hint');
    if(!this.value){h.textContent='';h.className='field-hint';return;}
    var ok=this.value===pw1.value;
    h.textContent=ok?'Passwords match!':'Passwords do not match.';
    h.className='field-hint '+(ok?'ok':'err');
  });
  document.getElementById('resetForm').addEventListener('submit',function(e){
    var p1=pw1.value,p2=pw2.value;
    if(p1.length<8||!/[A-Z]/.test(p1)||!/[0-9]/.test(p1)||!/[^A-Za-z0-9]/.test(p1)||p1!==p2){
      e.preventDefault(); return;
    }
    var btn=document.getElementById('resetBtn');
    btn.disabled=true;
    document.getElementById('resetIcon').className='fas fa-spinner fa-spin';
    document.getElementById('resetTxt').textContent='Saving…';
  });
})();
</script>
</body>
</html>