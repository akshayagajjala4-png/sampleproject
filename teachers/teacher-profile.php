<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$id      = $_GET['id'];
$teacher = mysqli_fetch_assoc(mysqli_query($conn,
           "SELECT * FROM teachers WHERE id='$id'"));

if (!$teacher) {
    header("Location: teacher-list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Profile - MindMerge</title>
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
        <a href="teacher-list.php" class="btn btn-secondary btn-sm">← Back</a>
        <a href="edit-teacher.php?id=<?= $teacher['id'] ?>"
           class="btn btn-warning btn-sm">✏️ Edit</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="profile-card">
    <div class="profile-avatar">👨‍🏫</div>
    <h4 class="text-center"><?= $teacher['name'] ?></h4>
    <p class="text-center text-muted mb-4">
        <?= $teacher['subject'] ?> Teacher
    </p>

    <div class="info-row">
        <span class="fw-bold">Qualification</span>
        <span><?= $teacher['qualification'] ?></span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Subject</span>
        <span>
            <span class="badge bg-success">
                <?= $teacher['subject'] ?>
            </span>
        </span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Salary</span>
        <span>₹<?= number_format($teacher['salary'],2) ?></span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Contact</span>
        <span><?= $teacher['contact'] ?></span>
    </div>
    <div class="info-row">
        <span class="fw-bold">Joined On</span>
        <span><?= $teacher['created_at'] ?></span>
    </div>

    <div class="text-center mt-4">
        <a href="edit-teacher.php?id=<?= $teacher['id'] ?>"
           class="btn btn-dark">
           ✏️ Edit Teacher
        </a>
        <a href="delete-teacher.php?id=<?= $teacher['id'] ?>"
           class="btn btn-danger ms-2"
           onclick="return confirm('Delete this teacher?')">
           🗑️ Delete
        </a>
    </div>
</div>

</body>
</html>
