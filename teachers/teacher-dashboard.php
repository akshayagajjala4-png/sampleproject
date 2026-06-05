<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (getUserRole() !== "teacher") { header("Location: ../auth/login.php"); exit(); }

$userName = $_SESSION['user_name'] ?? 'Teacher';
$userRole = $_SESSION['role'] ?? 'teacher';
$userId   = $_SESSION['user_id'] ?? 0;

// Get teacher record linked to this user
$tid = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM teachers WHERE user_id = $userId LIMIT 1"));
$teacherId = $tid['id'] ?? 0;

// Total students
$totalStudents = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'] ?? 0;

// Total classes
$totalClasses = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM classes"))['total'] ?? 0;

// Today's date
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; margin: 0; }
        .sidebar {
            width: 250px; background: #1a1a2e;
            min-height: 100vh; position: fixed;
            top: 0; left: 0; padding-top: 20px; z-index: 100;
        }
        .sidebar-brand {
            color: white; font-size: 18px; font-weight: bold;
            padding: 15px 20px; border-bottom: 1px solid #ffffff20; margin-bottom: 10px;
        }
        .sidebar a {
            display: block; color: #ffffffaa; padding: 12px 20px;
            text-decoration: none; font-size: 14px; transition: 0.3s;
        }
        .sidebar a:hover { background: #ffffff15; color: white; padding-left: 25px; }
        .sidebar a.active { background: #ffffff20; color: white; border-left: 3px solid #28c76f; }
        .sidebar .menu-title {
            color: #ffffff50; font-size: 11px; padding: 10px 20px 5px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .main-content { margin-left: 250px; }
        .top-navbar {
            background: white; padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center;
        }
        .stat-card {
            background: white; border-radius: 15px; border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); padding: 25px; transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .stat-icon { font-size: 45px; }
        .activity-item {
            padding: 12px 0; border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: center; gap: 10px;
        }
        .activity-dot { width: 10px; height: 10px; border-radius: 50%; background: #28c76f; flex-shrink: 0; }
        .welcome-banner {
            background: linear-gradient(135deg, #28c76f, #1a8a4a);
            border-radius: 15px; padding: 25px 30px; color: white; margin-bottom: 25px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">🎓 MindMerge</div>
    <div class="menu-title">Main Menu</div>
    <a href="teacher-dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <div class="menu-title">Academic</div>
    <a href="../students/student-list.php"><i class="bi bi-people"></i> Students</a>
    <a href="../attendance/mark-attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="../timetable/timetable.php"><i class="bi bi-clock"></i> Timetable</a>
    <a href="../exams/add-marks.php"><i class="bi bi-pencil-square"></i> Exams</a>
    <div class="menu-title">Communication</div>
    <a href="../notifications/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <h5 class="mb-0">📊 Teacher Dashboard</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= htmlspecialchars($userName) ?></span>
            <span class="badge bg-success text-white">Teacher</span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h4 class="mb-1">Welcome back, <?= htmlspecialchars($userName) ?>! 👋</h4>
            <p class="mb-0 opacity-75">Today is <?= date('l, F j, Y') ?></p>
        </div>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">👩‍🎓</div>
                    <h5 class="mt-2">Students</h5>
                    <h2 class="text-success"><?= $totalStudents ?></h2>
                    <small class="text-muted">Total Students</small>
                    <div class="mt-2">
                        <a href="../students/student-list.php" class="btn btn-sm btn-outline-success">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">🏫</div>
                    <h5 class="mt-2">Classes</h5>
                    <h2 class="text-primary"><?= $totalClasses ?></h2>
                    <small class="text-muted">Total Classes</small>
                    <div class="mt-2">
                        <a href="../timetable/timetable.php" class="btn btn-sm btn-outline-primary">Timetable</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">📋</div>
                    <h5 class="mt-2">Attendance</h5>
                    <h2 class="text-warning">0</h2>
                    <small class="text-muted">Marked Today</small>
                    <div class="mt-2">
                        <a href="../attendance/mark-attendance.php" class="btn btn-sm btn-outline-warning">Mark Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">📝</div>
                    <h5 class="mt-2">Exams</h5>
                    <h2 class="text-danger">0</h2>
                    <small class="text-muted">Pending Results</small>
                    <div class="mt-2">
                        <a href="../exams/add-marks.php" class="btn btn-sm btn-outline-danger">Add Marks</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Students -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">🎓 Recent Students</h6>
                    <?php
                    $students = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC LIMIT 5");
                    if ($students && mysqli_num_rows($students) > 0):
                        while($s = mysqli_fetch_assoc($students)):
                    ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div>
                            <strong><?= htmlspecialchars($s['name']) ?></strong>
                            <small class="text-muted d-block">Class <?= htmlspecialchars($s['class']) ?> - <?= htmlspecialchars($s['section']) ?></small>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <p class="text-muted">No students found.</p>
                    <?php endif; ?>
                    <a href="../students/student-list.php" class="btn btn-sm btn-dark mt-3">View All Students</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">⚡ Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="../attendance/mark-attendance.php" class="btn btn-outline-success">
                            <i class="bi bi-calendar-check"></i> Mark Attendance
                        </a>
                        <a href="../exams/add-marks.php" class="btn btn-outline-primary">
                            <i class="bi bi-pencil-square"></i> Add Exam Marks
                        </a>
                        <a href="../timetable/timetable.php" class="btn btn-outline-secondary">
                            <i class="bi bi-clock"></i> View Timetable
                        </a>
                        <a href="../notifications/notifications.php" class="btn btn-outline-warning">
                            <i class="bi bi-bell"></i> Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
