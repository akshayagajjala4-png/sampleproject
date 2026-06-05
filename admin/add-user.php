<?php
require_once '../auth/session.php';
require_once '../config/database.php';
adminOnly();

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id  = $_POST['role_id'];

    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email already exists!";
    } else {
        $query = "INSERT INTO users (name, email, password, role_id) 
                  VALUES ('$name', '$email', '$password', '$role_id')";
        if (mysqli_query($conn, $query)) {
            $success = "User added successfully!";
        }
    }
}

// Get all roles
$roles = mysqli_query($conn, "SELECT * FROM roles");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 30px auto;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="manage-users.php" class="btn btn-secondary btn-sm">← Back</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="form-card">
    <h5 class="mb-4 text-center">➕ Add New User</h5>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold">Full Name</label>
            <input type="text" name="name" class="form-control" 
                   placeholder="Enter full name" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Email</label>
            <input type="email" name="email" class="form-control" 
                   placeholder="Enter email" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Password</label>
            <input type="password" name="password" class="form-control" 
                   placeholder="Enter password" required>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Role</label>
            <select name="role_id" class="form-select" required>
                <option value="">-- Select Role --</option>
                <?php while($role = mysqli_fetch_assoc($roles)): ?>
                <option value="<?= $role['id'] ?>">
                    <?= $role['role_name'] ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-dark w-100">
            Add User
        </button>
    </form>
</div>

</body>
</html>
