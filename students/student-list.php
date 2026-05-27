<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query  = "SELECT * FROM students WHERE
           name LIKE '%$search%' OR
           student_id LIKE '%$search%' OR
           class LIKE '%$search%'
           ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$total  = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="../dashboard/dashboard.php" 
           class="btn btn-secondary btn-sm">← Dashboard</a>
        <a href="../auth/logout.php" 
           class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>🎓 Student Management</h4>
        <a href="add-student.php" class="btn btn-dark">+ Add Student</a>
    </div>

    <!-- Search -->
    <div class="card p-3 mb-4">
        <form method="GET">
            <div class="input-group">
                <input type="text" name="search"
                       class="form-control"
                       placeholder="Search by name, ID, class..."
                       value="<?= $search ?>">
                <button type="submit" class="btn btn-dark">Search</button>
                <a href="student-list.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card p-3">
        <p class="text-muted">
            Total Students: <strong><?= $total ?></strong>
        </p>
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Contact</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($student = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $student['id'] ?></td>
                    <td><?= $student['student_id'] ?></td>
                    <td><?= $student['name'] ?></td>
                    <td><?= $student['class'] ?></td>
                    <td><?= $student['section'] ?></td>
                    <td><?= $student['parent_contact'] ?></td>
                    <td>
                        <a href="student-profile.php?id=<?= $student['id'] ?>"
                           class="btn btn-sm btn-info">View</a>
                        <a href="edit-student.php?id=<?= $student['id'] ?>"
                           class="btn btn-sm btn-warning">Edit</a>
                        <a href="delete-student.php?id=<?= $student['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this student?')">
                           Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
