<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$id      = $_GET['id'];
$student = mysqli_fetch_assoc(mysqli_query($conn,
           "SELECT * FROM students WHERE id='$id'"));

if (!$student) {
    header("Location: student-list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Profile - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 30px auto;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #1a1a2e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="student-list.php" class="btn btn-secondary btn-sm">← Back</a>
        <a href="edit-student.php?id=<?= $student['id'] ?>"
           class="btn btn-warning btn-sm">✏️ Edit</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="profile-card">
    <div class="profile-avatar">🎓</div>
    <h4 class="text-center"><?= $student['name'] ?></h4>
    <p class="text-center text-muted mb-4">
        <?= $student['student_id'] ?>
    </p>

    <div class="info-row">
        <span class="fw-bold">Class</span>
        <span><?= $student['class'] ?> - <?= $student['section'] ?></span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Date of Birth</span>
        <span><?= $student['dob'] ?></span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Address</span>
        <span><?= $student['address'] ?></span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Parent Contact</span>
        <span><?= $student['parent_contact'] ?></span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Joined On</span>
        <span><?= $student['created_at'] ?></span>
    </div>

    <div class="text-center mt-4">
        <a href="edit-student.php?id=<?= $student['id'] ?>"
           class="btn btn-dark">
           ✏️ Edit Student
        </a>
        <a href="delete-student.php?id=<?= $student['id'] ?>"
           class="btn btn-danger ms-2"
           onclick="return confirm('Delete this student?')">
           🗑️ Delete
        </a>
    </div>
</div>

</body>
</html>
