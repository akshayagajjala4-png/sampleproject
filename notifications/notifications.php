<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$role      = $_SESSION['role']      ?? '';
$user_name = $_SESSION['user_name'] ?? 'User';
$user_id   = $_SESSION['user_id']   ?? 0;

// Handle delete
if (isset($_GET['delete']) && $role === 'admin') {
    mysqli_query($conn, "DELETE FROM notifications WHERE id=" . intval($_GET['delete']));
    header("Location: notifications.php?deleted=1");
    exit();
}

// Handle mark single read
if (isset($_GET['mark_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id=" . intval($_GET['mark_read']));
    header("Location: notifications.php");
    exit();
}

// Mark all read
if (isset($_GET['mark_all_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read=1");
    header("Location: notifications.php");
    exit();
}

// Fetch notifications based on role
if ($role === 'admin') {
    $result = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC");
} elseif ($role === 'parent') {
    $result = mysqli_query($conn, "SELECT * FROM notifications WHERE parent_id=$user_id OR (parent_id IS NULL AND student_id IS NULL) ORDER BY created_at DESC");
} elseif ($role === 'student') {
    $result = mysqli_query($conn, "SELECT * FROM notifications WHERE student_id=$user_id OR (parent_id IS NULL AND student_id IS NULL) ORDER BY created_at DESC");
} else {
    $result = mysqli_query($conn, "SELECT * FROM notifications WHERE parent_id IS NULL AND student_id IS NULL ORDER BY created_at DESC");
}

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

$unread_count = count(array_filter($notifications, fn($n) => !$n['is_read']));
$counts = array_count_values(array_column($notifications, 'type'));

$typeConfig = [
    'notice' => ['icon' => 'bi-bullhorn',         'color' => '#6366f1', 'bg' => '#e0e7ff', 'label' => 'Notice'],
    'alert'  => ['icon' => 'bi-exclamation-triangle', 'color' => '#ef4444', 'bg' => '#fee2e2', 'label' => 'Alert'],
    'event'  => ['icon' => 'bi-calendar-event',   'color' => '#f59e0b', 'bg' => '#fef3c7', 'label' => 'Event'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications – MindMerge SmartCampus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f0f4f8; margin: 0; }
.sidebar {
    width: 250px; background: #1a1a2e; min-height: 100vh;
    position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 100;
}
.sidebar-brand { color: white; font-size: 18px; font-weight: bold;
    padding: 15px 20px; border-bottom: 1px solid #ffffff20; margin-bottom: 10px; }
.sidebar a { display: block; color: #ffffffaa; padding: 12px 20px;
    text-decoration: none; font-size: 14px; transition: 0.3s; }
.sidebar a:hover { background: #ffffff15; color: white; padding-left: 25px; }
.sidebar a.active { background: #ffffff20; color: white; border-left: 3px solid #ffc107; }
.sidebar .menu-title { color: #ffffff50; font-size: 11px; padding: 10px 20px 5px;
    text-transform: uppercase; letter-spacing: 1px; }
.main-content { margin-left: 250px; }
.top-navbar { background: white; padding: 15px 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex; justify-content: space-between; align-items: center; }

/* Stats */
.stat-card { background: white; border-radius: 15px; border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08); padding: 20px; }
.stat-icon-box { width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }

/* Filter tabs */
.filter-tab { padding: 6px 16px; border-radius: 20px; font-size: .82rem;
    font-weight: 600; cursor: pointer; border: 1.5px solid #dee2e6;
    background: white; color: #6c757d; transition: all .15s; }
.filter-tab.active, .filter-tab:hover { background: #1a1a2e; color: white; border-color: #1a1a2e; }

/* Notification card */
.notif-card { background: white; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 16px 20px;
    display: flex; align-items: flex-start; gap: 14px;
    border-left: 4px solid transparent; transition: box-shadow .15s; margin-bottom: 12px; }
.notif-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.notif-card.unread { background: #fafbff; }
.notif-card.type-notice { border-color: #6366f1; }
.notif-card.type-alert  { border-color: #ef4444; }
.notif-card.type-event  { border-color: #f59e0b; }
.notif-icon { width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.notif-body { flex: 1; min-width: 0; }
.notif-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
.notif-badge { font-size: .7rem; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.unread-dot { width: 8px; height: 8px; border-radius: 50%;
    background: #6366f1; flex-shrink: 0; margin-top: 6px; }
.notif-message { font-size: .875rem; color: #475569; line-height: 1.55; margin: 4px 0 6px; }
.notif-meta { font-size: .77rem; color: #94a3b8; }
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8;
    background: white; border-radius: 14px; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">🎓 MindMerge</div>
    <div class="menu-title">Main Menu</div>
    <a href="../dashboard/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <div class="menu-title">Academic</div>
    <a href="../students/student-list.php"><i class="bi bi-people"></i> Students</a>
    <a href="../teachers/teacher-list.php"><i class="bi bi-person-badge"></i> Teachers</a>
    <a href="../attendance/mark-attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="../timetable/timetable.php"><i class="bi bi-clock"></i> Timetable</a>
    <a href="../exams/add-marks.php"><i class="bi bi-pencil-square"></i> Exams</a>
    <div class="menu-title">Finance</div>
    <a href="../fees/fees.php"><i class="bi bi-cash"></i> Fees</a>
    <div class="menu-title">Communication</div>
    <a href="notifications.php" class="active"><i class="bi bi-bell"></i> Notifications</a>
    <a href="../reports/reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
    <?php if ($role === 'admin'): ?>
    <div class="menu-title">Admin</div>
    <a href="../admin/manage-users.php"><i class="bi bi-gear"></i> Manage Users</a>
    <?php endif; ?>
</div>

<!-- Main -->
<div class="main-content">
    <div class="top-navbar">
        <h5 class="mb-0">🔔 Notifications</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= htmlspecialchars($user_name) ?></span>
            <span class="badge bg-warning text-dark text-capitalize"><?= htmlspecialchars($role) ?></span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-trash"></i> Notification deleted.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-box" style="background:#e0e7ff">
                        <i class="bi bi-bell" style="color:#6366f1"></i>
                    </div>
                    <div>
                        <div style="font-size:1.5rem;font-weight:800"><?= count($notifications) ?></div>
                        <div style="font-size:.78rem;color:#64748b">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-box" style="background:#fee2e2">
                        <i class="bi bi-circle-fill" style="color:#ef4444;font-size:.8rem"></i>
                    </div>
                    <div>
                        <div style="font-size:1.5rem;font-weight:800;color:#ef4444"><?= $unread_count ?></div>
                        <div style="font-size:.78rem;color:#64748b">Unread</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-box" style="background:#d1fae5">
                        <i class="bi bi-check2-all" style="color:#10b981"></i>
                    </div>
                    <div>
                        <div style="font-size:1.5rem;font-weight:800;color:#10b981"><?= count($notifications) - $unread_count ?></div>
                        <div style="font-size:.78rem;color:#64748b">Read</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-box" style="background:#fef3c7">
                        <i class="bi bi-calendar-event" style="color:#f59e0b"></i>
                    </div>
                    <div>
                        <div style="font-size:1.5rem;font-weight:800"><?= $counts['event'] ?? 0 ?></div>
                        <div style="font-size:.78rem;color:#64748b">Events</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex gap-2 flex-wrap">
                <button class="filter-tab active" onclick="filterNotifs('all',this)">All (<?= count($notifications) ?>)</button>
                <button class="filter-tab" onclick="filterNotifs('notice',this)">📢 Notices (<?= $counts['notice'] ?? 0 ?>)</button>
                <button class="filter-tab" onclick="filterNotifs('alert',this)">🚨 Alerts (<?= $counts['alert'] ?? 0 ?>)</button>
                <button class="filter-tab" onclick="filterNotifs('event',this)">📅 Events (<?= $counts['event'] ?? 0 ?>)</button>
            </div>
            <div class="d-flex gap-2">
                <?php if ($unread_count > 0): ?>
                <a href="notifications.php?mark_all_read=1" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check2-all"></i> Mark All Read
                </a>
                <?php endif; ?>
                <?php if ($role === 'admin'): ?>
                <a href="send-alert.php" class="btn btn-sm text-white" style="background:#1a1a2e">
                    <i class="bi bi-send"></i> Send Notification
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notification List -->
        <div id="notifList">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="bi bi-bell-slash" style="font-size:3rem;opacity:.3;display:block;margin-bottom:12px"></i>
                <p>No notifications yet.
                    <?php if ($role === 'admin'): ?>
                    <a href="send-alert.php" style="color:#6366f1;font-weight:600">Send one now →</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n):
                $tc = $typeConfig[$n['type']] ?? $typeConfig['notice'];
                $isUnread = !$n['is_read'];
                if ($n['student_id'])    $audience = 'Student';
                elseif ($n['parent_id']) $audience = 'Parent';
                else                     $audience = 'Everyone';
            ?>
            <div class="notif-card type-<?= htmlspecialchars($n['type']) ?> <?= $isUnread ? 'unread' : '' ?>"
                 data-type="<?= htmlspecialchars($n['type']) ?>">
                <div class="notif-icon" style="background:<?= $tc['bg'] ?>">
                    <i class="bi <?= $tc['icon'] ?>" style="color:<?= $tc['color'] ?>"></i>
                </div>
                <div class="notif-body">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <?php if ($isUnread): ?><div class="unread-dot"></div><?php endif; ?>
                        <span class="notif-title"><?= htmlspecialchars($n['title']) ?></span>
                        <span class="notif-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>"><?= $tc['label'] ?></span>
                        <span class="notif-badge" style="background:#f1f5f9;color:#475569"><?= $audience ?></span>
                    </div>
                    <div class="notif-message"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
                    <div class="notif-meta"><i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></div>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <?php if ($isUnread): ?>
                    <a href="notifications.php?mark_read=<?= $n['id'] ?>" class="btn btn-sm btn-success" title="Mark as Read">
                        <i class="bi bi-check"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                    <a href="notifications.php?delete=<?= $n['id'] ?>"
                       onclick="return confirm('Delete this notification?')"
                       class="btn btn-sm btn-danger" title="Delete">
                        <i class="bi bi-trash"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterNotifs(type, btn) {
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.notif-card').forEach(card => {
        card.style.display = (type === 'all' || card.dataset.type === type) ? 'flex' : 'none';
    });
}
</script>
</body>
</html>