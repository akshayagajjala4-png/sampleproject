<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - MindMerge SmartCampus</title>
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
        .report-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            text-align: center;
            transition: 0.3s;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .report-card h5 { font-weight: 600; margin-bottom: 15px; }
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">🎓 MindMerge</div>

    <div class="menu-title">Main Menu</div>
    <a href="../dashboard/dashboard.php">
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
    <a href="../reports/reports.php" class="active">
        <i class="bi bi-bar-chart"></i> Reports
    </a>

    <?php if($userRole === 'admin'): ?>
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
        <h5 class="mb-0">📊 Reports Module</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= htmlspecialchars($userName) ?></span>
            <span class="badge bg-warning text-dark text-capitalize">
                <?= htmlspecialchars($userRole) ?>
            </span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <!-- Report Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="report-card">
                    <div style="font-size:40px;">📅</div>
                    <h5>Attendance Report</h5>
                    <a href="?type=attendance" class="btn btn-primary w-100">Generate</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card">
                    <div style="font-size:40px;">🎓</div>
                    <h5>Student Report</h5>
                    <a href="?type=student" class="btn btn-success w-100">Generate</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card">
                    <div style="font-size:40px;">💰</div>
                    <h5>Fees Report</h5>
                    <a href="?type=fees" class="btn btn-warning w-100">Generate</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card">
                    <div style="font-size:40px;">📝</div>
                    <h5>Exam Report</h5>
                    <a href="?type=exam" class="btn btn-danger w-100">Generate</a>
                </div>
            </div>
        </div>

        <?php
        $type = isset($_GET['type']) ? $_GET['type'] : '';

        // =====================
        // ATTENDANCE REPORT
        // =====================
        if($type == 'attendance') {
            $result = $conn->query("
                SELECT s.name, a.date, a.status
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                ORDER BY a.date DESC
            ");
            ?>
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">📅 Attendance Report</h5>
                    <a href="export-pdf.php?type=attendance" class="btn btn-dark btn-sm">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
                <table class="table table-bordered table-hover">
                    <thead style="background:#1a1a2e; color:white;">
                        <tr><th>#</th><th>Student Name</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                        $badge = ($row['status'] == 'Present') ? 'success' : 'danger';
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= $row['date'] ?></td>
                            <td><span class="badge bg-<?= $badge ?>"><?= $row['status'] ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        // =====================
        // STUDENT REPORT
        // =====================
        elseif($type == 'student') {
            $result = $conn->query("SELECT * FROM students ORDER BY class, name");
            ?>
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">🎓 Student Report</h5>
                    <a href="export-pdf.php?type=student" class="btn btn-dark btn-sm">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
                <table class="table table-bordered table-hover">
                    <thead style="background:#1a1a2e; color:white;">
                        <tr><th>#</th><th>Student ID</th><th>Name</th><th>Class</th><th>Section</th><th>Parent Contact</th><th>DOB</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= htmlspecialchars($row['section']) ?></td>
                            <td><?= htmlspecialchars($row['parent_contact']) ?></td>
                            <td><?= $row['dob'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        // =====================
        // FEES REPORT
        // =====================
        elseif($type == 'fees') {
            $result = $conn->query("
                SELECT s.name, fp.amount_paid, fp.payment_date,
                       fp.payment_method, fp.receipt_no, fp.status
                FROM fee_payments fp
                JOIN students s ON fp.student_id = s.id
                ORDER BY fp.payment_date DESC
            ");
            ?>
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">💰 Fees Report</h5>
                    <a href="export-pdf.php?type=fees" class="btn btn-dark btn-sm">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
                <table class="table table-bordered table-hover">
                    <thead style="background:#1a1a2e; color:white;">
                        <tr><th>#</th><th>Student Name</th><th>Amount Paid</th><th>Payment Date</th><th>Method</th><th>Receipt No</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                        if($row['status'] == 'Paid') $badge = 'success';
                        elseif($row['status'] == 'Partial') $badge = 'warning';
                        else $badge = 'danger';
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>Rs. <?= $row['amount_paid'] ?></td>
                            <td><?= $row['payment_date'] ?></td>
                            <td><?= $row['payment_method'] ?></td>
                            <td><?= $row['receipt_no'] ?></td>
                            <td><span class="badge bg-<?= $badge ?>"><?= $row['status'] ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        // =====================
        // EXAM REPORT
        // =====================
        elseif($type == 'exam') {
            $result = $conn->query("
                SELECT s.name, e.exam_name, e.subject,
                       e.total_marks, m.marks_obtained, m.grade
                FROM marks m
                JOIN students s ON m.student_id = s.id
                JOIN exams e ON m.exam_id = e.id
                ORDER BY e.exam_name, m.marks_obtained DESC
            ");
            ?>
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">📝 Exam Report</h5>
                    <a href="export-pdf.php?type=exam" class="btn btn-dark btn-sm">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
                <table class="table table-bordered table-hover">
                    <thead style="background:#1a1a2e; color:white;">
                        <tr><th>#</th><th>Student Name</th><th>Exam Name</th><th>Subject</th><th>Total Marks</th><th>Marks Obtained</th><th>Grade</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['exam_name']) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= $row['total_marks'] ?></td>
                            <td><?= $row['marks_obtained'] ?></td>
                            <td><b><?= $row['grade'] ?></b></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        ?>

    </div><!-- container -->
</div><!-- main-content -->

</body>
</html>