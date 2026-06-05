<?php
require_once '../auth/session.php';
require_once '../config/database.php';
adminOnly();

$success = "";
$error   = "";

$id   = $_GET['id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM users WHERE id='$id'"));

if (!$user) {
    header("Location: manage-users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $role_id = $_POST['role_id'];

    // Update password only if entered
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "UPDATE users SET 
                  name='$name', email='$email', 
                  password='$password', role_id='$role_id' 
                  WHERE id='$id'";
    } else {
        $query = "UPDATE users SET 
                  name='$name', email='$email', 
                  role_id='$role_id' 
                  WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        $success = "User updated successfully!";
        $user = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT * FROM users WHERE id='$id'"));
    } else {
        $error = "Update failed!";
    }
}

$roles = mysqli_query($conn, "SELECT * FROM roles");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - MindMerge</title>
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

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="manage-users.php" class="btn btn-secondary btn-sm">← Back</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="form-card">
    <h5 class="mb-4 text-center">✏️ Edit User</h5>

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
                   value="<?= $user['name'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= $user['email'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">
                New Password 
                <small class="text-muted">(leave empty to keep same)</small>
            </label>
            <input type="password" name="password" class="form-control"
                   placeholder="Enter new password">
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Role</label>
            <select name="role_id" class="form-select" required>
                <?php
                // Reset roles query
                $roles = mysqli_query($conn, "SELECT * FROM roles");
                while($role = mysqli_fetch_assoc($roles)): ?>
                <option value="<?= $role['id'] ?>"
                    <?= $user['role_id'] == $role['id'] ? 'selected' : '' ?>>
                    <?= $role['role_name'] ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-dark w-100">
            Update User
        </button>
    </form>
</div>

</body>
</html>
