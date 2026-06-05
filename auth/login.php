<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'parent')  { header("Location: ../parents/parent-dashboard.php");   exit; }
    if ($role === 'teacher') { header("Location: ../teachers/teacher-dashboard.php"); exit; }
    if ($role === 'student') { header("Location: ../students/student-dashboard.php"); exit; }
    header("Location: ../dashboard/dashboard.php"); exit;
}

$error      = '';
$warn_verify = false;   // true → show "verify email" notice

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $email_esc = $conn->real_escape_string($email);

        // Detect whether email_verified column exists
        $col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
        $has_ev    = ($col_check && $col_check->num_rows > 0);

        $ev_select = $has_ev ? ', u.email_verified' : '';
        $sql = "SELECT u.id, u.name, u.email, u.password, u.role_id, r.role_name$ev_select
                FROM users u JOIN roles r ON u.role_id = r.id
                WHERE u.email = '$email_esc' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows === 1) {
            $user    = $result->fetch_assoc();
            $pass_ok = password_verify($password, $user['password']) || ($password === $user['password']);

            if ($pass_ok) {
                // Block login if email not verified (only when column exists)
                if ($has_ev && !$user['email_verified']) {
                    $warn_verify = true;
                    $error = '__verify__'; // sentinel
                } else {
                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['user_name']     = $user['name'];
                    $_SESSION['email']         = $user['email'];
                    $_SESSION['role_id']       = $user['role_id'];
                    $_SESSION['role']          = strtolower($user['role_name']);
                    $_SESSION['last_activity'] = time();
                    switch ($_SESSION['role']) {
                        case 'parent':  header("Location: ../parents/parent-dashboard.php");   break;
                        case 'teacher': header("Location: ../teachers/teacher-dashboard.php"); break;
                        case 'student': header("Location: ../students/student-dashboard.php"); break;
                        default:        header("Location: ../dashboard/dashboard.php");        break;
                    }
                    exit;
                }
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}

$logged_out = isset($_GET['logged_out']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — MindMerge SmartCampus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --sand:     #f5f0e8;
  --sand-2:   #ede6d6;
  --sand-3:   #d9cebc;
  --sand-4:   #b8a98c;
  --brown:    #4a3b20;
  --brown-2:  #6b5430;
  --amber:    #b07020;
  --green:    #2d5c3e;
  --red:      #8c2e2e;
  --ink:      #2b2015;
  --ink-2:    #4a3b28;
  --ink-3:    #7a6a55;
  --white:    #fdfaf5;
}

html, body { height: 100%; font-family: 'DM Sans', sans-serif; background: var(--sand); color: var(--ink); }
body { display: flex; align-items: center; justify-content: center; min-height: 100svh; padding: 24px 16px; }

.wrap { width: 100%; max-width: 420px; animation: rise 0.5s cubic-bezier(.16,1,.3,1) both; }
@keyframes rise { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }

/* ── Logo ── */
.logo-row  { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
.logo-box  { width: 46px; height: 46px; border-radius: 13px; background: var(--brown); display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
.logo-name { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 600; color: var(--ink); line-height: 1; letter-spacing: -0.3px; }
.logo-sub  { font-size: 10.5px; font-weight: 500; letter-spacing: 2.5px; text-transform: uppercase; color: var(--ink-3); margin-top: 3px; }

/* ── Page heading ── */
.page-head  { margin-bottom: 24px; }
.page-title { font-family: 'Fraunces', serif; font-size: 34px; font-weight: 700; color: var(--ink); letter-spacing: -0.5px; line-height: 1.1; margin-bottom: 6px; }
.page-sub   { font-size: 14px; font-weight: 300; color: var(--ink-3); line-height: 1.5; }

/* ── Alerts ── */
.alert {
  display: flex; align-items: center; gap: 10px;
  border-radius: 10px; padding: 12px 14px;
  font-size: 14px; line-height: 1.5; margin-bottom: 20px;
  animation: drop .3s cubic-bezier(.16,1,.3,1) both;
}
@keyframes drop { from{opacity:0;transform:translateY(-5px)} to{opacity:1;transform:none} }
.alert i { font-size: 15px; flex-shrink: 0; }
.alert-success { background: #eaf4ed; border: 1px solid #aed6b8; color: var(--green); }
.alert-error   { background: #faeaea; border: 1px solid #e0aaaa; color: var(--red); }
.alert-warn    { background: #fdf5e8; border: 1px solid #ddc890; color: var(--amber); align-items: flex-start; }

/* Verify notice */
.verify-box {
  background: #fdf5e8; border: 1px solid #ddc890;
  border-radius: 12px; padding: 18px 16px; margin-bottom: 20px;
  animation: drop .3s cubic-bezier(.16,1,.3,1) both;
}
.verify-box h4 { font-size: 15px; color: var(--ink); margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
.verify-box p  { font-size: 13.5px; color: var(--ink-2); line-height: 1.6; margin-bottom: 12px; }
.verify-box a  {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--brown); color: var(--sand);
  padding: 9px 18px; border-radius: 8px;
  font-size: 13.5px; font-weight: 600; text-decoration: none;
  transition: background .18s;
}
.verify-box a:hover { background: var(--ink); }

/* No account box */
.no-acct {
  background: #fdf5e8; border: 1px solid #ddc890;
  border-radius: 10px; padding: 16px 14px; text-align: center;
  margin-bottom: 20px; animation: drop .3s cubic-bezier(.16,1,.3,1) both;
}
.no-acct p  { font-size: 14px; color: var(--ink-2); line-height: 1.6; margin-bottom: 12px; }
.no-acct strong { color: var(--ink); }
.no-acct a  { display: inline-flex; align-items: center; gap: 7px; background: var(--brown); color: var(--sand); padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; transition: background .18s; }
.no-acct a:hover { background: var(--ink); }

/* ── Fields ── */
.fields { display: flex; flex-direction: column; gap: 16px; }

.field-label { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; letter-spacing: 1.3px; text-transform: uppercase; color: var(--ink-3); margin-bottom: 8px; }
.field-label i { font-size: 10.5px; }

.input-wrap { position: relative; display: flex; align-items: center; }

.input-icon { position: absolute; left: 14px; z-index: 2; font-size: 14px; color: var(--sand-4); pointer-events: none; transition: color .2s; }
.input-wrap:focus-within .input-icon { color: var(--amber); }

.field-input {
  width: 100%; background: var(--white); border: 1.5px solid var(--sand-3); border-radius: 10px;
  padding: 13px 44px 13px 42px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 400;
  color: #2b2015 !important; -webkit-text-fill-color: #2b2015 !important;
  outline: none; transition: border-color .2s, box-shadow .2s; caret-color: var(--amber);
}
.field-input::placeholder { color: #b8a98c !important; -webkit-text-fill-color: #b8a98c !important; font-size: 14px; }
.field-input:focus { border-color: var(--amber); box-shadow: 0 0 0 3px rgba(176,112,32,0.10); }
.field-input:-webkit-autofill, .field-input:-webkit-autofill:focus {
  -webkit-text-fill-color: #2b2015 !important;
  -webkit-box-shadow: 0 0 0 100px #fdfaf5 inset !important;
  border-color: var(--amber); transition: background-color 9999s ease 0s;
}

.eye-btn { position: absolute; right: 13px; z-index: 2; background: none; border: none; cursor: pointer; color: var(--sand-4); font-size: 14px; padding: 4px; transition: color .2s; }
.eye-btn:hover { color: var(--amber); }

/* ── Remember + forgot ── */
.meta-row { display: flex; align-items: center; justify-content: space-between; margin-top: 12px; }
.remember { display: flex; align-items: center; gap: 9px; cursor: pointer; user-select: none; }
.remember input[type=checkbox] { display: none; }
.chk { width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid var(--sand-3); background: var(--white); display: flex; align-items: center; justify-content: center; transition: all .2s; flex-shrink: 0; }
.remember input:checked ~ .chk { background: var(--brown); border-color: var(--brown); }
.chk i { color: #fff; font-size: 9px; opacity: 0; transition: opacity .15s; }
.remember input:checked ~ .chk i { opacity: 1; }
.remember-lbl { font-size: 13.5px; color: var(--ink-2); }
.forgot { font-size: 13.5px; color: var(--ink-3); text-decoration: none; display: flex; align-items: center; gap: 5px; transition: color .2s; }
.forgot:hover { color: var(--amber); }

/* ── Sign in button ── */
.btn-login-main {
  width: 100%; margin-top: 22px; background: var(--brown); border: none; border-radius: 10px;
  padding: 15px; font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 600;
  color: var(--sand); cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 9px;
  transition: background .2s, transform .15s; letter-spacing: 0.1px;
}
.btn-login-main:hover  { background: var(--ink); transform: translateY(-1px); }
.btn-login-main:active { transform: scale(.99); }
.btn-login-main:disabled { opacity: .6; cursor: not-allowed; transform: none; }

/* ── Divider ── */
.divider { display: flex; align-items: center; gap: 12px; margin: 20px 0 16px; }
.div-line { flex: 1; height: 1px; background: var(--sand-3); }
.div-txt  { font-size: 12.5px; color: var(--ink-3); white-space: nowrap; }

/* ── Create account ── */
.btn-register {
  width: 100%; background: transparent; border: 1.5px solid var(--sand-3);
  border-radius: 10px; padding: 14px; font-family: 'DM Sans', sans-serif;
  font-size: 15.5px; font-weight: 600; color: var(--brown); text-decoration: none;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: all .2s;
}
.btn-register:hover { background: var(--sand-2); border-color: var(--brown-2); }

/* ── Password hint ── */
.pw-hint { font-size: 11.5px; color: var(--ink-3); margin-top: 5px; min-height: 16px; }
.pw-hint.err { color: var(--red); }
.pw-hint.ok  { color: #2d7a4f; }

@media (max-width: 460px) {
  .page-title { font-size: 28px; }
  .meta-row   { flex-direction: column; align-items: flex-start; gap: 10px; }
}
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

  <!-- Heading -->
  <div class="page-head">
    <div class="page-title">Sign in</div>
    <div class="page-sub">Welcome back — enter your credentials to continue.</div>
  </div>

  <!-- Logged out -->
  <?php if ($logged_out): ?>
    <div class="alert alert-success">
      <i class="fas fa-circle-check"></i>
      <span>You've been signed out successfully.</span>
    </div>
  <?php endif; ?>

  <!-- Email not verified -->
  <?php if ($warn_verify): ?>
    <div class="verify-box">
      <h4><i class="fas fa-envelope-circle-check"></i> Email not verified</h4>
      <p>You haven't verified the email address for this account yet.
         Please complete registration to verify your email before signing in.</p>
      <a href="register.php"><i class="fas fa-arrow-right"></i> Complete Verification</a>
    </div>
  <?php elseif ($error && str_contains($error, 'No account found')): ?>
    <div class="no-acct">
      <p>No account found for <strong><?= htmlspecialchars($_POST['email'] ?? '') ?></strong>.<br>Would you like to create one?</p>
      <a href="register.php"><i class="fas fa-user-plus"></i> Create Account</a>
    </div>
  <?php elseif ($error && $error !== '__verify__'): ?>
    <div class="alert alert-error">
      <i class="fas fa-triangle-exclamation"></i>
      <span><?= htmlspecialchars($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Form -->
  <form method="POST" id="loginForm" autocomplete="on" novalidate>

    <div class="fields">

      <!-- Email -->
      <div>
        <div class="field-label"><i class="fas fa-envelope"></i> Email Address</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-at"></i>
          <input class="field-input" type="email" name="email" id="lEmail"
            placeholder="your@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required autocomplete="username" spellcheck="false">
        </div>
        <div class="pw-hint" id="emailHint"></div>
      </div>

      <!-- Password -->
      <div>
        <div class="field-label"><i class="fas fa-lock"></i> Password</div>
        <div class="input-wrap">
          <i class="input-icon fas fa-key"></i>
          <input class="field-input" type="password" name="password" id="pwdInput"
            placeholder="Enter your password"
            required autocomplete="current-password">
          <button type="button" class="eye-btn" id="eyeBtn">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
        <div class="pw-hint" id="pwHint"></div>
      </div>

    </div>

    <!-- Remember + forgot -->
    <div class="meta-row">
      <label class="remember">
        <input type="checkbox" name="remember">
        <div class="chk"><i class="fas fa-check"></i></div>
        <span class="remember-lbl">Remember me</span>
      </label>
      <a href="forgot-password.php" class="forgot">
        <i class="fas fa-rotate-left"></i> Forgot password?
      </a>
    </div>

    <!-- Sign in -->
    <button type="submit" class="btn-login-main" id="loginBtn">
      <i class="fas fa-arrow-right-to-bracket" id="btnIcon"></i>
      <span id="btnText">Sign In</span>
    </button>

  </form>

  <!-- Divider -->
  <div class="divider">
    <div class="div-line"></div>
    <span class="div-txt">New to MindMerge?</span>
    <div class="div-line"></div>
  </div>

  <!-- Create account -->
  <a href="register.php" class="btn-register">
    <i class="fas fa-user-plus"></i> Create an Account
  </a>



</div><!-- .wrap -->

<script>
/* ── Eye toggle ── */
document.getElementById('eyeBtn').addEventListener('click', function() {
  var inp = document.getElementById('pwdInput');
  var ico = document.getElementById('eyeIcon');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
});

/* ── Live email hint ── */
document.getElementById('lEmail').addEventListener('input', function() {
  var hint = document.getElementById('emailHint');
  var v = this.value.trim();
  if (!v) { hint.textContent = ''; hint.className = 'pw-hint'; return; }
  var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  hint.textContent = ok ? '' : 'Enter a valid email address.';
  hint.className   = 'pw-hint' + (ok ? '' : ' err');
});

/* ── Live password length hint ── */
document.getElementById('pwdInput').addEventListener('input', function() {
  var hint = document.getElementById('pwHint');
  var v = this.value;
  if (!v) { hint.textContent = ''; hint.className = 'pw-hint'; return; }
  var ok = v.length >= 6;
  hint.textContent = ok ? '' : 'Password must be at least 6 characters.';
  hint.className   = 'pw-hint' + (ok ? '' : ' err');
});

/* ── Submit ── */
document.getElementById('loginForm').addEventListener('submit', function(e) {
  var email = document.getElementById('lEmail').value.trim();
  var pwd   = document.getElementById('pwdInput').value;
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    e.preventDefault();
    var h = document.getElementById('emailHint');
    h.textContent = 'Please enter a valid email address.';
    h.className   = 'pw-hint err';
    document.getElementById('lEmail').focus();
    return;
  }
  if (!pwd || pwd.length < 6) {
    e.preventDefault();
    var h2 = document.getElementById('pwHint');
    h2.textContent = 'Password must be at least 6 characters.';
    h2.className   = 'pw-hint err';
    document.getElementById('pwdInput').focus();
    return;
  }
  var btn  = document.getElementById('loginBtn');
  var icon = document.getElementById('btnIcon');
  var txt  = document.getElementById('btnText');
  btn.disabled    = true;
  icon.className  = 'fas fa-spinner fa-spin';
  txt.textContent = 'Signing in…';
});
</script>
</body>
</html>