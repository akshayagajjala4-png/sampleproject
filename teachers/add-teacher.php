<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name          = $_POST['name'];
    $qualification = $_POST['qualification'];
    $subject       = $_POST['subject'];
    $salary        = $_POST['salary'];
    $contact       = $_POST['contact'];

    $query = "INSERT INTO teachers 
              (name, qualification, subject, salary, contact)
              VALUES 
              ('$name','$qualification','$subject',
               '$salary','$contact')";

    if (mysqli_query($conn, $query)) {
        $success = "Teacher added successfully!";
    } else {
        $error = "Something went wrong!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Teacher - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 30px auto;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="teacher-list.php" class="btn btn-secondary btn-sm">← Back</a>
        <a href="../dashboard/dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="form-card">
    <h5 class="text-center mb-4">➕ Add New Teacher</h5>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name"
                       class="form-control"
                       placeholder="Enter full name" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Qualification</label>
                <input type="text" name="qualification"
                       class="form-control"
                       placeholder="e.g. M.Sc Mathematics">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Subject</label>
                <select name="subject" class="form-select" required>
                    <option value="">-- Select Subject --</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="English">English</option>
                    <option value="Physics">Physics</option>
                    <option value="Chemistry">Chemistry</option>
                    <option value="Biology">Biology</option>
                    <option value="History">History</option>
                    <option value="Geography">Geography</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Hindi">Hindi</option>
                    <option value="Telugu">Telugu</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Salary (₹)</label>
                <input type="number" name="salary"
                       class="form-control"
                       placeholder="Enter salary">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Contact Number</label>
            <input type="text" name="contact"
                   class="form-control"
                   placeholder="Enter contact number">
        </div>
        <button type="submit" class="btn btn-dark w-100">
            ➕ Add Teacher
        </button>
    </form>
</div>

</body>
</html>
