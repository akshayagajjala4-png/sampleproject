<?php
date_default_timezone_set('Asia/Kolkata');
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['role']      ?? '';

// Only admin can access
if ($userRole !== 'admin') {
    header("Location: ../dashboard/dashboard.php"); exit;
}

$success = '';
$error   = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Change Site Name ──
    if ($action === 'site') {
        $site_name = trim($_POST['site_name'] ?? '');
        if (empty($site_name)) {
            $error = 'Site name cannot be empty.';
        } else {
            // Save to a simple config file
            $config = ['site_name' => $site_name];
            file_put_contents(__DIR__ . '/../config/site_config.json',
                json_encode($config, JSON_PRETTY_PRINT));
            $success = 'Site name updated successfully.';
        }
    }

    // ── Change Timezone ──
    if ($action === 'timezone') {
        $tz = trim($_POST['timezone'] ?? 'Asia/Kolkata');
        $config = file_exists(__DIR__ . '/../config/site_config.json')
            ? json_decode(file_get_contents(__DIR__ . '/../config/site_config.json'), true)
            : [];
        $config['timezone'] = $tz;
        file_put_contents(__DIR__ . '/../config/site_config.json',
            json_encode($config, JSON_PRETTY_PRINT));
        $success = 'Timezone updated successfully.';
    }

    // ── Change Admin Password ──
    if ($action === 'password') {
        $current  = trim($_POST['current_password']  ?? '');
        $new_pass = trim($_POST['new_password']       ?? '');
        $confirm  = trim($_POST['confirm_password']   ?? '');

        if (empty($current) || empty($new_pass) || empty($confirm)) {
            $error = 'All password fields are required.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $uid = (int)$_SESSION['user_id'];
            $res = mysqli_query($conn,
                "SELECT password FROM users WHERE id = $uid LIMIT 1");
            if ($res && $row = mysqli_fetch_assoc($res)) {
                if (password_verify($current, $row['password'])) {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $hashed_e = mysqli_real_escape_string($conn, $hashed);
                    mysqli_query($conn,
                        "UPDATE users SET password='$hashed_e' WHERE id=$uid");
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Current password is incorrect.';
                }
            } else {
                $error = 'User not found.';
            }
        }
    }
}

// Load current config
$siteConfig = file_exists(__DIR__ . '/../config/site_config.json')
    ? json_decode(file_get_contents(__DIR__ . '/../config/site_config.json'), true)
    : [];
$siteName    = $siteConfig['site_name'] ?? 'MindMerge SmartCampus';
$siteTz      = $siteConfig['timezone']  ?? 'Asia/Kolkata';

// DB info
$dbInfo = [
    'host'    => defined('DB_HOST') ? DB_HOST : 'localhost',
    'name'    => defined('DB_NAME') ? DB_NAME : 'mindmerge_db',
    'version' => '',
];
$vRes = mysqli_query($conn, "SELECT VERSION() as v");
if ($vRes) $dbInfo['version'] = mysqli_fetch_assoc($vRes)['v'] ?? '';

$timezones = [
    'Asia/Kolkata'    => 'India (IST, UTC+5:30)',
    'Asia/Dubai'      => 'Dubai (GST, UTC+4)',
    'America/New_York'=> 'New York (EST/EDT)',
    'Europe/London'   => 'London (GMT/BST)',
    'Asia/Singapore'  => 'Singapore (SGT, UTC+8)',
    'UTC'             => 'UTC',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Settings — MindMerge SmartCampus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f0f4f8; margin: 0; font-family: 'Segoe UI', sans-serif; }

/* ── SIDEBAR ── */
.sidebar {
    width: 250px; background: #1a1a2e;
    min-height: 100vh; position: fixed;
    top: 0; left: 0; padding-top: 20px;
    z-index: 100; overflow-y: auto;
}
.sidebar-brand {
    color: white; font-size: 18px; font-weight: bold;
    padding: 15px 20px;
    border-bottom: 1px solid #ffffff20;
    margin-bottom: 10px;
}
.sidebar a {
    display: block; color: #ffffffaa;
    padding: 12px 20px; text-decoration: none;
    font-size: 14px; transition: 0.3s;
}
.sidebar a:hover { background: #ffffff15; color: white; padding-left: 25px; }
.sidebar a.active {
    background: #ffffff20; color: white;
    border-left: 3px solid #ffc107;
}
.sidebar .menu-title {
    color: #ffffff50; font-size: 11px;
    padding: 10px 20px 5px;
    text-transform: uppercase; letter-spacing: 1px;
}

/* ── MAIN ── */
.main-content { margin-left: 250px; }
.top-navbar {
    background: white; padding: 15px 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex; justify-content: space-between; align-items: center;
}

/* ── SETTINGS CARDS ── */
.settings-card {
    background: white; border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    padding: 28px; margin-bottom: 24px;
    border-left: 4px solid #1a1a2e;
    transition: 0.3s;
}
.settings-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
.settings-card .card-icon {
    width: 46px; height: 46px;
    border-radius: 12px; background: #1a1a2e;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 16px;
}
.settings-card h6 {
    font-size: 16px; font-weight: 700;
    color: #1a1a2e; margin-bottom: 4px;
}
.settings-card .sub {
    font-size: 12.5px; color: #9ca3af; margin-bottom: 20px;
}

/* Info grid */
.info-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.info-item {
    background: #f8fafc; border-radius: 10px;
    padding: 14px 16px; border: 1px solid #e5e7eb;
}
.info-label { font-size: 11px; font-weight: 700; color: #9ca3af;
              letter-spacing: 1px; text-transform: uppercase; margin-bottom: 5px; }
.info-value { font-size: 14px; font-weight: 600; color: #1a1a2e; }

.form-control, .form-select {
    border-radius: 9px; border: 1.5px solid #e5e7eb;
    font-size: 14px; padding: 10px 14px;
    transition: border-color .2s, box-shadow .2s;
}
.form-control:focus, .form-select:focus {
    border-color: #1a1a2e; box-shadow: 0 0 0 3px rgba(26,26,46,0.08);
}
.btn-save {
    background: #1a1a2e; color: white; border: none;
    padding: 10px 24px; border-radius: 9px;
    font-size: 14px; font-weight: 600;
    transition: 0.2s; display: inline-flex; align-items: center; gap: 7px;
}
.btn-save:hover { background: #0f3460; color: white; transform: translateY(-1px); }

.alert { border-radius: 10px; font-size: 14px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">🧠 MindMerge</div>

    <div class="menu-title">Main Menu</div>
    <a href="../dashboard/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>

    <div class="menu-title">Academic</div>
    <a href="../students/student-list.php"><i class="bi bi-people"></i> Students</a>
    <a href="../teachers/teacher-list.php"><i class="bi bi-person-badge"></i> Teachers</a>
    <a href="../attendance/mark-attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="../timetable/timetable.php"><i class="bi bi-clock"></i> Timetable</a>
    <a href="../exams/exam-schedule.php"><i class="bi bi-pencil-square"></i> Exams</a>

    <div class="menu-title">Finance</div>
    <a href="../fees/fees.php"><i class="bi bi-cash"></i> Fees</a>

    <div class="menu-title">Communication</div>
    <a href="../notifications/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
    <a href="../reports/reports.php"><i class="bi bi-bar-chart"></i> Reports</a>

    <div class="menu-title">Admin</div>
    <a href="../admin/admin.php"><i class="bi bi-shield-check"></i> Roles & Permissions</a>
    <a href="../admin/settings.php" class="active"><i class="bi bi-gear"></i> System Settings</a>
    <a href="../admin/manage-users.php"><i class="bi bi-people-fill"></i> Manage Users</a>

    <div style="padding:20px 10px 10px;">
        <a href="../auth/logout.php"
           style="display:block;padding:10px 15px;color:#ff6b6b;
                  text-decoration:none;border-radius:8px;border:1px solid #ff6b6b;font-size:14px;">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main-content">
    <div class="top-navbar">
        <h5 class="mb-0">⚙️ System Settings</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= htmlspecialchars($userName) ?></span>
            <span class="badge bg-warning text-dark text-capitalize px-3 py-2">
                <?= htmlspecialchars($userRole) ?>
            </span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container-fluid p-4" style="max-width:900px;">

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- System Info -->
        <div class="settings-card">
            <div class="card-icon">🖥️</div>
            <h6>System Information</h6>
            <p class="sub">Current environment and database details</p>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">PHP Version</div>
                    <div class="info-value"><?= phpversion() ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">MySQL Version</div>
                    <div class="info-value"><?= htmlspecialchars($dbInfo['version']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Database</div>
                    <div class="info-value"><?= htmlspecialchars($dbInfo['name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Server Time</div>
                    <div class="info-value"><?= date('d M Y, h:i A') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Timezone</div>
                    <div class="info-value"><?= htmlspecialchars($siteTz) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Site Name</div>
                    <div class="info-value"><?= htmlspecialchars($siteName) ?></div>
                </div>
            </div>
        </div>

        <!-- Site Settings -->
        <div class="settings-card">
            <div class="card-icon">🏫</div>
            <h6>Site Settings</h6>
            <p class="sub">Update the site name displayed across the platform</p>
            <form method="POST">
                <input type="hidden" name="action" value="site">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Site Name</label>
                    <input type="text" name="site_name" class="form-control"
                           value="<?= htmlspecialchars($siteName) ?>" required>
                </div>
                <button type="submit" class="btn-save">
                    <i class="bi bi-floppy"></i> Save Site Name
                </button>
            </form>
        </div>

        <!-- Timezone -->
        <div class="settings-card">
            <div class="card-icon">🌐</div>
            <h6>Timezone</h6>
            <p class="sub">Set the timezone used for dates and times across the system</p>
            <form method="POST">
                <input type="hidden" name="action" value="timezone">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Select Timezone</label>
                    <select name="timezone" class="form-select">
                        <?php foreach ($timezones as $tz => $label): ?>
                        <option value="<?= $tz ?>" <?= $siteTz === $tz ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-save">
                    <i class="bi bi-globe"></i> Save Timezone
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="settings-card">
            <div class="card-icon">🔐</div>
            <h6>Change Password</h6>
            <p class="sub">Update your admin account password</p>
            <form method="POST">
                <input type="hidden" name="action" value="password">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Current Password</label>
                    <input type="password" name="current_password" class="form-control"
                           placeholder="Enter current password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">New Password</label>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="Min. 6 characters" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control"
                           placeholder="Re-enter new password" required>
                </div>
                <button type="submit" class="btn-save">
                    <i class="bi bi-shield-lock"></i> Update Password
                </button>
            </form>
        </div>

    </div>
</div>

</body>
</html>
