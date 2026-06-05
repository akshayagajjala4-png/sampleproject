<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (getUserRole() !== "student") { header("Location: ../auth/login.php"); exit(); }

$userName = $_SESSION['user_name'] ?? 'Student';
$userRole = $_SESSION['role']      ?? 'student';
$userId   = $_SESSION['user_id']   ?? 0;
$userEmail= $_SESSION['email']     ?? 'N/A';

// ── Try to find the student record ──────────────────────────────────────────
// Attempt 1: column named user_id
$studentData = false;
$result = $conn->query("SHOW COLUMNS FROM students LIKE 'user_id'");
if ($result && $result->num_rows > 0) {
    $studentData = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM students WHERE user_id = $userId LIMIT 1"));
}

// Attempt 2: column named id (same as users.id)
if (!$studentData) {
    $r2 = $conn->query("SELECT * FROM students WHERE id = $userId LIMIT 1");
    if ($r2 && $r2->num_rows > 0) {
        $studentData = $r2->fetch_assoc();
    }
}

// Attempt 3: match by email if table has email column
if (!$studentData) {
    $emailEsc = $conn->real_escape_string($userEmail);
    $r3 = $conn->query("SHOW COLUMNS FROM students LIKE 'email'");
    if ($r3 && $r3->num_rows > 0) {
        $r4 = $conn->query("SELECT * FROM students WHERE email='$emailEsc' LIMIT 1");
        if ($r4 && $r4->num_rows > 0) $studentData = $r4->fetch_assoc();
    }
}

$studentClass   = $studentData['class']   ?? $studentData['class_name'] ?? 'N/A';
$studentSection = $studentData['section'] ?? 'N/A';
$studentId      = $studentData['id']      ?? 0;

// ── Attendance ───────────────────────────────────────────────────────────────
$attendanceCount  = 0;
$totalDays        = 0;
$attendancePct    = 0;
if ($studentId) {
    $aRes = $conn->query(
        "SELECT COUNT(*) as total FROM attendance
         WHERE student_id = $studentId AND status = 'Present'");
    if ($aRes) $attendanceCount = $aRes->fetch_assoc()['total'];

    $tRes = $conn->query(
        "SELECT COUNT(*) as total FROM attendance WHERE student_id = $studentId");
    if ($tRes) $totalDays = $tRes->fetch_assoc()['total'];

    $attendancePct = $totalDays > 0
        ? round(($attendanceCount / $totalDays) * 100) : 0;
}

// ── Exam results ─────────────────────────────────────────────────────────────
$examCount = 0;
if ($studentId) {
    $eRes = $conn->query(
        "SELECT COUNT(*) as total FROM results WHERE student_id = $studentId");
    if ($eRes) $examCount = $eRes->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --purple:#7367f0; --purple-light:#9e95f5; }
        body { background:#f0f4f8; margin:0; font-family:'Segoe UI',sans-serif; }

        /* ── Sidebar ── */
        .sidebar {
            width:250px; background:#1a1a2e;
            min-height:100vh; position:fixed;
            top:0; left:0; padding-top:20px; z-index:100;
        }
        .sidebar-brand {
            color:#fff; font-size:18px; font-weight:800;
            padding:15px 20px; border-bottom:1px solid #ffffff20; margin-bottom:10px;
        }
        .sidebar a {
            display:block; color:#ffffffaa; padding:12px 20px;
            text-decoration:none; font-size:14px; transition:.3s;
        }
        .sidebar a:hover { background:#ffffff15; color:#fff; padding-left:25px; }
        .sidebar a.active { background:#ffffff20; color:#fff; border-left:3px solid var(--purple); }
        .sidebar .menu-title {
            color:#ffffff50; font-size:11px; padding:10px 20px 5px;
            text-transform:uppercase; letter-spacing:1px;
        }
        .sidebar-footer {
            position:absolute; bottom:0; width:100%;
            padding:15px 20px; border-top:1px solid #ffffff15;
        }

        /* ── Main ── */
        .main-content { margin-left:250px; }
        .top-navbar {
            background:#fff; padding:15px 25px;
            box-shadow:0 2px 10px rgba(0,0,0,.08);
            display:flex; justify-content:space-between; align-items:center;
            position:sticky; top:0; z-index:99;
        }

        /* ── Cards ── */
        .stat-card {
            background:#fff; border-radius:16px; border:none;
            box-shadow:0 4px 15px rgba(0,0,0,.07); padding:24px; transition:.3s;
        }
        .stat-card:hover { transform:translateY(-4px); box-shadow:0 10px 25px rgba(0,0,0,.13); }
        .stat-icon { font-size:42px; line-height:1; }

        .welcome-banner {
            background:linear-gradient(135deg,var(--purple),#4a3fbf);
            border-radius:16px; padding:28px 32px; color:#fff; margin-bottom:24px;
            position:relative; overflow:hidden;
        }
        .welcome-banner::after {
            content:'🎓'; position:absolute; right:30px; top:50%;
            transform:translateY(-50%); font-size:80px; opacity:.15;
        }

        /* ── No-record notice ── */
        .notice-bar {
            background:#fff8e1; border-left:4px solid #ffc107;
            border-radius:10px; padding:12px 16px;
            font-size:13px; color:#7a5800; margin-bottom:20px;
            display:flex; align-items:center; gap:10px;
        }

        .info-row {
            padding:10px 0; border-bottom:1px solid #f0f0f0;
            display:flex; justify-content:space-between;
        }
        .info-row:last-child { border-bottom:none; }

        /* ── Progress ring ── */
        .ring-wrap { position:relative; width:70px; height:70px; margin:0 auto 8px; }
        .ring-wrap svg { transform:rotate(-90deg); }
        .ring-bg  { fill:none; stroke:#f0f0f0; stroke-width:6; }
        .ring-val { fill:none; stroke:#28c76f; stroke-width:6;
                    stroke-linecap:round; transition:stroke-dashoffset .6s; }
        .ring-text {
            position:absolute; inset:0;
            display:flex; align-items:center; justify-content:center;
            font-size:13px; font-weight:700; color:#1a1f2e;
        }

        @media(max-width:768px){
            .sidebar{display:none;}
            .main-content{margin-left:0;}
        }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<div class="sidebar">
    <div class="sidebar-brand">🧠 MindMerge</div>
    <div class="menu-title">Main Menu</div>
    <a href="student-dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <div class="menu-title">My Academics</div>
    <a href="../attendance/my-attendance.php"><i class="bi bi-calendar-check me-2"></i>My Attendance</a>
    <a href="../exams/my-results.php"><i class="bi bi-bar-chart me-2"></i>My Results</a>
    <a href="../timetable/timetable.php"><i class="bi bi-clock me-2"></i>Timetable</a>
    <div class="menu-title">Other</div>
    <a href="../fees/my-fees.php"><i class="bi bi-cash me-2"></i>My Fees</a>
    <a href="../notifications/notifications.php"><i class="bi bi-bell me-2"></i>Notifications</a>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" style="color:#ff6b6b;padding:0">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
    </div>
</div>

<!-- ── Main Content ── -->
<div class="main-content">
    <div class="top-navbar">
        <h5 class="mb-0 fw-bold">📊 Student Dashboard</h5>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted" style="font-size:14px">👤 <?= htmlspecialchars($userName) ?></span>
            <span class="badge" style="background:var(--purple)">Student</span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <!-- No student record warning -->
        <?php if (!$studentData): ?>
        <div class="notice-bar">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:18px"></i>
            <div>
                Your student profile hasn't been set up yet in the system.
                Attendance and results may not show. Please contact your administrator.
            </div>
        </div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h4 class="mb-1 fw-bold">Welcome back, <?= htmlspecialchars($userName) ?>! 👋</h4>
            <p class="mb-0" style="opacity:.8">
                <?php if ($studentClass !== 'N/A'): ?>
                    Class <?= htmlspecialchars($studentClass) ?> · Section <?= htmlspecialchars($studentSection) ?>
                    &nbsp;|&nbsp;
                <?php endif; ?>
                <?= date('l, F j, Y') ?>
            </p>
        </div>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">🏫</div>
                    <h6 class="mt-2 fw-bold">My Class</h6>
                    <h2 style="color:var(--purple)"><?= htmlspecialchars($studentClass) ?></h2>
                    <small class="text-muted">Section <?= htmlspecialchars($studentSection) ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <!-- Attendance ring -->
                    <div class="ring-wrap">
                        <svg width="70" height="70" viewBox="0 0 70 70">
                            <circle class="ring-bg" cx="35" cy="35" r="30"/>
                            <circle class="ring-val" cx="35" cy="35" r="30"
                                stroke-dasharray="188.5"
                                stroke-dashoffset="<?= 188.5 - (188.5 * $attendancePct / 100) ?>"/>
                        </svg>
                        <div class="ring-text"><?= $attendancePct ?>%</div>
                    </div>
                    <h6 class="fw-bold">Attendance</h6>
                    <p class="mb-1 text-success fw-bold"><?= $attendanceCount ?> / <?= $totalDays ?> days</p>
                    <a href="../attendance/my-attendance.php" class="btn btn-sm btn-outline-success">View</a>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">📝</div>
                    <h6 class="mt-2 fw-bold">Results</h6>
                    <h2 class="text-primary"><?= $examCount ?></h2>
                    <small class="text-muted">Exams Completed</small>
                    <div class="mt-2">
                        <a href="../exams/my-results.php" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">💰</div>
                    <h6 class="mt-2 fw-bold">Fees</h6>
                    <h2 class="text-warning">—</h2>
                    <small class="text-muted">Fee Status</small>
                    <div class="mt-2">
                        <a href="../fees/my-fees.php" class="btn btn-sm btn-outline-warning">View</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile + Quick Actions -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">👤 My Profile</h6>
                    <div class="info-row">
                        <span class="text-muted">Full Name</span>
                        <strong><?= htmlspecialchars($userName) ?></strong>
                    </div>
                    <div class="info-row">
                        <span class="text-muted">Email</span>
                        <strong><?= htmlspecialchars($userEmail) ?></strong>
                    </div>
                    <div class="info-row">
                        <span class="text-muted">Class</span>
                        <strong><?= htmlspecialchars($studentClass) ?></strong>
                    </div>
                    <div class="info-row">
                        <span class="text-muted">Section</span>
                        <strong><?= htmlspecialchars($studentSection) ?></strong>
                    </div>
                    <div class="info-row">
                        <span class="text-muted">Role</span>
                        <span class="badge" style="background:var(--purple)">Student</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">⚡ Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="../attendance/my-attendance.php" class="btn btn-outline-success">
                            <i class="bi bi-calendar-check me-1"></i> View My Attendance
                        </a>
                        <a href="../exams/my-results.php" class="btn btn-outline-primary">
                            <i class="bi bi-bar-chart me-1"></i> View My Results
                        </a>
                        <a href="../timetable/timetable.php" class="btn btn-outline-secondary">
                            <i class="bi bi-clock me-1"></i> View Timetable
                        </a>
                        <a href="../notifications/notifications.php" class="btn btn-outline-warning">
                            <i class="bi bi-bell me-1"></i> Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>