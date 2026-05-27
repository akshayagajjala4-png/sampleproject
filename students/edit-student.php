<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$success = "";
$error   = "";
$id      = $_GET['id'];
$student = mysqli_fetch_assoc(mysqli_query($conn,
           "SELECT * FROM students WHERE id='$id'"));

if (!$student) {
    header("Location: student-list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name           = $_POST['name'];
    $class          = $_POST['class'];
    $section        = $_POST['section'];
    $dob            = $_POST['dob'];
    $address        = $_POST['address'];
    $parent_contact = $_POST['parent_contact'];

    $query = "UPDATE students SET
              name='$name', class='$class',
              section='$section', dob='$dob',
              address='$address',
              parent_contact='$parent_contact'
              WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        $success = "Student updated successfully!";
        $student = mysqli_fetch_assoc(mysqli_query($conn,
                   "SELECT * FROM students WHERE id='$id'"));
    } else {
        $error = "Update failed!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - MindMerge</title>
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
        <a href="student-list.php" class="btn btn-secondary btn-sm">← Back</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="form-card">
    <h5 class="text-center mb-4">✏️ Edit Student</h5>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Student ID</label>
                <input type="text" class="form-control"
                       value="<?= $student['student_id'] ?>" disabled>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name" class="form-control"
                       value="<?= $student['name'] ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Class</label>
                <select name="class" class="form-select" required>
                    <?php
                    $classes = ['8th','9th','10th','11th','12th'];
                    foreach($classes as $c): ?>
                    <option value="<?= $c ?>"
                        <?= $student['class']==$c ? 'selected':'' ?>>
                        <?= $c ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Section</label>
                <select name="section" class="form-select" required>
                    <?php
                    $sections = ['A','B','C'];
                    foreach($sections as $s): ?>
                    <option value="<?= $s ?>"
                        <?= $student['section']==$s ? 'selected':'' ?>>
                        <?= $s ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Date of Birth</label>
                <input type="date" name="dob" class="form-control"
                       value="<?= $student['dob'] ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Parent Contact</label>
                <input type="text" name="parent_contact" class="form-control"
                       value="<?= $student['parent_contact'] ?>">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Address</label>
            <textarea name="address" class="form-control" rows="3">
<?= $student['address'] ?>
            </textarea>
        </div>
        <button type="submit" class="btn btn-dark w-100">
            Update Student
        </button>
    </form>
</div>

</body>
</html>
