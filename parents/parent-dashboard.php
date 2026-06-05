<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (getUserRole() !== "parent") { header("Location: ../auth/login.php"); exit(); }

$userName = $_SESSION['user_name'] ?? 'Parent';
$userId   = $_SESSION['user_id'] ?? 0;

// Get children linked to this parent
$children = mysqli_query($conn,
    "SELECT * FROM students WHERE parent_id = $userId");
$childCount = $children ? mysqli_num_rows($children) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - MindMerge</title>
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
        .sidebar a.active { background: #ffffff20; color: white; border-left: 3px solid #ff9f43; }
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
        .welcome-banner {
            background: linear-gradient(135deg, #ff9f43, #e07b15);
            border-radius: 15px; padding: 25px 30px; color: white; margin-bottom: 25px;
        }
        .child-card {
            background: #f8f9ff; border-radius: 12px; padding: 18px;
            border: 1px solid #e8ecf4; margin-bottom: 12px;
        }
        .info-row { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">🎓 MindMerge</div>
    <div class="menu-title">Main Menu</div>
    <a href="parent-dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <div class="menu-title">My Children</div>
    <a href="child-attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="child-results.php"><i class="bi bi-bar-chart"></i> Results</a>
    <a href="child-fees.php"><i class="bi bi-cash"></i> Fee Status</a>
    <div class="menu-title">Other</div>
    <a href="../notifications/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <h5 class="mb-0">🏠 Parent Dashboard</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= htmlspecialchars($userName) ?></span>
            <span class="badge text-white" style="background:#ff9f43">Parent</span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h4 class="mb-1">Welcome back, <?= htmlspecialchars($userName) ?>! 👋</h4>
            <p class="mb-0 opacity-75">
                <?= $childCount > 0 ? "You have $childCount child(ren) enrolled." : "No children linked yet." ?>
                &nbsp;|&nbsp; <?= date('l, F j, Y') ?>
            </p>
        </div>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <div class="stat-icon">👦</div>
                    <h5 class="mt-2">Children</h5>
                    <h2 style="color:#ff9f43"><?= $childCount ?></h2>
                    <small class="text-muted">Enrolled Students</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <div class="stat-icon">📋</div>
                    <h5 class="mt-2">Attendance</h5>
                    <h2 class="text-success">—</h2>
                    <small class="text-muted">This Month</small>
                    <div class="mt-2">
                        <a href="child-attendance.php" class="btn btn-sm btn-outline-success">View</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <div class="stat-icon">💰</div>
                    <h5 class="mt-2">Fee Status</h5>
                    <h2 class="text-warning">—</h2>
                    <small class="text-muted">Pending Dues</small>
                    <div class="mt-2">
                        <a href="child-fees.php" class="btn btn-sm btn-outline-warning">View</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Children List + Quick Actions -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">👨‍👩‍👦 My Children</h6>
                    <?php
                    if ($childCount > 0):
                        // Re-query since pointer may be exhausted
                        $children2 = mysqli_query($conn,
                            "SELECT * FROM students WHERE parent_id = $userId");
                        while($child = mysqli_fetch_assoc($children2)):
                    ?>
                    <div class="child-card d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($child['name']) ?></strong>
                            <small class="text-muted d-block">
                                Class <?= htmlspecialchars($child['class']) ?> - <?= htmlspecialchars($child['section']) ?>
                            </small>
                        </div>
                        <a href="child-results.php?id=<?= $child['id'] ?>"
                           class="btn btn-sm btn-outline-primary">Results</a>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-person-x fs-2"></i>
                        <p class="mt-2">No children linked to your account yet.<br>
                        Please contact the admin.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="stat-card">
                    <h6 class="fw-bold mb-3">⚡ Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="child-attendance.php" class="btn btn-outline-success">
                            <i class="bi bi-calendar-check"></i> Check Attendance
                        </a>
                        <a href="child-results.php" class="btn btn-outline-primary">
                            <i class="bi bi-bar-chart"></i> View Results
                        </a>
                        <a href="child-fees.php" class="btn btn-outline-warning">
                            <i class="bi bi-cash"></i> Fee Status
                        </a>
                        <a href="../notifications/notifications.php" class="btn btn-outline-secondary">
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
