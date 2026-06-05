<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$search  = isset($_GET['search'])  ? $_GET['search']  : '';
$class   = isset($_GET['class'])   ? $_GET['class']   : '';
$section = isset($_GET['section']) ? $_GET['section'] : '';

// Build dynamic query
$where = "WHERE 1=1";
if ($search)  $where .= " AND (name LIKE '%$search%' OR student_id LIKE '%$search%')";
if ($class)   $where .= " AND class = '$class'";
if ($section) $where .= " AND section = '$section'";

$query  = "SELECT * FROM students $where ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$total  = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students - MindMerge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar { background: #1a1a2e; }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        select.form-select:focus,
        input.form-control:focus {
            border-color: #1a1a2e;
            box-shadow: 0 0 0 0.2rem rgba(26,26,46,0.2);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3">
    <span class="navbar-brand fw-bold">🎓 MindMerge SmartCampus</span>
    <div class="d-flex gap-3">
        <a href="../dashboard/dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>🎓 Student Management</h4>
        <a href="add-student.php" class="btn btn-dark">+ Add Student</a>
    </div>

    <!-- Search + Filter Bar -->
    <div class="card p-3 mb-4">
        <form method="GET">
            <div class="row g-2 align-items-center">

                <!-- Text Search -->
                <div class="col-md-4">
                    <input type="text" name="search"
                           class="form-control"
                           placeholder="Search by name or ID..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- Class Dropdown (6th to 10th hardcoded) -->
                <div class="col-md-3">
                    <select name="class" class="form-select">
                        <option value="">-- All Classes --</option>
                        <?php
                        $classList = ['6th', '7th', '8th', '9th', '10th'];
                        foreach ($classList as $cls): ?>
                            <option value="<?= $cls ?>"
                                <?= ($class == $cls) ? 'selected' : '' ?>>
                                Class <?= $cls ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Section Dropdown (A, B, C hardcoded) -->
                <div class="col-md-2">
                    <select name="section" class="form-select">
                        <option value="">-- All Sections --</option>
                        <?php
                        $sectionList = ['A', 'B', 'C'];
                        foreach ($sectionList as $sec): ?>
                            <option value="<?= $sec ?>"
                                <?= ($section == $sec) ? 'selected' : '' ?>>
                                Section <?= $sec ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">Search</button>
                    <a href="student-list.php" class="btn btn-secondary w-100">Clear</a>
                </div>

            </div>
        </form>
    </div>

    <!-- Active Filter Badges -->
    <?php if ($class || $section || $search): ?>
    <div class="mb-3 d-flex gap-2 flex-wrap">
        <span class="text-muted small fw-semibold mt-1">Active filters:</span>
        <?php if ($search): ?>
            <span class="badge bg-dark">Search: "<?= htmlspecialchars($search) ?>"</span>
        <?php endif; ?>
        <?php if ($class): ?>
            <span class="badge bg-primary">Class: <?= $class ?></span>
        <?php endif; ?>
        <?php if ($section): ?>
            <span class="badge bg-success">Section: <?= $section ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="card p-3">
        <p class="text-muted">
            Total Students: <strong><?= $total ?></strong>
        </p>
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Contact</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total == 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No students found matching your filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($student = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $student['id'] ?></td>
                        <td><?= $student['student_id'] ?></td>
                        <td><?= $student['name'] ?></td>
                        <td><?= $student['class'] ?></td>
                        <td><?= $student['section'] ?></td>
                        <td><?= $student['parent_contact'] ?></td>
                        <td>
                            <a href="student-profile.php?id=<?= $student['id'] ?>"
                               class="btn btn-sm btn-info">View</a>
                            <a href="edit-student.php?id=<?= $student['id'] ?>"
                               class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete-student.php?id=<?= $student['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this student?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>