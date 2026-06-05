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

// Convert saved subjects string to array for pre-checking
$savedSubjects = array_map('trim', explode(',', $teacher['subject']));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name          = $_POST['name'];
    $qualification = $_POST['qualification'];
    $subject       = isset($_POST['subjects']) 
                     ? implode(', ', $_POST['subjects']) 
                     : '';
    $salary        = $_POST['salary'];
    $contact       = $_POST['contact'];

    if (empty($subject)) {
        $error = "Please select at least one subject!";
    } else {
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
            $savedSubjects = array_map('trim', explode(',', $teacher['subject']));
        } else {
            $error = "Update failed!";
        }
    }
}

$subjectList = [
    'Mathematics', 'English', 'Physics',
    'Chemistry', 'Biology', 'History',
    'Geography', 'Computer Science',
    'Hindi', 'Telugu'
];
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
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            padding: 12px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .subject-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.2s;
        }
        .subject-item:hover {
            border-color: #1a1a2e;
            background: #f0f4f8;
        }
        .subject-item:has(input:checked) {
            border-color: #1a1a2e;
            background: #e8eaf6;
        }
        .subject-item input[type="checkbox"]:checked + label {
            font-weight: 600;
            color: #1a1a2e;
        }
        .selected-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-height: 30px;
            margin-top: 8px;
        }
        .tag {
            background: #1a1a2e;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
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

        <!-- Multi Subject Checkboxes -->
        <div class="mb-3">
            <label class="form-label fw-bold">
                Subjects
                <span class="text-muted fw-normal">(Select one or more)</span>
            </label>
            <div class="subject-grid">
                <?php foreach ($subjectList as $sub): ?>
                <div class="subject-item">
                    <input type="checkbox"
                           name="subjects[]"
                           value="<?= $sub ?>"
                           id="sub_<?= str_replace(' ', '_', $sub) ?>"
                           <?= in_array($sub, $savedSubjects) ? 'checked' : '' ?>
                           onchange="updateTags()">
                    <label for="sub_<?= str_replace(' ', '_', $sub) ?>"
                           class="mb-0" style="cursor:pointer">
                        <?= $sub ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Selected tags preview -->
            <div class="selected-tags" id="selectedTags"></div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Salary (₹)</label>
                <input type="number" name="salary" class="form-control"
                       value="<?= $teacher['salary'] ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Contact Number</label>
                <input type="text" name="contact" class="form-control"
                       value="<?= $teacher['contact'] ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100">
            Update Teacher
        </button>
    </form>
</div>

<script>
// Show pre-checked subjects as tags on page load
function updateTags() {
    const checked = document.querySelectorAll('input[name="subjects[]"]:checked');
    const container = document.getElementById('selectedTags');

    if (checked.length === 0) {
        container.innerHTML = '<span class="text-muted small">No subjects selected</span>';
        return;
    }

    container.innerHTML = '';
    checked.forEach(cb => {
        const tag = document.createElement('span');
        tag.className = 'tag';
        tag.textContent = cb.value;
        container.appendChild(tag);
    });
}

// Run on page load to show existing subjects as tags
updateTags();
</script>

</body>
</html>