<?php
// generate-result.php — View & Generate Exam Results
include '../config/database.php';

if (!isset($_GET['exam_id'])) {
    die("No exam selected.");
}

$eid = intval($_GET['exam_id']);

// Fetch exam details
$exam = $conn->query("
    SELECT e.*, c.class_name 
    FROM exams e 
    JOIN classes c ON e.class_id = c.id 
    WHERE e.id = $eid
")->fetch_assoc();

if (!$exam) {
    die("Exam not found.");
}

// Fetch results — use s.name (matches your DB structure)
$results = $conn->query("
    SELECT s.name AS student_name, s.student_id,
           m.marks_obtained, m.grade, e.total_marks,
           ROUND((m.marks_obtained / e.total_marks) * 100, 2) AS percentage
    FROM marks m
    JOIN students s ON s.id = m.student_id
    JOIN exams e ON e.id = m.exam_id
    WHERE m.exam_id = $eid
    ORDER BY m.marks_obtained DESC
");

// Stats
$stats = $conn->query("
    SELECT COUNT(*) AS total,
           MAX(marks_obtained) AS highest,
           MIN(marks_obtained) AS lowest,
           ROUND(AVG(marks_obtained), 2) AS average,
           SUM(CASE WHEN grade != 'F' THEN 1 ELSE 0 END) AS passed,
           SUM(CASE WHEN grade  = 'F' THEN 1 ELSE 0 END) AS failed
    FROM marks WHERE exam_id = $eid
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Result — <?= htmlspecialchars($exam['exam_name']) ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
  h2   { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 6px; }
  .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
  .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
  .stat-box { background: #f0f4ff; border-radius: 8px; padding: 16px; text-align: center; }
  .stat-box .val { font-size: 28px; font-weight: bold; color: #2196F3; }
  .stat-box .lbl { font-size: 13px; color: #666; margin-top: 4px; }
  .stat-box.green .val { color: #4CAF50; }
  .stat-box.red   .val { color: #f44336; }
  .stat-box.orange .val { color: #FF9800; }
  table { width: 100%; border-collapse: collapse; }
  th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #eee; }
  th { background: #f0f0f0; font-size: 13px; }
  tr:hover { background: #fafafa; }
  .grade { display: inline-block; padding: 3px 10px; border-radius: 12px; font-weight: bold; font-size: 13px; }
  .grade-Ap { background:#c8f7c5; color:#1a7a17; }
  .grade-A  { background:#d4edda; color:#155724; }
  .grade-B  { background:#cce5ff; color:#004085; }
  .grade-C  { background:#fff3cd; color:#856404; }
  .grade-D  { background:#ffe5b4; color:#7d4e00; }
  .grade-F  { background:#f8d7da; color:#721c24; }
  .rank { font-weight: bold; color: #888; }
  .rank-1 { color: #FFD700; }
  .rank-2 { color: #A8A9AD; }
  .rank-3 { color: #CD7F32; }
  .btn { display: inline-block; padding: 10px 20px; border-radius: 4px; color: #fff; text-decoration: none; cursor: pointer; border: none; font-size: 14px; margin-right: 8px; }
  .btn-green  { background: #4CAF50; }
  .btn-blue   { background: #2196F3; }
  .btn-gray   { background: #9E9E9E; }
  .btn:hover  { opacity: .88; }
  .exam-info span { margin-right: 24px; font-size: 14px; color: #555; }
  .exam-info strong { color: #333; }
  @media print {
    .no-print { display: none !important; }
    body { background: #fff; padding: 0; }
    .card { box-shadow: none; }
  }
</style>
</head>
<body>

<div class="card">
  <div class="no-print" style="margin-bottom:16px;">
    <a href="add-marks.php" class="btn btn-gray">← Back</a>
    <button onclick="window.print()" class="btn btn-blue">🖨️ Print Result</button>
  </div>

  <h2>📊 Exam Result Sheet</h2>
  <div class="exam-info" style="margin-bottom:18px;">
    <span><strong>Exam:</strong> <?= htmlspecialchars($exam['exam_name']) ?></span>
    <span><strong>Class:</strong> <?= htmlspecialchars($exam['class_name']) ?></span>
    <span><strong>Subject:</strong> <?= htmlspecialchars($exam['subject']) ?></span>
    <span><strong>Total Marks:</strong> <?= $exam['total_marks'] ?></span>
    <span><strong>Date:</strong> <?= date('d M Y', strtotime($exam['exam_date'])) ?></span>
  </div>

  <!-- Stats -->
  <?php if ($stats['total'] > 0): ?>
  <div class="stats-grid">
    <div class="stat-box">
      <div class="val"><?= $stats['total'] ?></div>
      <div class="lbl">Total Students</div>
    </div>
    <div class="stat-box green">
      <div class="val"><?= $stats['passed'] ?></div>
      <div class="lbl">Passed</div>
    </div>
    <div class="stat-box red">
      <div class="val"><?= $stats['failed'] ?></div>
      <div class="lbl">Failed</div>
    </div>
    <div class="stat-box">
      <div class="val"><?= $stats['highest'] ?></div>
      <div class="lbl">Highest Marks</div>
    </div>
    <div class="stat-box">
      <div class="val"><?= $stats['lowest'] ?></div>
      <div class="lbl">Lowest Marks</div>
    </div>
    <div class="stat-box orange">
      <div class="val"><?= $stats['average'] ?></div>
      <div class="lbl">Class Average</div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Results Table -->
  <?php if ($results && $results->num_rows > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Rank</th>
        <th>Student Name</th>
        <th>Student ID</th>
        <th>Marks Obtained</th>
        <th>Percentage</th>
        <th>Grade</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php $rank = 1; while($row = $results->fetch_assoc()): 
        $gradeClass = 'grade-' . str_replace('+', 'p', $row['grade']);
      ?>
      <tr>
        <td class="rank rank-<?= $rank ?>"><?= $rank <= 3 ? ['🥇','🥈','🥉'][$rank-1] : '#'.$rank ?></td>
        <td><?= htmlspecialchars($row['student_name']) ?></td>
        <td><?= htmlspecialchars($row['student_id']) ?></td>
        <td><strong><?= $row['marks_obtained'] ?></strong> / <?= $exam['total_marks'] ?></td>
        <td><?= $row['percentage'] ?>%</td>
        <td><span class="grade <?= $gradeClass ?>"><?= $row['grade'] ?></span></td>
        <td><?= $row['grade'] !== 'F' ? '✅ Pass' : '❌ Fail' ?></td>
      </tr>
      <?php $rank++; endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p style="color:#888; text-align:center; padding:30px;">No marks entered for this exam yet. <a href="add-marks.php?exam_id=<?= $eid ?>">Enter Marks</a></p>
  <?php endif; ?>
</div>

</body>
</html>