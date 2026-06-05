<?php
// results.php — Display Results with Ranks
include '../config/database.php';

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Fetch all exams
$exams = $conn->query("SELECT e.id, e.exam_name, e.subject, c.class_name FROM exams e JOIN classes c ON e.class_id=c.id ORDER BY e.exam_date DESC");

// Fetch selected exam info
$selected_exam = null;
$results = null;
$stats = ['total'=>0,'passed'=>0,'failed'=>0,'avg'=>0,'highest'=>0,'lowest'=>0];

if ($exam_id) {
    $r = $conn->query("SELECT e.*, c.class_name FROM exams e JOIN classes c ON e.class_id=c.id WHERE e.id=$exam_id");
    $selected_exam = $r->fetch_assoc();

    // Fetch results with rank
    $results = $conn->query("
        SELECT r.rank, s.full_name, r.total_marks AS marks_obtained,
               r.percentage, r.status, m.grade,
               r.student_id
        FROM results r
        JOIN students s ON s.id = r.student_id
        JOIN marks m ON m.student_id = r.student_id AND m.exam_id = r.exam_id
        WHERE r.exam_id = $exam_id
        ORDER BY r.rank ASC, s.full_name ASC
    ");

    // Stats
    $st = $conn->query("
        SELECT COUNT(*) AS total,
               SUM(status='Pass') AS passed,
               SUM(status='Fail') AS failed,
               ROUND(AVG(percentage),2) AS avg_pct,
               MAX(total_marks) AS highest,
               MIN(total_marks) AS lowest
        FROM results WHERE exam_id = $exam_id
    ")->fetch_assoc();
    if ($st) {
        $stats = [
            'total'   => $st['total'],
            'passed'  => $st['passed'],
            'failed'  => $st['failed'],
            'avg'     => $st['avg_pct'],
            'highest' => $st['highest'],
            'lowest'  => $st['lowest'],
        ];
    }
}

function rankMedal($rank) {
    if ($rank == 1) return '🥇';
    if ($rank == 2) return '🥈';
    if ($rank == 3) return '🥉';
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Results | Module 7</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; padding: 20px; margin: 0; }
  h2   { color: #1a237e; border-bottom: 3px solid #3f51b5; padding-bottom: 8px; }
  .card { background: #fff; border-radius: 10px; padding: 22px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.09); }
  select { padding: 9px 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; min-width: 320px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th { background: #3f51b5; color: #fff; padding: 12px 14px; text-align: left; font-size: 13px; }
  td { padding: 11px 14px; border-bottom: 1px solid #eee; font-size: 14px; }
  tr:hover td { background: #f5f5f5; }
  .rank-1 td { background: #fffde7 !important; font-weight: bold; }
  .rank-2 td { background: #fafafa !important; }
  .rank-3 td { background: #fff3e0 !important; }
  .pass { color: #2e7d32; font-weight: bold; }
  .fail { color: #c62828; font-weight: bold; }
  .grade-ap { color: #1565c0; font-weight: bold; }
  .grade-a  { color: #1976d2; font-weight: bold; }
  .grade-b  { color: #388e3c; font-weight: bold; }
  .grade-c  { color: #f57c00; font-weight: bold; }
  .grade-d  { color: #ef6c00; font-weight: bold; }
  .grade-f  { color: #c62828; font-weight: bold; }

  /* Stats cards */
  .stats-grid { display: grid; grid-template-columns: repeat(6,1fr); gap: 12px; margin-bottom: 20px; }
  .stat-card  { background: #fff; border-radius: 8px; padding: 14px 10px; text-align: center; box-shadow: 0 1px 5px rgba(0,0,0,.08); }
  .stat-card .val { font-size: 22px; font-weight: bold; color: #3f51b5; }
  .stat-card .lbl { font-size: 11px; color: #777; margin-top: 4px; }

  .no-result { text-align:center; padding: 30px; color: #999; font-size: 15px; }
  .print-btn { background: #3f51b5; color: #fff; border: none; padding: 10px 22px; border-radius: 5px; cursor: pointer; font-size: 14px; }
  .print-btn:hover { background: #303f9f; }
  .gen-btn { background: #FF9800; color: #fff; text-decoration: none; padding: 10px 22px; border-radius: 5px; font-size: 14px; display: inline-block; }

  @media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .card { box-shadow: none; }
  }
</style>
</head>
<body>

<!-- Header -->
<div class="card no-print">
  <h2>📊 Exam Results Sheet</h2>
  <form method="GET">
    <select name="exam_id" onchange="this.form.submit()">
      <option value="">-- Select Exam --</option>
      <?php while($ex = $exams->fetch_assoc()): ?>
        <option value="<?= $ex['id'] ?>" <?= ($exam_id == $ex['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($ex['exam_name']) ?> | <?= htmlspecialchars($ex['subject']) ?> | <?= htmlspecialchars($ex['class_name']) ?>
        </option>
      <?php endwhile; ?>
    </select>
    &nbsp;
    <?php if ($exam_id): ?>
      <button onclick="window.print()" type="button" class="print-btn">🖨️ Print / PDF</button>
      &nbsp;
      <a href="generate-result.php?exam_id=<?= $exam_id ?>" class="gen-btn">🔄 Regenerate</a>
      &nbsp;
      <a href="add-marks.php?exam_id=<?= $exam_id ?>" style="display:inline-block;background:#2196F3;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;font-size:14px;">✏️ Edit Marks</a>
    <?php endif; ?>
  </form>
</div>

<?php if ($selected_exam): ?>

<!-- Exam Title (printable) -->
<div class="card" style="text-align:center;padding:16px 22px;">
  <h3 style="margin:0;color:#1a237e;font-size:20px;">
    <?= htmlspecialchars($selected_exam['exam_name']) ?> — Result Sheet
  </h3>
  <p style="margin:6px 0 0;color:#666;font-size:13px;">
    Subject: <b><?= htmlspecialchars($selected_exam['subject']) ?></b> &nbsp;|&nbsp;
    Class: <b><?= htmlspecialchars($selected_exam['class_name']) ?></b> &nbsp;|&nbsp;
    Date: <b><?= $selected_exam['exam_date'] ?></b> &nbsp;|&nbsp;
    Total Marks: <b><?= $selected_exam['total_marks'] ?></b>
  </p>
</div>

<!-- Statistics -->
<?php if ($stats['total'] > 0): ?>
<div class="stats-grid no-print">
  <div class="stat-card"><div class="val"><?= $stats['total'] ?></div><div class="lbl">Total Students</div></div>
  <div class="stat-card" style="border-top:3px solid #4CAF50"><div class="val" style="color:#2e7d32"><?= $stats['passed'] ?></div><div class="lbl">Passed</div></div>
  <div class="stat-card" style="border-top:3px solid #f44336"><div class="val" style="color:#c62828"><?= $stats['failed'] ?></div><div class="lbl">Failed</div></div>
  <div class="stat-card"><div class="val"><?= $stats['avg'] ?>%</div><div class="lbl">Class Average</div></div>
  <div class="stat-card" style="border-top:3px solid #FF9800"><div class="val" style="color:#e65100"><?= $stats['highest'] ?></div><div class="lbl">Highest Marks</div></div>
  <div class="stat-card"><div class="val" style="color:#888"><?= $stats['lowest'] ?></div><div class="lbl">Lowest Marks</div></div>
</div>
<?php endif; ?>

<!-- Results Table -->
<div class="card">
  <?php if ($results && $results->num_rows > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Rank</th>
        <th>Student Name</th>
        <th>Marks Obtained</th>
        <th>Total Marks</th>
        <th>Percentage</th>
        <th>Grade</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
    <?php while($res = $results->fetch_assoc()):
        $rankClass = '';
        if ($res['rank'] == 1) $rankClass = 'rank-1';
        elseif ($res['rank'] == 2) $rankClass = 'rank-2';
        elseif ($res['rank'] == 3) $rankClass = 'rank-3';

        $gradeClass = 'grade-' . strtolower(str_replace('+','p',$res['grade']));
    ?>
      <tr class="<?= $rankClass ?>">
        <td><?= rankMedal($res['rank']) ?> <?= $res['rank'] ?></td>
        <td><?= htmlspecialchars($res['full_name']) ?></td>
        <td><?= $res['marks_obtained'] ?></td>
        <td><?= $selected_exam['total_marks'] ?></td>
        <td><?= $res['percentage'] ?>%</td>
        <td class="<?= $gradeClass ?>"><?= $res['grade'] ?></td>
        <td class="<?= strtolower($res['status']) ?>"><?= $res['status'] == 'Pass' ? '✅ Pass' : '❌ Fail' ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="no-result">
      Results not generated yet. &nbsp;
      <a href="generate-result.php?exam_id=<?= $exam_id ?>">Click here to generate →</a>
    </div>
  <?php endif; ?>
</div>

<!-- Grade Legend -->
<div class="card no-print" style="font-size:13px;color:#555;">
  <strong>Grade Scale:</strong> &nbsp;
  A+ (90-100%) &nbsp;|&nbsp; A (80-89%) &nbsp;|&nbsp; B (70-79%) &nbsp;|&nbsp; C (60-69%) &nbsp;|&nbsp; D (50-59%) &nbsp;|&nbsp; F (below 50%) &nbsp;|&nbsp;
  <strong>Pass Mark:</strong> 40%
</div>

<?php elseif ($exam_id == 0): ?>
<div class="card no-result">Select an exam above to view its results.</div>
<?php endif; ?>

</body>
</html>