<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$success = "";
$error   = "";
$id      = $_GET['id'];
$teacher = mysqli_fetch_assoc(mysqli_query($conn,
           "SELECT * FROM teachers WHERE id='$id'"));

if (!$teacher) {
    header("Location: teacher-list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name          = $_POST['name'];
    $qualification = $_POST['qualification'];
    $subject       = $_POST['subject'];
    $salary        = $_POST['salary'];
    $contact       = $_POST['contact'];

    $query = "UPDATE teachers SET
              name='$name',
              qualification='$qualification',
              subject='$subject',
              salary='$salary',
              contact='$contact'
              WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        $success = "Teacher updated successfully!";
        $teacher = mysqli_fetch_assoc(mysqli_query($conn,
                   "SELECT * FROM teachers WHERE id='$id'"));
    } else {
        $error = "Update failed!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Teacher - MindMerge</title>
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
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="form-card">
    <h5 class="text-center mb-4">✏️ Edit Teacher</h5>

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
                <input type="text" name="name" class="form-control"
                       value="<?= $teacher['name'] ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Qualification</label>
                <input type="text" name="qualification" class="form-control"
                       value="<?= $teacher['qualification'] ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Subject</label>
                <select name="subject" class="form-select" required>
                    <?php
                    $subjects = ['Mathematics','English','Physics',
                                 'Chemistry','Biology','History',
                                 'Geography','Computer Science',
                                 'Hindi','Telugu'];
                    foreach($subjects as $sub): ?>
                    <option value="<?= $sub ?>"
                        <?= $teacher['subject']==$sub ? 'selected':'' ?>>
                        <?= $sub ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Salary (₹)</label>
                <input type="number" name="salary" class="form-control"
                       value="<?= $teacher['salary'] ?>">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Contact Number</label>
            <input type="text" name="contact" class="form-control"
                   value="<?= $teacher['contact'] ?>">
        </div>
        <button type="submit" class="btn btn-dark w-100">
            Update Teacher
        </button>
    </form>
</div>

</body>
</html>
