<?php
date_default_timezone_set('Asia/Kolkata');
require_once '../auth/session.php';
require_once '../config/database.php';
require_once 'stats.php';
redirectIfNotLoggedIn();

$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['role']      ?? '';
$greeting = (function() {
    $h = (int)date('H');
    if ($h < 12) return 'Good morning';
    if ($h < 17) return 'Good afternoon';
    return 'Good evening';
})();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — MindMerge SmartCampus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f0f4f8; margin: 0; font-family: 'Segoe UI', sans-serif; }

/* ── SIDEBAR (original theme) ── */
.sidebar {
    width: 250px;
    background: #1a1a2e;
    min-height: 100vh;
    position: fixed;
    top: 0; left: 0;
    padding-top: 20px;
    z-index: 100;
    overflow-y: auto;
}
.sidebar-brand {
    color: white;
    font-size: 18px;
    font-weight: bold;
    padding: 15px 20px;
    border-bottom: 1px solid #ffffff20;
    margin-bottom: 10px;
}
.sidebar a {
    display: block;
    color: #ffffffaa;
    padding: 12px 20px;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}
.sidebar a:hover {
    background: #ffffff15;
    color: white;
    padding-left: 25px;
}
.sidebar a.active {
    background: #ffffff20;
    color: white;
    border-left: 3px solid #ffc107;
}
.sidebar .menu-title {
    color: #ffffff50;
    font-size: 11px;
    padding: 10px 20px 5px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.sidebar .notif-count {
    float: right;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 20px;
    margin-top: 1px;
}

/* ── MAIN CONTENT ── */
.main-content { margin-left: 250px; }

/* ── TOP NAVBAR ── */
.top-navbar {
    background: white;
    padding: 15px 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.top-navbar h5 { margin: 0; font-weight: 700; }

/* ── WELCOME BANNER ── */
.welcome-banner {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
    border-radius: 15px;
    padding: 24px 28px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 20px rgba(26,26,46,0.3);
    position: relative;
    overflow: hidden;
}
.welcome-banner::after {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,193,7,0.08);
    pointer-events: none;
}
.welcome-text h4 {
    color: white;
    font-weight: 700;
    margin-bottom: 6px;
    font-size: 22px;
}
.welcome-text p { color: #ffffffaa; margin: 0; font-size: 14px; }
.welcome-actions { display: flex; gap: 10px; }
.btn-mark {
    background: #ffc107;
    color: #1a1a2e;
    border: none;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    display: flex; align-items: center; gap: 6px;
    transition: 0.2s;
}
.btn-mark:hover { background: #e0a800; color: #1a1a2e; transform: translateY(-1px); }
.btn-add {
    background: rgba(255,255,255,0.12);
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: flex; align-items: center; gap: 6px;
    transition: 0.2s;
}
.btn-add:hover { background: rgba(255,255,255,0.2); color: white; transform: translateY(-1px); }

/* ── STAT CARDS (original style, enhanced) ── */
.stat-card {
    background: white;
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    padding: 25px;
    transition: 0.3s;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.stat-icon { font-size: 45px; }
.stat-value {
    font-size: 32px;
    font-weight: 800;
    line-height: 1;
    margin: 8px 0 4px;
}
.stat-label { font-size: 13px; color: #6b7280; }
.stat-bar {
    height: 4px;
    border-radius: 2px;
    background: #f0f4f8;
    margin: 14px 0 14px;
    overflow: hidden;
}
.stat-bar-fill { height: 100%; border-radius: 2px; transition: width 1s ease; }
.stat-trend {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 11px; font-weight: 700;
    padding: 2px 8px; border-radius: 20px;
    margin-bottom: 6px;
}
.trend-up   { background: #dcfce7; color: #16a34a; }
.trend-new  { background: #fef3c7; color: #d97706; }

/* Section headings */
.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}

/* Panel cards */
.panel-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    padding: 24px;
    height: 100%;
    transition: 0.3s;
}
.panel-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 18px;
}
.panel-title { font-size: 15px; font-weight: 700; color: #1a1a2e; }
.panel-sub   { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.panel-link  {
    font-size: 12px; font-weight: 600; color: #1a1a2e;
    text-decoration: none; padding: 5px 12px;
    border: 1px solid #e5e7eb; border-radius: 7px;
    transition: 0.2s; white-space: nowrap;
}
.panel-link:hover { background: #f9fafb; color: #1a1a2e; }

/* Activity items */
.activity-item {
    padding: 10px 12px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: 0.15s;
}
.activity-item:hover { background: #f9fafb; }
.act-avatar {
    width: 36px; height: 36px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
    flex-shrink: 0;
}
.act-name  { font-size: 14px; font-weight: 600; color: #111827; }
.act-meta  { font-size: 12px; color: #9ca3af; }
.act-pill  {
    font-size: 10.5px; font-weight: 700;
    padding: 2px 9px; border-radius: 20px;
    margin-left: auto; flex-shrink: 0;
}
.pill-blue  { background: #dbeafe; color: #2563eb; }
.pill-green { background: #dcfce7; color: #16a34a; }

/* Class chips */
.class-chip {
    display: inline-flex;
    font-size: 12.5px; font-weight: 600;
    padding: 5px 13px; border-radius: 8px;
    margin: 3px; transition: 0.15s; cursor: default;
}
.class-chip:hover { transform: translateY(-2px); }
.chip-1 { background: #1a1a2e; color: white; }
.chip-2 { background: #16213e; color: #ffc107; }
.chip-3 { background: #0f3460; color: white; }

/* Donut */
.donut-wrap {
    display: flex; align-items: center; justify-content: center;
    position: relative; margin: 4px 0 16px;
}
.donut-center {
    position: absolute; text-align: center; pointer-events: none;
}
.donut-pct  { font-size: 26px; font-weight: 800; color: #1a1a2e; line-height: 1; }
.donut-sub  { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.legend-row { display: flex; justify-content: center; gap: 20px; }
.legend-item { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: #6b7280; }
.legend-item strong { color: #111827; }
.dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

/* Chart area */
.chart-area { position: relative; height: 200px; }

/* No data */
.no-data { font-size: 13px; color: #9ca3af; padding: 8px 0; }

/* Counter animation class */
.counter { display: inline-block; }
</style>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════ -->
<div class="sidebar">
    <div class="sidebar-brand">🧠 MindMerge</div>

    <div class="menu-title">Main Menu</div>
    <a href="dashboard.php" class="active">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="menu-title">Academic</div>
    <a href="../students/student-list.php">
        <i class="bi bi-people"></i> Students
    </a>
    <a href="../teachers/teacher-list.php">
        <i class="bi bi-person-badge"></i> Teachers
    </a>
    <a href="../attendance/mark-attendance.php">
        <i class="bi bi-calendar-check"></i> Attendance
    </a>
    <a href="../timetable/timetable.php">
        <i class="bi bi-clock"></i> Timetable
    </a>
    <a href="../exams/exam-schedule.php">
        <i class="bi bi-pencil-square"></i> Exams
    </a>

    <div class="menu-title">Finance</div>
    <a href="../fees/fees.php">
        <i class="bi bi-cash"></i> Fees
    </a>

    <div class="menu-title">Communication</div>
    <a href="../notifications/notifications.php">
        <i class="bi bi-bell"></i> Notifications
        <?php if(!empty($unreadNotifications) && $unreadNotifications > 0): ?>
        <span class="notif-count"><?= $unreadNotifications ?></span>
        <?php endif; ?>
    </a>
    <a href="../reports/reports.php">
        <i class="bi bi-bar-chart"></i> Reports
    </a>

    <?php if ($userRole === 'admin'): ?>
    <div class="menu-title">Admin</div>
    <a href="../admin/admin.php">
        <i class="bi bi-shield-check"></i> Roles & Permissions
    </a>
    <a href="../admin/settings.php">
        <i class="bi bi-gear"></i> System Settings
    </a>
    <a href="../admin/manage-users.php">
        <i class="bi bi-people-fill"></i> Manage Users
    </a>
    <?php endif; ?>

    <div style="padding: 20px 10px 10px;">
        <a href="../auth/logout.php"
           style="display:block;padding:10px 15px;color:#ff6b6b;
                  text-decoration:none;border-radius:8px;
                  border:1px solid #ff6b6b; font-size:14px;">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</div>

<!-- ══ MAIN CONTENT ═════════════════════════════ -->
<div class="main-content">

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h5>📊 Dashboard</h5>
        <div class="d-flex align-items-center gap-3">
            <span style="font-size:14px;">👤 <?= htmlspecialchars($userName) ?></span>
            <span class="badge bg-warning text-dark text-capitalize px-3 py-2">
                <?= htmlspecialchars($userRole) ?>
            </span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h4><?= $greeting ?>, <?= htmlspecialchars($userName) ?>! 👋</h4>
                <p>
                    <i class="bi bi-calendar3"></i> <?= date('l, d F Y') ?>
                    &nbsp;·&nbsp;
                    <i class="bi bi-clock"></i> <?= date('h:i A') ?>
                    &nbsp;·&nbsp;
                    <i class="bi bi-shield-check"></i> <?= ucfirst($userRole) ?>
                </p>
            </div>
            <div class="welcome-actions">
                <a href="../attendance/mark-attendance.php" class="btn-mark">
                    <i class="bi bi-calendar-check"></i> Mark Attendance
                </a>
                <a href="../students/add-student.php" class="btn-add">
                    <i class="bi bi-plus-lg"></i> Add Student
                </a>
            </div>
        </div>

        <!-- Widgets -->
        <?php require_once 'widgets.php'; ?>

    </div>
</div>

</body>
</html>