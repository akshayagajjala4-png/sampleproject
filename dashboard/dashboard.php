<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once 'stats.php';
redirectIfNotLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MindMerge SmartCampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; margin: 0; }
        .sidebar {
            width: 250px;
            background: #1a1a2e;
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            padding-top: 20px;
            z-index: 100;
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
        .main-content { margin-left: 250px; }
        .top-navbar {
            background: white;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            transition: 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stat-icon { font-size: 45px; }
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .activity-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #1a1a2e;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">🎓 MindMerge</div>

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
    <a href="../exams/add-marks.php">
        <i class="bi bi-pencil-square"></i> Exams
    </a>

    <div class="menu-title">Finance</div>
    <a href="../fees/fees.php">
        <i class="bi bi-cash"></i> Fees
    </a>

    <div class="menu-title">Communication</div>
    <a href="../notifications/notifications.php">
        <i class="bi bi-bell"></i> Notifications
    </a>
    <a href="../reports/reports.php">
        <i class="bi bi-bar-chart"></i> Reports
    </a>

    <?php if ($_SESSION['role'] == 'Admin'): ?>
    <div class="menu-title">Admin</div>
    <a href="../admin/manage-users.php">
        <i class="bi bi-gear"></i> Manage Users
    </a>
    <?php endif; ?>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h5 class="mb-0">📊 Dashboard</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= $_SESSION['name'] ?></span>
            <span class="badge bg-warning text-dark">
                <?= $_SESSION['role'] ?>
            </span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                Logout
            </a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <!-- Welcome Message -->
        <h5 class="mb-4">Welcome back, <?= $_SESSION['name'] ?>! 👋</h5>

        <!-- Widgets (Stats + Results + Classes) -->
        <?php require_once 'widgets.php'; ?>

        <!-- Recent Activity -->
        <div class="row g-4">

            <!-- Recent Students -->
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">🎓 Recent Students</h6>
                    <?php
                    $recentStudents = mysqli_query($conn,
                        "SELECT * FROM students ORDER BY id DESC LIMIT 5");
                    while($s = mysqli_fetch_assoc($recentStudents)):
                    ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div>
                            <strong><?= $s['name'] ?></strong>
                            <small class="text-muted d-block">
                                Class <?= $s['class'] ?> - <?= $s['section'] ?>
                            </small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <a href="../students/student-list.php"
                       class="btn btn-sm btn-dark mt-3">
                       View All Students
                    </a>
                </div>
            </div>

            <!-- Recent Teachers -->
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">👨‍🏫 Recent Teachers</h6>
                    <?php
                    $recentTeachers = mysqli_query($conn,
                        "SELECT * FROM teachers ORDER BY id DESC LIMIT 5");
                    while($t = mysqli_fetch_assoc($recentTeachers)):
                    ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div>
                            <strong><?= $t['name'] ?></strong>
                            <small class="text-muted d-block">
                                <?= $t['subject'] ?>
                            </small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <a href="../teachers/teacher-list.php"
                       class="btn btn-sm btn-dark mt-3">
                       View All Teachers
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>