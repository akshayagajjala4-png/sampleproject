<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
include('../config/database.php');

$role      = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['name'] ?? 'Admin User';

$selected_class   = isset($_GET['class'])   ? $conn->real_escape_string($_GET['class'])   : '';
$selected_section = isset($_GET['section']) ? $conn->real_escape_string($_GET['section']) : '';
$selected_type    = isset($_GET['type'])    ? $conn->real_escape_string($_GET['type'])    : 'class';

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$max_period = 8;

$classes_result = $conn->query("SELECT DISTINCT class_name, section FROM timetable ORDER BY class_name, section");

$timetable_data = [];
if ($selected_class !== '') {
    $where = "class_name='$selected_class' AND schedule_type='$selected_type'";
    if ($selected_section !== '') $where .= " AND section='$selected_section'";
    $res = $conn->query("SELECT * FROM timetable WHERE $where ORDER BY period");
    while ($row = $res->fetch_assoc()) {
        $timetable_data[$row['day']][$row['period']] = $row;
    }
    $pm = $conn->query("SELECT MAX(period) as mp FROM timetable WHERE $where")->fetch_assoc()['mp'];
    if ($pm) $max_period = max(8, $pm);
}

$subject_colors = ['#dbeafe','#dcfce7','#fef9c3','#fce7f3','#ede9fe','#e0f2fe','#ffedd5','#f1f5f9','#d1fae5','#fee2e2'];
function subjectColor($s, $c) { return $c[(crc32($s) & 0x7fffffff) % count($c)]; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - MindMerge SmartCampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; margin: 0; }
        .sidebar {
            width: 250px;
            background: #1a1a2e;
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            padding-top: 20px;
            z-index: 100;
        }
        .sidebar-brand {
            color: white;
            font-size: 18px;
            font-weight: bold;
            padding: 15px 20px;
            border-bottom: 1px solid #ffffff20;
            margin-bottom: 10px;
        }
        .sidebar a {
            display: block;
            color: #ffffffaa;
            padding: 12px 20px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .sidebar a:hover { background: #ffffff15; color: white; padding-left: 25px; }
        .sidebar a.active { background: #ffffff20; color: white; border-left: 3px solid #ffc107; }
        .sidebar .menu-title {
            color: #ffffff50;
            font-size: 11px;
            padding: 10px 20px 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .main-content { margin-left: 250px; }
        .top-navbar {
            background: white;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .content-area { padding: 24px; }
        .card-box {
            background: white;
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 20px;
        }
        .filter-label {
            font-size: 11px; font-weight: 700; color: #6b7280;
            letter-spacing: 0.06em; text-transform: uppercase;
            display: block; margin-bottom: 5px;
        }
        .stype-tabs { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
        .stype-tab {
            padding: 5px 14px; border-radius: 20px; font-size: 13px;
            font-weight: 500; text-decoration: none; color: #6b7280;
            background: #f3f4f6; border: 1px solid #e5e7eb; transition: all 0.2s;
        }
        .stype-tab:hover { background: #e5e7eb; color: #374151; }
        .stype-tab.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
        .quick-btn {
            font-size: 12px; padding: 3px 12px; border-radius: 20px;
            border: 1px solid #1a1a2e; color: #1a1a2e; background: #fff;
            text-decoration: none; display: inline-block; transition: all 0.2s;
        }
        .quick-btn:hover, .quick-btn.active { background: #1a1a2e; color: #fff; }
        .tt-wrap { overflow-x: auto; }
        .tt-table { min-width: 750px; border-collapse: collapse; width: 100%; }
        .tt-table thead th {
            background: #f8fafc; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;
            padding: 10px 8px; border-bottom: 2px solid #e5e7eb; text-align: center;
        }
        .tt-table thead th:first-child { text-align: left; padding-left: 14px; }
        .tt-table tbody td { padding: 5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .tt-table tbody tr:last-child td { border-bottom: none; }
        .day-cell { font-size: 13px; font-weight: 700; color: #374151; padding-left: 14px !important; white-space: nowrap; background: #fafbfc; }
        .period-card {
            border-radius: 8px; padding: 7px 9px; min-height: 60px;
            display: flex; flex-direction: column; justify-content: center; gap: 2px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .period-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .period-subject { font-size: 12px; font-weight: 700; color: #1e293b; line-height: 1.3; }
        .period-meta { font-size: 10px; color: #64748b; }
        .period-time { font-size: 10px; color: #94a3b8; }
        .empty-cell { text-align: center; color: #e2e8f0; font-size: 18px; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 52px; color: #1a1a2e; display: block; margin-bottom: 16px; }
        .empty-state h5 { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: #6b7280; }
        @media print {
            .sidebar, .top-navbar, .no-print { display: none !important; }
            .main-content { margin: 0; }
            .content-area { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">🎓 MindMerge</div>
    <div class="menu-title">Main Menu</div>
    <a href="../dashboard/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <div class="menu-title">Academic</div>
    <a href="../students/student-list.php"><i class="bi bi-people"></i> Students</a>
    <a href="../teachers/teacher-list.php"><i class="bi bi-person-badge"></i> Teachers</a>
    <a href="../attendance/mark-attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="timetable.php" class="active"><i class="bi bi-clock"></i> Timetable</a>
    <a href="../exams/add-marks.php"><i class="bi bi-pencil-square"></i> Exams</a>
    <div class="menu-title">Finance</div>
    <a href="../fees/fees.php"><i class="bi bi-cash"></i> Fees</a>
    <div class="menu-title">Communication</div>
    <a href="../notifications/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
    <a href="../reports/reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
    <?php if (strtolower($role) === 'admin'): ?>
    <div class="menu-title">Admin</div>
    <a href="../admin/manage-users.php"><i class="bi bi-gear"></i> Manage Users</a>
    <?php endif; ?>
</div>

<div class="main-content">
    <div class="top-navbar">
        <h5 class="mb-0">🗓️ Timetable</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= htmlspecialchars($user_name) ?></span>
            <span class="badge bg-warning text-dark"><?= htmlspecialchars($role) ?></span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm no-print">Logout</a>
        </div>
    </div>

    <div class="content-area">

        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <p class="mb-0 text-muted" style="font-size:14px;">View class, teacher, and exam schedules.</p>
            <div class="d-flex gap-2">
                <?php if ($selected_class !== ''): ?>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php endif; ?>
                <?php if (strtolower($role) === 'admin' || strtolower($role) === 'teacher'): ?>
                <a href="manage-schedule.php" class="btn btn-sm btn-dark">
                    <i class="bi bi-plus-circle"></i> Manage Schedule
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-box mb-4 no-print">
            <div class="stype-tabs">
                <a href="?type=class<?= $selected_class ? '&class='.urlencode($selected_class).'&section='.urlencode($selected_section) : '' ?>"
                   class="stype-tab <?= $selected_type=='class' ? 'active':'' ?>">📚 Class Schedule</a>
                <a href="?type=teacher<?= $selected_class ? '&class='.urlencode($selected_class).'&section='.urlencode($selected_section) : '' ?>"
                   class="stype-tab <?= $selected_type=='teacher' ? 'active':'' ?>">👨‍🏫 Teacher Schedule</a>
                <a href="?type=exam<?= $selected_class ? '&class='.urlencode($selected_class).'&section='.urlencode($selected_section) : '' ?>"
                   class="stype-tab <?= $selected_type=='exam' ? 'active':'' ?>">📝 Exam Schedule</a>
            </div>
            <form method="GET" class="d-flex align-items-end gap-3 flex-wrap">
                <input type="hidden" name="type" value="<?= htmlspecialchars($selected_type) ?>">
                <div>
                    <label class="filter-label">Class</label>
                    <input type="text" name="class" class="form-control form-control-sm"
                        placeholder="e.g. 10th" style="width:110px"
                        value="<?= htmlspecialchars($selected_class) ?>">
                </div>
                <div>
                    <label class="filter-label">Section</label>
                    <input type="text" name="section" class="form-control form-control-sm"
                        placeholder="e.g. A" style="width:80px"
                        value="<?= htmlspecialchars($selected_section) ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-dark">
                    <i class="bi bi-search"></i> View
                </button>
            </form>
            <?php if ($classes_result && $classes_result->num_rows > 0): ?>
            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                <span style="font-size:12px;font-weight:600;color:#6b7280;">Quick select:</span>
                <?php $classes_result->data_seek(0); while ($c = $classes_result->fetch_assoc()):
                    $label = htmlspecialchars($c['class_name']);
                    if (!empty(trim($c['section']))) $label .= ' – ' . htmlspecialchars($c['section']);
                    $isActive = ($selected_class == $c['class_name'] && $selected_section == $c['section']);
                ?>
                <a href="?class=<?= urlencode($c['class_name']) ?>&section=<?= urlencode($c['section']) ?>&type=<?= $selected_type ?>"
                   class="quick-btn <?= $isActive ? 'active':'' ?>"><?= $label ?></a>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($selected_class !== ''): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div style="font-size:15px;font-weight:700;color:#111827;">
                Class <?= htmlspecialchars($selected_class) ?>
                <?= $selected_section ? ' – Section '.htmlspecialchars($selected_section) : '' ?>
                <span class="badge bg-warning text-dark ms-2" style="font-size:11px;">
                    <?= ucfirst($selected_type) ?> Schedule
                </span>
            </div>
            <small class="text-muted"><?= date('l, d F Y') ?></small>
        </div>

        <?php if (!empty($timetable_data)): ?>
        <div class="card-box p-0">
            <div class="tt-wrap">
                <table class="tt-table">
                    <thead>
                        <tr>
                            <th style="width:95px;text-align:left;padding-left:14px;">Day</th>
                            <?php for ($p = 1; $p <= $max_period; $p++):
                                $pt = '';
                                foreach ($days as $d) {
                                    if (isset($timetable_data[$d][$p])) {
                                        $pt = date('g:i A', strtotime($timetable_data[$d][$p]['start_time']));
                                        break;
                                    }
                                }
                            ?>
                            <th>P<?= $p ?><?php if ($pt): ?><br><span style="font-size:9px;font-weight:400;color:#94a3b8;"><?= $pt ?></span><?php endif; ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day):
                            $has = false;
                            for ($p = 1; $p <= $max_period; $p++) {
                                if (isset($timetable_data[$day][$p])) { $has = true; break; }
                            }
                            if (!$has) continue;
                        ?>
                        <tr>
                            <td class="day-cell"><?= $day ?></td>
                            <?php for ($p = 1; $p <= $max_period; $p++): ?>
                            <td>
                                <?php if (isset($timetable_data[$day][$p])):
                                    $e = $timetable_data[$day][$p];
                                    $bg = subjectColor($e['subject'], $subject_colors);
                                ?>
                                <div class="period-card" style="background:<?= $bg ?>">
                                    <div class="period-subject"><?= htmlspecialchars($e['subject']) ?></div>
                                    <?php if ($e['teacher_name']): ?>
                                    <div class="period-meta"><i class="bi bi-person-fill" style="font-size:9px"></i> <?= htmlspecialchars($e['teacher_name']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($e['room']): ?>
                                    <div class="period-meta"><i class="bi bi-geo-alt" style="font-size:9px"></i> <?= htmlspecialchars($e['room']) ?></div>
                                    <?php endif; ?>
                                    <div class="period-time"><?= date('g:i', strtotime($e['start_time'])) ?>–<?= date('g:i A', strtotime($e['end_time'])) ?></div>
                                </div>
                                <?php else: ?><div class="empty-cell">—</div><?php endif; ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php else: ?>
        <div class="card-box"><div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No timetable found</h5>
            <p>No entries for Class <?= htmlspecialchars($selected_class) ?><?= $selected_section ? ' – Section '.$selected_section : '' ?>.<br>
            <a href="manage-schedule.php" class="btn btn-sm btn-dark mt-2"><i class="bi bi-plus-circle"></i> Add Entries</a></p>
        </div></div>
        <?php endif; ?>

        <?php else: ?>
        <div class="card-box"><div class="empty-state">
            <i class="bi bi-calendar-week"></i>
            <h5>Select a Class to View Timetable</h5>
            <p>
                <?php if ($classes_result && $classes_result->num_rows > 0): ?>
                    Use the filter above or click a quick select button.
                <?php else: ?>
                    No data yet. <a href="manage-schedule.php" class="btn btn-sm btn-dark ms-2"><i class="bi bi-plus-circle"></i> Add Entries</a>
                <?php endif; ?>
            </p>
            <?php if ($classes_result && $classes_result->num_rows > 0):
                $classes_result->data_seek(0);
                echo '<div class="d-flex flex-wrap gap-2 justify-content-center mt-3">';
                while ($c = $classes_result->fetch_assoc()):
                    $label = htmlspecialchars($c['class_name']);
                    if (!empty(trim($c['section']))) $label .= ' – ' . htmlspecialchars($c['section']);
            ?>
            <a href="?class=<?= urlencode($c['class_name']) ?>&section=<?= urlencode($c['section']) ?>&type=<?= $selected_type ?>"
               class="quick-btn"><?= $label ?></a>
            <?php endwhile; echo '</div>'; endif; ?>
        </div></div>
        <?php endif; ?>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>