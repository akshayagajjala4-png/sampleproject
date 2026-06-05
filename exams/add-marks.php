<?php
// add-marks.php — Create Exam & Enter Marks
include '../config/database.php'; // your DB connection file

$success = $error = "";

// ── STEP 1: Handle Create Exam ──────────────────────────────────────
if (isset($_POST['create_exam'])) {
    $exam_name   = trim($_POST['exam_name']);
    $class_id    = intval($_POST['class_id']);
    $subject     = trim($_POST['subject']);
    $total_marks = intval($_POST['total_marks']);
    $exam_date   = $_POST['exam_date'];

    if ($exam_name && $class_id && $subject && $total_marks && $exam_date) {
        $stmt = $conn->prepare("INSERT INTO exams (exam_name, class_id, subject, total_marks, exam_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sissi", $exam_name, $class_id, $subject, $total_marks, $exam_date);
        if ($stmt->execute()) {
            $success = "Exam created successfully! ID: " . $conn->insert_id;
        } else {
            $error = "Error creating exam: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}

// ── STEP 2: Handle Marks Entry ──────────────────────────────────────
if (isset($_POST['save_marks'])) {
    $exam_id     = intval($_POST['exam_id']);
    $student_ids = $_POST['student_id'];
    $marks_list  = $_POST['marks_obtained'];

    // Get total marks for this exam
    $res = $conn->query("SELECT total_marks FROM exams WHERE id = $exam_id");
    $exam = $res->fetch_assoc();
    $total = $exam['total_marks'];

    $saved = 0;
    foreach ($student_ids as $i => $sid) {
        $sid   = intval($sid);
        $marks = floatval($marks_list[$i]);

        // Calculate Grade
        $pct = ($total > 0) ? ($marks / $total) * 100 : 0;
        if ($pct >= 90)      $grade = 'A+';
        elseif ($pct >= 80)  $grade = 'A';
        elseif ($pct >= 70)  $grade = 'B';
        elseif ($pct >= 60)  $grade = 'C';
        elseif ($pct >= 50)  $grade = 'D';
        else                 $grade = 'F';

        // Check if marks already exist (upsert)
        $check = $conn->prepare("SELECT id FROM marks WHERE exam_id=? AND student_id=?");
        $check->bind_param("ii", $exam_id, $sid);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $upd = $conn->prepare("UPDATE marks SET marks_obtained=?, grade=? WHERE exam_id=? AND student_id=?");
            $upd->bind_param("dsii", $marks, $grade, $exam_id, $sid);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("INSERT INTO marks (exam_id, student_id, marks_obtained, grade) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iids", $exam_id, $sid, $marks, $grade);
            $ins->execute();
            $ins->close();
        }
        $check->close();
        $saved++;
    }
    $success = "Marks saved for $saved student(s)!";
}

// ── Fetch data for dropdowns ────────────────────────────────────────
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$exams   = $conn->query("SELECT e.id, e.exam_name, e.subject, e.total_marks, c.class_name FROM exams e JOIN classes c ON e.class_id = c.id ORDER BY e.exam_date DESC");

// If an exam is selected for marks entry, load its students
$selected_exam = null;
$students      = [];
if (isset($_GET['exam_id'])) {
    $eid = intval($_GET['exam_id']);
    $exam_row = $conn->query("SELECT e.*, c.class_name FROM exams e JOIN classes c ON e.class_id=c.id WHERE e.id=$eid")->fetch_assoc();
    if ($exam_row) {
        $selected_exam = $exam_row;
        // Fetch students of that class with any existing marks
        $class_name = $conn->real_escape_string($exam_row['class_name']);
        $students = $conn->query("
            SELECT s.id, s.name AS full_name,
                   COALESCE(m.marks_obtained, '') AS marks_obtained,
                   COALESCE(m.grade,'') AS grade
            FROM students s
            LEFT JOIN marks m ON m.student_id = s.id AND m.exam_id = $eid
            WHERE s.class = '$class_name'
            ORDER BY s.name
        ");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Marks | Module 7</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
  h2   { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 6px; }
  .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
  label { display: block; margin-bottom: 4px; font-weight: bold; font-size: 13px; }
  input, select { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 12px; box-sizing: border-box; }
  .btn-primary { background: #4CAF50; color: #fff; border: none; padding: 10px 22px; border-radius: 4px; cursor: pointer; font-size: 14px; }
  .btn-primary:hover { background: #45a049; }
  .btn-blue { background: #2196F3; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; }
  .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 14px; }
  .error   { color: red;   background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 14px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #eee; }
  th { background: #f0f0f0; font-size: 13px; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
  a { color: #2196F3; text-decoration: none; }
</style>
</head>
<body>

<?php if ($success): ?><div class="success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="error">❌ <?= htmlspecialchars($error)   ?></div><?php endif; ?>

<!-- ══════════════════════════════════════════
     SECTION 1: CREATE EXAM
════════════════════════════════════════════ -->
<div class="card">
  <h2>📝 Create Exam</h2>
  <form method="POST">
    <div class="grid-3">
      <div>
        <label>Exam Name</label>
        <input type="text" name="exam_name" placeholder="e.g. Mid-Term 2024" required>
      </div>
      <div>
        <label>Class</label>
        <select name="class_id" required>
          <option value="">-- Select Class --</option>
          <?php while($c = $classes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label>Subject</label>
        <input type="text" name="subject" placeholder="e.g. Mathematics" required>
      </div>
    </div>
    <div class="grid-2">
      <div>
        <label>Total Marks</label>
        <input type="number" name="total_marks" min="1" placeholder="e.g. 100" required>
      </div>
      <div>
        <label>Exam Date</label>
        <input type="date" name="exam_date" required>
      </div>
    </div>
    <button type="submit" name="create_exam" class="btn-primary">➕ Create Exam</button>
  </form>
</div>

<!-- ══════════════════════════════════════════
     SECTION 2: SELECT EXAM FOR MARKS ENTRY
════════════════════════════════════════════ -->
<div class="card">
  <h2>📋 Select Exam to Enter Marks</h2>
  <table>
    <thead>
      <tr><th>#</th><th>Exam Name</th><th>Class</th><th>Subject</th><th>Total Marks</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php $i=1; while($ex = $exams->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($ex['exam_name']) ?></td>
        <td><?= htmlspecialchars($ex['class_name']) ?></td>
        <td><?= htmlspecialchars($ex['subject']) ?></td>
        <td><?= $ex['total_marks'] ?></td>
        <td><a href="?exam_id=<?= $ex['id'] ?>" class="btn-blue" style="padding:6px 12px;border-radius:4px;color:#fff;display:inline-block;">Enter Marks</a></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- ══════════════════════════════════════════
     SECTION 3: MARKS ENTRY FORM
════════════════════════════════════════════ -->
<?php if ($selected_exam): ?>
<div class="card">
  <h2>✏️ Enter Marks — <?= htmlspecialchars($selected_exam['exam_name']) ?> | <?= htmlspecialchars($selected_exam['subject']) ?> | Total: <?= $selected_exam['total_marks'] ?></h2>
  <form method="POST">
    <input type="hidden" name="exam_id" value="<?= $selected_exam['id'] ?>">
    <table>
      <thead>
        <tr><th>#</th><th>Student Name</th><th>Marks Obtained (out of <?= $selected_exam['total_marks'] ?>)</th><th>Grade</th></tr>
      </thead>
      <tbody>
        <?php $n=1; while($st = $students->fetch_assoc()): ?>
        <tr>
          <td><?= $n++ ?></td>
          <td><?= htmlspecialchars($st['full_name']) ?>
            <input type="hidden" name="student_id[]" value="<?= $st['id'] ?>">
          </td>
          <td>
            <input type="number" name="marks_obtained[]" min="0" max="<?= $selected_exam['total_marks'] ?>"
              value="<?= $st['marks_obtained'] ?>" step="0.5" style="width:120px;" required>
          </td>
          <td><?= $st['grade'] ? '<b>'.$st['grade'].'</b>' : '—' ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <br>
    <button type="submit" name="save_marks" class="btn-primary">💾 Save All Marks</button>
    &nbsp;
    <a href="generate-result.php?exam_id=<?= $selected_exam['id'] ?>" class="btn-blue" style="display:inline-block;padding:10px 22px;border-radius:4px;color:#fff;">⚙️ Generate Results</a>
  </form>
</div>
<?php endif; ?>

</body>
</html>
