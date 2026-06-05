<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$success = "";
$error   = "";
$today   = date('Y-m-d');

// Get all students
$students = mysqli_query($conn, 
            "SELECT * FROM students ORDER BY class, section, name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date      = $_POST['date'];
    $statuses  = $_POST['status'];

    // Check if attendance already marked for this date
    $check = mysqli_query($conn,
             "SELECT id FROM attendance WHERE date='$date' LIMIT 1");

    if (mysqli_num_rows($check) > 0) {
        $error = "Attendance already marked for $date!";
    } else {
        $allSaved = true;
        foreach ($statuses as $student_id => $status) {
            $query = "INSERT INTO attendance 
                      (student_id, date, status)
                      VALUES ('$student_id','$date','$status')";
            if (!mysqli_query($conn, $query)) {
                $allSaved = false;
            }
        }
        if ($allSaved) {
            $success = "Attendance marked successfully for $date!";
        } else {
            $error = "Something went wrong!";
        }
    }
    // Refresh students
    $students = mysqli_query($conn,
                "SELECT * FROM students ORDER BY class, section, name");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .present  { background: #d4edda; }
        .absent   { background: #f8d7da; }
        .late     { background: #fff3cd; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="attendance-report.php" 
           class="btn btn-info btn-sm">📊 Reports</a>
        <a href="../dashboard/dashboard.php" 
           class="btn btn-secondary btn-sm">← Dashboard</a>
        <a href="../auth/logout.php" 
           class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <h4 class="mb-4">📋 Mark Attendance</h4>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Date Selection -->
        <div class="card p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Select Date</label>
                    <input type="date" name="date"
                           class="form-control"
                           value="<?= $today ?>" required>
                </div>
                <div class="col-md-4 mt-3">
                    <button type="submit" class="btn btn-dark w-100">
                        ✅ Save Attendance
                    </button>
                </div>
            </div>
        </div>

        <!-- Students Attendance Table -->
        <div class="card p-3">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    while($student = mysqli_fetch_assoc($students)): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $student['student_id'] ?></td>
                        <td><?= $student['name'] ?></td>
                        <td><?= $student['class'] ?></td>
                        <td><?= $student['section'] ?></td>
                        <td>
                            <select name="status[<?= $student['id'] ?>]"
                                    class="form-select form-select-sm"
                                    style="width: 130px">
                                <option value="Present">✅ Present</option>
                                <option value="Absent">❌ Absent</option>
                                <option value="Late">⏰ Late</option>
                            </select>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

</body>
</html>
