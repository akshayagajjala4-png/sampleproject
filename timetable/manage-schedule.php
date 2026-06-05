<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
include('../config/database.php');

$message  = "";
$role      = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['name'] ?? 'Admin User';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM timetable WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = 'success:Entry deleted successfully.';
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $r  = $conn->query("SELECT * FROM timetable WHERE id = $id");
    $edit_data = $r->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name    = $conn->real_escape_string(trim($_POST['class_name']));
    $section       = $conn->real_escape_string(trim($_POST['section']));
    $day           = $conn->real_escape_string($_POST['day']);
    $period        = intval($_POST['period']);
    $start_time    = $conn->real_escape_string($_POST['start_time']);
    $end_time      = $conn->real_escape_string($_POST['end_time']);
    $subject       = $conn->real_escape_string(trim($_POST['subject']));
    $teacher_name  = $conn->real_escape_string(trim($_POST['teacher_name']));
    $room          = $conn->real_escape_string(trim($_POST['room']));
    $schedule_type = $conn->real_escape_string($_POST['schedule_type']);

    if (isset($_POST['id']) && $_POST['id'] != '') {
        $id = intval($_POST['id']);
        $conn->query("UPDATE timetable SET
            class_name='$class_name', section='$section', day='$day',
            period='$period', start_time='$start_time', end_time='$end_time',
            subject='$subject', teacher_name='$teacher_name',
            room='$room', schedule_type='$schedule_type'
            WHERE id=$id");
        $message = 'success:Timetable updated successfully.';
    } else {
        $conn->query("INSERT INTO timetable
            (class_name, section, day, period, start_time, end_time, subject, teacher_name, room, schedule_type)
            VALUES
            ('$class_name','$section','$day','$period','$start_time','$end_time','$subject','$teacher_name','$room','$schedule_type')");
        $message = 'success:Timetable entry added successfully.';
    }
    $edit_data = null;
}

$filter_type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : 'class';
$entries = $conn->query("SELECT * FROM timetable WHERE schedule_type='$filter_type'
    ORDER BY class_name, section,
    FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), period");
$total = $conn->query("SELECT COUNT(*) as c FROM timetable")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule – MindMerge SmartCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; }
        .sidebar { width: 160px; min-height: 100vh; background: #1e2237; position: fixed; top: 0; left: 0; z-index: 200; display: flex; flex-direction: column; overflow-y: auto; }
        .sidebar .brand { padding: 18px 14px 16px; color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.07); }
        .sidebar .brand i { color: #a78bfa; font-size: 16px; }
        .sidebar .section-label { font-size: 10px; color: #6b7280; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; padding: 16px 14px 5px; }
        .sidebar a { display: flex; align-items: center; gap: 9px; padding: 8px 14px; color: #9ca3af; text-decoration: none; font-size: 13px; transition: background 0.15s, color 0.15s; }
        .sidebar a i { font-size: 14px; width: 16px; text-align: center; flex-shrink: 0; }
        .sidebar a:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .sidebar a.active { background: #3b4fd8; color: #fff; font-weight: 500; }
        .topbar { margin-left: 160px; background: #fff; padding: 11px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 100; }
        .topbar .page-title { font-size: 16px; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px; }
        .user-section { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 32px; height: 32px; background: #3b4fd8; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; font-weight: 700; }
        .user-name { font-size: 13px; font-weight: 500; color: #374151; }
        .role-badge { background: #f59e0b; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 4px; text-transform: uppercase; }
        .btn-logout { background: #ef4444; color: #fff; border: none; padding: 5px 14px; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; }
        .btn-logout:hover { background: #dc2626; color: #fff; }
        .main-content { margin-left: 160px; padding: 24px; }
        .card { background: #fff; border: none; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.07); }
        .card-head { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
        .card-head h6 { font-size: 14px; font-weight: 700; color: #111827; margin: 0; }
        .form-label-sm { font-size: 11px; font-weight: 700; color: #6b7280; letter-spacing: 0.05em; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .form-control, .form-select { font-size: 13px !important; }
        .type-tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 0; }
        .type-tab { padding: 9px 20px; font-size: 13px; font-weight: 500; color: #6b7280; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.15s; }
        .type-tab:hover { color: #374151; }
        .type-tab.active { color: #3b4fd8; border-bottom-color: #3b4fd8; font-weight: 600; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; padding: 10px 12px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
        .data-table td { font-size: 13px; color: #374151; padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: #fafbff; }
        .badge-class { background: #ede9fe; color: #6d28d9; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; }
        .badge-period { background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; }
        .alert-msg { padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .empty-row td { text-align: center; padding: 48px 20px; color: #9ca3af; font-size: 13px; }
        .empty-row i { font-size: 32px; display: block; margin-bottom: 10px; color: #d1d5db; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand"><i class="bi bi-mortarboard-fill"></i>MindMerge</div>
    <span class="section-label">Main Menu</span>
    <a href="../dashboard/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <span class="section-label">Academic</span>
    <a href="../students/student-list.php"><i class="bi bi-people"></i> Students</a>
    <a href="../teachers/teacher-list.php"><i class="bi bi-person-badge"></i> Teachers</a>
    <a href="../attendance/mark-attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="timetable.php" class="active"><i class="bi bi-clock"></i> Timetable</a>
    <a href="../exams/add-marks.php"><i class="bi bi-pencil-square"></i> Exams</a>
    <span class="section-label">Finance</span>
    <a href="../fees/fees.php"><i class="bi bi-cash-coin"></i> Fees</a>
    <span class="section-label">Communication</span>
    <a href="../notifications/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
    <a href="../reports/reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
    <span class="section-label">Admin</span>
    <a href="../admin/manage-users.php"><i class="bi bi-shield-check"></i> Manage Users</a>
    <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="topbar">
    <div class="page-title"><span>🗓️</span> Manage Timetable</div>
    <div class="user-section">
        <div class="avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        <span class="role-badge"><?= strtoupper($role) ?></span>
        <a href="../auth/logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="mb-0" style="font-size:13px;color:#6b7280;">
            Add and manage schedules. &nbsp;Total entries: <strong style="color:#111827"><?= $total ?></strong>
        </p>
        <a href="timetable.php" style="background:#f3f4f6;border:1px solid #e5e7eb;padding:5px 14px;border-radius:6px;font-size:13px;color:#374151;text-decoration:none;">
            <i class="bi bi-eye"></i> View Timetable
        </a>
    </div>

    <?php if ($message): [$type, $text] = explode(':', $message, 2); ?>
    <div class="alert-msg alert-<?= $type ?>">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($text) ?>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-head">
            <h6>
                <?= $edit_data
                    ? '<i class="bi bi-pencil" style="color:#f59e0b"></i> Edit Timetable Entry'
                    : '<i class="bi bi-plus-circle" style="color:#3b4fd8"></i> Add New Timetable Entry' ?>
            </h6>
            <?php if ($edit_data): ?>
            <a href="manage-schedule.php" style="font-size:12px;color:#6b7280;text-decoration:none;">✕ Cancel</a>
            <?php endif; ?>
        </div>
        <div style="padding:20px">
            <form method="POST">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label-sm">Schedule Type *</label>
                        <select name="schedule_type" class="form-select form-select-sm" required>
                            <option value="class"   <?= ($edit_data && $edit_data['schedule_type']=='class')   ? 'selected':'' ?>>📚 Class Schedule</option>
                            <option value="teacher" <?= ($edit_data && $edit_data['schedule_type']=='teacher') ? 'selected':'' ?>>👨‍🏫 Teacher Schedule</option>
                            <option value="exam"    <?= ($edit_data && $edit_data['schedule_type']=='exam')    ? 'selected':'' ?>>📝 Exam Schedule</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Class *</label>
                        <input type="text" name="class_name" class="form-control form-control-sm" placeholder="e.g. 10th" required value="<?= $edit_data ? htmlspecialchars($edit_data['class_name']) : '' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label-sm">Section</label>
                        <input type="text" name="section" class="form-control form-control-sm" placeholder="A" value="<?= $edit_data ? htmlspecialchars($edit_data['section']) : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Day *</label>
                        <select name="day" class="form-select form-select-sm" required>
                            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
                            <option value="<?= $d ?>" <?= ($edit_data && $edit_data['day']==$d) ? 'selected':'' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label-sm">Period *</label>
                        <input type="number" name="period" class="form-control form-control-sm" min="1" max="10" placeholder="1" required value="<?= $edit_data ? $edit_data['period'] : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Subject *</label>
                        <input type="text" name="subject" class="form-control form-control-sm" placeholder="e.g. Mathematics" required value="<?= $edit_data ? htmlspecialchars($edit_data['subject']) : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Start Time *</label>
                        <input type="time" name="start_time" class="form-control form-control-sm" required value="<?= $edit_data ? $edit_data['start_time'] : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">End Time *</label>
                        <input type="time" name="end_time" class="form-control form-control-sm" required value="<?= $edit_data ? $edit_data['end_time'] : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Teacher Name</label>
                        <input type="text" name="teacher_name" class="form-control form-control-sm" placeholder="e.g. Mr. Rahul" value="<?= $edit_data ? htmlspecialchars($edit_data['teacher_name']) : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Room</label>
                        <input type="text" name="room" class="form-control form-control-sm" placeholder="e.g. Room 101" value="<?= $edit_data ? htmlspecialchars($edit_data['room']) : '' ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" style="background:#3b4fd8;color:#fff;border:none;padding:7px 24px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">
                            <i class="bi bi-<?= $edit_data ? 'check-lg' : 'plus-lg' ?>"></i>
                            <?= $edit_data ? 'Update Entry' : 'Add Entry' ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="type-tabs">
        <a href="?type=class"   class="type-tab <?= $filter_type=='class'   ? 'active':'' ?>">📚 Class Schedule</a>
        <a href="?type=teacher" class="type-tab <?= $filter_type=='teacher' ? 'active':'' ?>">👨‍🏫 Teacher Schedule</a>
        <a href="?type=exam"    class="type-tab <?= $filter_type=='exam'    ? 'active':'' ?>">📝 Exam Schedule</a>
    </div>

    <div class="card" style="border-radius:0 10px 10px 10px;">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th><th>Class</th><th>Section</th><th>Day</th>
                        <th>Period</th><th>Time</th><th>Subject</th>
                        <th>Teacher</th><th>Room</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sn = 1;
                    if ($entries && $entries->num_rows > 0):
                        while ($row = $entries->fetch_assoc()):
                    ?>
                    <tr>
                        <td style="color:#9ca3af;"><?= $sn++ ?></td>
                        <td><span class="badge-class"><?= htmlspecialchars($row['class_name']) ?></span></td>
                        <td><?= htmlspecialchars($row['section']) ?: '<span style="color:#d1d5db">—</span>' ?></td>
                        <td><?= $row['day'] ?></td>
                        <td><span class="badge-period">P<?= $row['period'] ?></span></td>
                        <td style="font-size:12px;color:#6b7280;"><?= date('h:i A', strtotime($row['start_time'])) ?> – <?= date('h:i A', strtotime($row['end_time'])) ?></td>
                        <td style="font-weight:600;color:#111827;"><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['teacher_name']) ?: '<span style="color:#d1d5db">—</span>' ?></td>
                        <td><?= htmlspecialchars($row['room']) ?: '<span style="color:#d1d5db">—</span>' ?></td>
                        <td>
                            <a href="?edit=<?= $row['id'] ?>&type=<?= $filter_type ?>"
                               style="background:#fef9c3;color:#92400e;border:1px solid #fde68a;padding:3px 10px;border-radius:5px;font-size:12px;text-decoration:none;display:inline-block;margin-right:4px;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?delete=<?= $row['id'] ?>&type=<?= $filter_type ?>"
                               style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:3px 10px;border-radius:5px;font-size:12px;text-decoration:none;display:inline-block;"
                               onclick="return confirm('Delete this entry?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr class="empty-row">
                        <td colspan="10">
                            <i class="bi bi-inbox"></i>
                            No entries yet. Fill the form above to add your first entry.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>