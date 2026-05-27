<?php
require_once '../config/database.php';
require_once 'auth.php';
require_once 'session.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $role = loginUser($email, $password);

    if ($role) {
        if ($role == 'Admin') {
            header("Location: ../dashboard/dashboard.php");
        } elseif ($role == 'Teacher') {
            header("Location: ../dashboard/dashboard.php");
        } elseif ($role == 'Student') {
            header("Location: ../dashboard/dashboard.php");
        } elseif ($role == 'Parent') {
            header("Location: ../parents/parent-dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MindMerge SmartCampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .login-card h2 {
            color: #1a1a2e;
            font-weight: 700;
        }
        .btn-login {
            background: #1a1a2e;
            color: white;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-size: 16px;
        }
        .btn-login:hover {
            background: #16213e;
            color: white;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: #1a1a2e;
            box-shadow: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2 class="text-center">🎓 MindMerge</h2>
    <p class="text-center text-muted mb-4">SmartCampus Management System</p>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold">Email Address</label>
            <input type="email" name="email"
                   class="form-control"
                   placeholder="Enter your email" required>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Password</label>
            <input type="password" name="password"
                   class="form-control"
                   placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-login">
            Login →
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="forgot-password.php" class="text-muted text-decoration-none">
            Forgot Password?
        </a>
    </div>
</div>

</body>
</html>
