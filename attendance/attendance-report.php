<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

// Get filter values
$filter_date  = isset($_GET['date'])  ? $_GET['date']  : date('Y-m-d');
$filter_class = isset($_GET['class']) ? $_GET['class'] : '';

// Build query
$query = "SELECT a.*, s.name, s.student_id, s.class, s.section
          FROM attendance a
          JOIN students s ON a.student_id = s.id
          WHERE a.date = '$filter_date'";

if (!empty($filter_class)) {
    $query .= " AND s.class = '$filter_class'";
}
$query .= " ORDER BY s.class, s.section, s.name";

$result = mysqli_query($conn, $query);
$total  = mysqli_num_rows($result);

// Count statuses
$present = mysqli_fetch_assoc(mysqli_query($conn,
           "SELECT COUNT(*) as total FROM attendance 
            WHERE date='$filter_date' AND status='Present'"))['total'];
$absent  = mysqli_fetch_assoc(mysqli_query($conn,
           "SELECT COUNT(*) as total FROM attendance 
            WHERE date='$filter_date' AND status='Absent'"))['total'];
$late    = mysqli_fetch_assoc(mysqli_query($conn,
           "SELECT COUNT(*) as total FROM attendance 
            WHERE date='$filter_date' AND status='Late'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="mark-attendance.php" 
           class="btn btn-success btn-sm">✅ Mark Attendance</a>
        <a href="../dashboard/dashboard.php" 
           class="btn btn-secondary btn-sm">← Dashboard</a>
        <a href="../auth/logout.php" 
           class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <h4 class="mb-4">📊 Attendance Report</h4>

    <!-- Filter -->
    <div class="card p-3 mb-4">
        <form method="GET">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Select Date</label>
                    <input type="date" name="date"
                           class="form-control"
                           value="<?= $filter_date ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Filter by Class</label>
                    <select name="class" class="form-select">
                        <option value="">-- All Classes --</option>
                        <option value="8th"  <?= $filter_class=='8th'  ? 'selected':'' ?>>8th</option>
                        <option value="9th"  <?= $filter_class=='9th'  ? 'selected':'' ?>>9th</option>
                        <option value="10th" <?= $filter_class=='10th' ? 'selected':'' ?>>10th</option>
                        <option value="11th" <?= $filter_class=='11th' ? 'selected':'' ?>>11th</option>
                        <option value="12th" <?= $filter_class=='12th' ? 'selected':'' ?>>12th</option>
                    </select>
                </div>
                <div class="col-md-4 mt-4">
                    <button type="submit" class="btn btn-dark w-100">
                        🔍 Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-box">
                <h6>Total</h6>
                <h3 class="text-dark"><?= $total ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <h6>Present</h6>
                <h3 class="text-success"><?= $present ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <h6>Absent</h6>
                <h3 class="text-danger"><?= $absent ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <h6>Late</h6>
                <h3 class="text-warning"><?= $late ?></h3>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card p-3">
        <?php if ($total > 0): ?>
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                while($row = mysqli_fetch_assoc($result)):
                    $badge = [
                        'Present' => 'success',
                        'Absent'  => 'danger',
                        'Late'    => 'warning'
                    ];
                    $color = $badge[$row['status']];
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $row['student_id'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['class'] ?></td>
                    <td><?= $row['section'] ?></td>
                    <td><?= $row['date'] ?></td>
                    <td>
                        <span class="badge bg-<?= $color ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="text-center py-4">
                <h5 class="text-muted">
                    No attendance found for <?= $filter_date ?>
                </h5>
                <a href="mark-attendance.php" class="btn btn-dark mt-2">
                    Mark Attendance Now
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
