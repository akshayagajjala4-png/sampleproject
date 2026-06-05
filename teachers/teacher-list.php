<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query  = "SELECT * FROM teachers WHERE
           name LIKE '%$search%' OR
           subject LIKE '%$search%'
           ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$total  = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teachers - MindMerge</title>
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
        <h4>👨‍🏫 Teacher Management</h4>
        <a href="add-teacher.php" class="btn btn-dark">+ Add Teacher</a>
    </div>

    <!-- Search -->
    <div class="card p-3 mb-4">
        <form method="GET">
            <div class="input-group">
                <input type="text" name="search"
                       class="form-control"
                       placeholder="Search by name or subject..."
                       value="<?= $search ?>">
                <button type="submit" class="btn btn-dark">Search</button>
                <a href="teacher-list.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card p-3">
        <p class="text-muted">
            Total Teachers: <strong><?= $total ?></strong>
        </p>
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Qualification</th>
                    <th>Subject</th>
                    <th>Salary</th>
                    <th>Contact</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($teacher = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $teacher['id'] ?></td>
                    <td><?= $teacher['name'] ?></td>
                    <td><?= $teacher['qualification'] ?></td>
                    <td>
                        <span class="badge bg-success">
                            <?= $teacher['subject'] ?>
                        </span>
                    </td>
                    <td>₹<?= number_format($teacher['salary'],2) ?></td>
                    <td><?= $teacher['contact'] ?></td>
                    <td>
                        <a href="teacher-profile.php?id=<?= $teacher['id'] ?>"
                           class="btn btn-sm btn-info">View</a>
                        <a href="edit-teacher.php?id=<?= $teacher['id'] ?>"
                           class="btn btn-sm btn-warning">Edit</a>
                        <a href="delete-teacher.php?id=<?= $teacher['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this teacher?')">
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
