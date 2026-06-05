<?php
include '../config/database.php';

$message = "";

// ── ADD / EDIT EXAM ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_name   = htmlspecialchars($_POST['exam_name']);
    $subject     = htmlspecialchars($_POST['subject']);
    $class_id    = intval($_POST['class_id']);
    $exam_date   = $_POST['exam_date'];
    $start_time  = $_POST['start_time'];
    $end_time    = $_POST['end_time'];
    $total_marks = intval($_POST['total_marks']);
    $pass_marks  = intval($_POST['pass_marks']);

    if (isset($_POST['exam_id']) && $_POST['exam_id']) {
        $id   = intval($_POST['exam_id']);
        $stmt = $conn->prepare("UPDATE exams SET exam_name=?, subject=?, class_id=?, exam_date=?, start_time=?, end_time=?, total_marks=?, pass_marks=? WHERE id=?");
        $stmt->bind_param("ssisssiii", $exam_name, $subject, $class_id, $exam_date, $start_time, $end_time, $total_marks, $pass_marks, $id);
        $stmt->execute();
        $message = "✅ Exam updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO exams (exam_name, subject, class_id, exam_date, start_time, end_time, total_marks, pass_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssii", $exam_name, $subject, $class_id, $exam_date, $start_time, $end_time, $total_marks, $pass_marks);
        $stmt->execute();
        $message = "✅ Exam scheduled successfully!";
    }
    $stmt->close();
}

// ── DELETE EXAM ────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM exams WHERE id=$id");
    $message = "🗑️ Exam deleted.";
}

// ── FETCH FOR EDIT ─────────────────────────────────────────────────
$edit_exam = null;
if (isset($_GET['edit'])) {
    $id        = intval($_GET['edit']);
    $edit_exam = $conn->query("SELECT * FROM exams WHERE id=$id")->fetch_assoc();
}

// ── FETCH ALL EXAMS ────────────────────────────────────────────────
$exams   = $conn->query("SELECT e.*, c.class_name FROM exams e JOIN classes c ON e.class_id=c.id ORDER BY e.exam_date DESC");
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Schedule | MindMerge</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .form-group { display: flex; flex-direction: column; gap: 5px; }
  .success { color: #2e7d32; background: #e8f5e9; padding: 12px; border-radius: 4px; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; margin-top: 14px; }
  th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #eee; font-size: 14px; }
  th { background: #f1f5f9; color: #555; }
  .btn-edit   { background: #3b82f6; color: #fff; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
  .btn-delete { background: #ef4444; color: #fff; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
  .btn-marks  { background: #10b981; color: #fff; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
  .badge-upcoming { background: #dbeafe; color: #1d4ed8; }
  .badge-past     { background: #f3f4f6; color: #6b7280; }
</style>
</head>
<body>

<?php include '../dashboard/sidebar.php'; ?>

<div class="main-content">

  <div class="page-header">
    <h1>📅 Exam Schedule</h1>
    <p>Schedule and manage all exams</p>
  </div>

  <?php if ($message): ?>
    <div class="success"><?= $message ?></div>
  <?php endif; ?>

  <!-- ── Form Card ── -->
  <div class="card">
    <h2><?= $edit_exam ? '✏️ Edit Exam' : '➕ Schedule New Exam' ?></h2>
    <form method="POST">
      <?php if ($edit_exam): ?>
        <input type="hidden" name="exam_id" value="<?= $edit_exam['id'] ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group">
          <label>Exam Name</label>
          <input type="text" name="exam_name" required value="<?= $edit_exam['exam_name'] ?? '' ?>" placeholder="e.g. Mid Term 2025">
        </div>
        <div class="form-group">
          <label>Subject</label>
          <input type="text" name="subject" required value="<?= $edit_exam['subject'] ?? '' ?>" placeholder="e.g. Mathematics">
        </div>
        <div class="form-group">
          <label>Class</label>
          <select name="class_id" required>
            <option value="">-- Select Class --</option>
            <?php $classes->data_seek(0); while($c = $classes->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_exam && $edit_exam['class_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['class_name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Exam Date</label>
          <input type="date" name="exam_date" required value="<?= $edit_exam['exam_date'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Start Time</label>
          <input type="time" name="start_time" required value="<?= $edit_exam['start_time'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>End Time</label>
          <input type="time" name="end_time" required value="<?= $edit_exam['end_time'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Total Marks</label>
          <input type="number" name="total_marks" required value="<?= $edit_exam['total_marks'] ?? '' ?>" placeholder="100">
        </div>
        <div class="form-group">
          <label>Pass Marks</label>
          <input type="number" name="pass_marks" required value="<?= $edit_exam['pass_marks'] ?? '' ?>" placeholder="40">
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:12px;">
        <?= $edit_exam ? '💾 Update Exam' : '➕ Schedule Exam' ?>
      </button>
      <?php if ($edit_exam): ?>
        <a href="exam-schedule.php" style="margin-left:12px; color:#6b7280;">Cancel</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Exam List ── -->
  <div class="card">
    <h2>📋 All Scheduled Exams</h2>
    <table>
      <thead>
        <tr>
          <th>#</th><th>Exam Name</th><th>Subject</th><th>Class</th>
          <th>Date</th><th>Time</th><th>Marks</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($exams->num_rows === 0): ?>
        <tr><td colspan="9" style="text-align:center;color:#9ca3af;">No exams scheduled yet.</td></tr>
      <?php else:
        $n = 1;
        while($e = $exams->fetch_assoc()):
          $upcoming = strtotime($e['exam_date']) >= strtotime(date('Y-m-d'));
      ?>
        <tr>
          <td><?= $n++ ?></td>
          <td><strong><?= htmlspecialchars($e['exam_name']) ?></strong></td>
          <td><?= htmlspecialchars($e['subject']) ?></td>
          <td><?= htmlspecialchars($e['class_name']) ?></td>
          <td><?= date('d M Y', strtotime($e['exam_date'])) ?></td>
          <td><?= date('h:i A', strtotime($e['start_time'])) ?> – <?= date('h:i A', strtotime($e['end_time'])) ?></td>
          <td><?= $e['total_marks'] ?> / <?= $e['pass_marks'] ?> pass</td>
          <td><span class="badge <?= $upcoming ? 'badge-upcoming' : 'badge-past' ?>"><?= $upcoming ? 'Upcoming' : 'Completed' ?></span></td>
          <td style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="?edit=<?= $e['id'] ?>" class="btn-edit">✏️ Edit</a>
            <a href="add-marks.php?exam_id=<?= $e['id'] ?>" class="btn-marks">📝 Marks</a>
            <a href="generate-results.php?exam_id=<?= $e['id'] ?>" style="background:#f59e0b;color:#fff;padding:5px 12px;border-radius:4px;text-decoration:none;font-size:13px;">⚙️ Results</a>
            <a href="?delete=<?= $e['id'] ?>" class="btn-delete" onclick="return confirm('Delete this exam?')">🗑️</a>
          </td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>