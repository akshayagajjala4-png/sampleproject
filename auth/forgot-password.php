<?php
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $message = "Reset link sent to your email!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
        }
    </style>
</head>
<body>

<div class="card-box">
    <h4 class="text-center mb-4">🔐 Forgot Password</h4>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold">Registered Email</label>
            <input type="email" name="email" 
                   class="form-control" 
                   placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn btn-dark w-100 rounded-3 p-2">
            Send Reset Link
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="login.php" class="text-muted text-decoration-none">
            ← Back to Login
        </a>
    </div>
</div>

</body>
</html>
