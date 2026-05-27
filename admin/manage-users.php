<?php
require_once '../auth/session.php';
require_once '../config/database.php';
adminOnly();

// Fetch all users with roles
$query = "SELECT u.id, u.name, u.email, r.role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          ORDER BY u.id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="../dashboard/dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <h4 class="mb-4">👥 Manage Users & Roles</h4>

    <!-- Add New User Button -->
    <a href="add-user.php" class="btn btn-dark mb-3">+ Add New User</a>

    <!-- Users Table -->
    <div class="card p-3">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= $user['name'] ?></td>
                    <td><?= $user['email'] ?></td>
                    <td>
                        <?php
                        $badge = [
                            'Admin'   => 'danger',
                            'Teacher' => 'success',
                            'Student' => 'primary',
                            'Parent'  => 'warning'
                        ];
                        $color = $badge[$user['role_name']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>">
                            <?= $user['role_name'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit-user.php?id=<?= $user['id'] ?>" 
                           class="btn btn-sm btn-warning">Edit</a>
                        <a href="delete-user.php?id=<?= $user['id'] ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this user?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
