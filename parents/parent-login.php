<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['parent_id'])) {
    header("Location: parent-dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM parents WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $parent = $result->fetch_assoc();
            if (password_verify($password, $parent['password'])) {
                $_SESSION['parent_id']   = $parent['id'];
                $_SESSION['parent_name'] = $parent['name'];
                $_SESSION['parent_email']= $parent['email'];
                header("Location: parent-dashboard.php");
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Login – MindMerge SmartCampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3a5f, #2d6a9f);
            padding: 32px 24px;
            text-align: center;
            color: #fff;
        }
        .login-header .icon-wrap {
            width: 68px;
            height: 68px;
            background: rgba(255,255,255,0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .login-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0 0 4px;
        }
        .login-header p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        .login-body {
            padding: 32px 28px;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        .form-control {
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #2d6a9f;
            box-shadow: 0 0 0 3px rgba(45,106,159,0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #1e3a5f, #2d6a9f);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            transition: opacity 0.2s;
        }
        .btn-login:hover {
            opacity: 0.9;
            color: #fff;
        }
        .input-group-text {
            border: 1.5px solid #e5e7eb;
            background: #f9fafb;
            color: #6b7280;
        }
        .school-badge {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="icon-wrap">
            <i class="fa fa-graduation-cap fa-2x"></i>
        </div>
        <h1>Parent Portal</h1>
        <p>MindMerge SmartCampus</p>
    </div>

    <div class="login-body">
        <p class="text-muted mb-4" style="font-size:0.9rem;">Sign in to stay connected with your child's education.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Enter your email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fa fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>

        <div class="school-badge">
            <i class="fa fa-shield-alt me-1"></i>Secure Parent Access
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
</script>
</body>
</html>

