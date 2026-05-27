<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$success = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id     = $_POST['student_id'];
    $name           = $_POST['name'];
    $class          = $_POST['class'];
    $section        = $_POST['section'];
    $dob            = $_POST['dob'];
    $address        = $_POST['address'];
    $parent_contact = $_POST['parent_contact'];

    // Check duplicate
    $check = mysqli_query($conn,
             "SELECT id FROM students 
              WHERE student_id='$student_id'");

    if (mysqli_num_rows($check) > 0) {
        $error = "Student ID already exists!";
    } else {
        $query = "INSERT INTO students 
                  (student_id, name, class, section, 
                   dob, address, parent_contact)
                  VALUES 
                  ('$student_id','$name','$class',
                   '$section','$dob','$address',
                   '$parent_contact')";

        if (mysqli_query($conn, $query)) {
            $success = "Student added successfully!";
        } else {
            $error = "Something went wrong!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student - MindMerge</title>
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
        <a href="../dashboard/dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="form-card">
    <h5 class="text-center mb-4">➕ Add New Student</h5>

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
                <input type="text" name="student_id"
                       class="form-control"
                       placeholder="e.g. STU006" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name"
                       class="form-control"
                       placeholder="Enter full name" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Class</label>
                <select name="class" class="form-select" required>
                    <option value="">-- Select Class --</option>
                    <option value="8th">8th</option>
                    <option value="9th">9th</option>
                    <option value="10th">10th</option>
                    <option value="11th">11th</option>
                    <option value="12th">12th</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Section</label>
                <select name="section" class="form-select" required>
                    <option value="">-- Select Section --</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Date of Birth</label>
                <input type="date" name="dob" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Parent Contact</label>
                <input type="text" name="parent_contact"
                       class="form-control"
                       placeholder="Enter contact number">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Address</label>
            <textarea name="address" class="form-control"
                      rows="3"
                      placeholder="Enter address"></textarea>
        </div>
        <button type="submit" class="btn btn-dark w-100">
            ➕ Add Student
        </button>
    </form>
</div>

</body>
</html>
