<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$role      = $_SESSION['role']      ?? '';
$user_name = $_SESSION['user_name'] ?? 'User';
$user_id   = $_SESSION['user_id']   ?? 0;

if ($role !== 'admin') {
    header("Location: notifications.php");
    exit();
}

$success = $error = '';

// Fetch students and parents
$studentsResult = mysqli_query($conn, "SELECT id, name FROM students ORDER BY name");
$students = [];
while ($row = mysqli_fetch_assoc($studentsResult)) $students[] = $row;

$parentsResult = mysqli_query($conn, "SELECT id, name FROM parents ORDER BY name");
$parents = [];
while ($row = mysqli_fetch_assoc($parentsResult)) $parents[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $title      = trim($_POST['title']);
    $message    = trim($_POST['message']);
    $type       = $_POST['type'];
    $target     = $_POST['target'];
    $student_id = (!empty($_POST["student_id"]) && intval($_POST["student_id"]) > 0) ? intval($_POST["student_id"]) : null;
    $parent_id  = (!empty($_POST["parent_id"])  && intval($_POST["parent_id"])  > 0) ? intval($_POST["parent_id"])  : null;

    $allowed_types = ['notice', 'alert', 'event'];

    if ($title && $message && in_array($type, $allowed_types)) {
        if ($target === 'student') {
            $parent_id = null;
        } elseif ($target === 'parent') {
            $student_id = null;
        } else {
            $student_id = null;
            $parent_id  = null;
        }

        $title   = mysqli_real_escape_string($conn, $title);
        $message = mysqli_real_escape_string($conn, $message);
        $type    = mysqli_real_escape_string($conn, $type);

        $sid = ($student_id !== null) ? $student_id : 'NULL';
        $pid = ($parent_id  !== null) ? $parent_id  : 'NULL';
        $sql = "INSERT INTO notifications (parent_id, student_id, title, message, type, is_read, created_at)
                VALUES ($pid, $sid, '$title', '$message', '$type', 0, NOW())";

        if (mysqli_query($conn, $sql)) {
            $success = "Notification sent successfully!";
        } else {
            $error = "Failed: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Recent 10
$recentResult = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$recent = [];
while ($row = mysqli_fetch_assoc($recentResult)) $recent[] = $row;

$typeConfig = [
    'notice' => ['icon' => 'bi-bullhorn',             'color' => '#6366f1', 'bg' => '#e0e7ff', 'label' => 'Notice'],
    'alert'  => ['icon' => 'bi-exclamation-triangle', 'color' => '#ef4444', 'bg' => '#fee2e2', 'label' => 'Alert'],
    'event'  => ['icon' => 'bi-calendar-event',       'color' => '#f59e0b', 'bg' => '#fef3c7', 'label' => 'Event'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Notification – MindMerge SmartCampus</title>
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

.card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }

/* Type selector */
.type-option { border: 2px solid #dee2e6; border-radius: 12px; padding: 14px 10px;
    text-align: center; cursor: pointer; transition: all .15s; background: white; }
.type-option:hover { border-color: #1a1a2e; }
.type-option.selected { border-color: #1a1a2e; background: #f0f4f8; }
.type-option .t-icon { font-size: 1.6rem; margin-bottom: 5px; }
.type-option .t-label { font-size: .8rem; font-weight: 700; color: #475569; }

/* Audience */
.audience-option { border: 2px solid #dee2e6; border-radius: 10px; padding: 12px 8px;
    cursor: pointer; transition: all .15s; text-align: center; font-size: .82rem;
    font-weight: 600; color: #475569; }
.audience-option:hover, .audience-option.selected { border-color: #1a1a2e; background: #1a1a2e; color: white; }

/* Preview */
.preview-card { border-radius: 12px; padding: 16px; border-left: 4px solid #6366f1;
    background: #fafbff; display: none; }
.preview-card.visible { display: block; }

/* Recent items */
.recent-item { display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.recent-item:last-child { border-bottom: none; }
.r-icon { width: 36px; height: 36px; border-radius: 8px; display: flex;
    align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }

.char-count { font-size: .75rem; color: #94a3b8; text-align: right; }
.char-count.warn { color: #f59e0b; }
.char-count.over { color: #ef4444; }
.target-wrap { display: none; }
.target-wrap.visible { display: block; }
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
    <a href="../timetable/timetable.php"><i class="bi bi-clock"></i> Timetable</a>
    <a href="../exams/add-marks.php"><i class="bi bi-pencil-square"></i> Exams</a>
    <div class="menu-title">Finance</div>
    <a href="../fees/fees.php"><i class="bi bi-cash"></i> Fees</a>
    <div class="menu-title">Communication</div>
    <a href="notifications.php" class="active"><i class="bi bi-bell"></i> Notifications</a>
    <a href="../reports/reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
    <div class="menu-title">Admin</div>
    <a href="../admin/manage-users.php"><i class="bi bi-gear"></i> Manage Users</a>
</div>

<div class="main-content">
    <div class="top-navbar">
        <h5 class="mb-0">📢 Send Notification</h5>
        <div class="d-flex align-items-center gap-3">
            <span>👤 <?= htmlspecialchars($user_name) ?></span>
            <span class="badge bg-warning text-dark text-capitalize"><?= htmlspecialchars($role) ?></span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container-fluid p-4">

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <a href="notifications.php" class="btn btn-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left"></i> Back to Notifications
        </a>

        <div class="row g-4">

            <!-- Compose Form -->
            <div class="col-lg-7">
                <div class="card p-4">
                    <h6 class="fw-bold mb-4"><i class="bi bi-send me-2" style="color:#6366f1"></i>Compose Notification</h6>

                    <form method="POST">

                        <!-- Type -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Notification Type</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="type-option selected" onclick="selectType('notice',this)">
                                        <div class="t-icon">📢</div>
                                        <div class="t-label">Notice</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="type-option" onclick="selectType('alert',this)">
                                        <div class="t-icon">🚨</div>
                                        <div class="t-label">Alert</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="type-option" onclick="selectType('event',this)">
                                        <div class="t-icon">📅</div>
                                        <div class="t-label">Event</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="type" id="typeInput" value="notice">
                        </div>

                        <!-- Audience -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Send To</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="audience-option selected" onclick="selectAudience('all',this)">
                                        <i class="bi bi-globe d-block mb-1"></i> Everyone
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="audience-option" onclick="selectAudience('student',this)">
                                        <i class="bi bi-mortarboard d-block mb-1"></i> Student
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="audience-option" onclick="selectAudience('parent',this)">
                                        <i class="bi bi-people d-block mb-1"></i> Parent
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="target" id="targetInput" value="all">
                        </div>

                        <!-- Student picker -->
                        <div class="target-wrap mb-3" id="studentPicker">
                            <label class="form-label fw-semibold small">Select Student</label>
                            <select name="student_id" class="form-select">
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Parent picker -->
                        <div class="target-wrap mb-3" id="parentPicker">
                            <label class="form-label fw-semibold small">Select Parent</label>
                            <select name="parent_id" class="form-select">
                                <option value="">-- Select Parent --</option>
                                <?php foreach ($parents as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="titleInput" class="form-control"
                                   maxlength="100" placeholder="e.g. School closed on Friday"
                                   oninput="updatePreview()" required>
                        </div>

                        <!-- Message -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
                            <textarea name="message" id="messageInput" class="form-control"
                                      rows="4" maxlength="500"
                                      placeholder="Write your notification message here..."
                                      oninput="updatePreview();countChars(this)" required></textarea>
                            <div class="char-count mt-1" id="charCount">0 / 500</div>
                        </div>

                        <!-- Live Preview -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Live Preview</label>
                            <div class="preview-card" id="previewCard">
                                <div id="previewBadge" class="badge mb-2"></div>
                                <div id="previewTitle" class="fw-bold"></div>
                                <div id="previewMessage" class="text-muted small mt-1"></div>
                                <div class="text-muted mt-2" style="font-size:.75rem">
                                    <i class="bi bi-clock me-1"></i> Just now
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="send_notification"
                                class="btn w-100 text-white fw-bold" style="background:#1a1a2e;padding:12px">
                            <i class="bi bi-send me-2"></i> Send Notification
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recently Sent -->
            <div class="col-lg-5">
                <div class="card p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2" style="color:#6366f1"></i>Recently Sent (<?= count($recent) ?>)</h6>
                    <?php if (empty($recent)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px"></i>
                        No notifications yet
                    </div>
                    <?php else: ?>
                        <?php foreach ($recent as $n):
                            $tc = $typeConfig[$n['type']] ?? $typeConfig['notice'];
                            if ($n['student_id'])    $audience = 'Student';
                            elseif ($n['parent_id']) $audience = 'Parent';
                            else                     $audience = 'Everyone';
                        ?>
                        <div class="recent-item">
                            <div class="r-icon" style="background:<?= $tc['bg'] ?>">
                                <i class="bi <?= $tc['icon'] ?>" style="color:<?= $tc['color'] ?>"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div class="fw-semibold" style="font-size:.875rem"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="d-flex gap-1 mt-1 flex-wrap">
                                    <span class="badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>"><?= $tc['label'] ?></span>
                                    <span class="badge bg-light text-secondary"><?= $audience ?></span>
                                    <span class="text-muted" style="font-size:.72rem"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const typeColors = {
    notice: { color:'#6366f1', bg:'#e0e7ff', label:'📢 Notice' },
    alert:  { color:'#ef4444', bg:'#fee2e2', label:'🚨 Alert'  },
    event:  { color:'#f59e0b', bg:'#fef3c7', label:'📅 Event'  },
};
let currentType = 'notice';

function selectType(type, el) {
    document.querySelectorAll('.type-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('typeInput').value = type;
    currentType = type;
    updatePreview();
}

function selectAudience(role, el) {
    document.querySelectorAll('.audience-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('targetInput').value = role;
    document.getElementById('studentPicker').classList.toggle('visible', role === 'student');
    document.getElementById('parentPicker').classList.toggle('visible',  role === 'parent');
}

function updatePreview() {
    const title   = document.getElementById('titleInput').value.trim();
    const message = document.getElementById('messageInput').value.trim();
    const tc      = typeColors[currentType];
    const card    = document.getElementById('previewCard');
    if (title || message) {
        card.classList.add('visible');
        card.style.borderColor = tc.color;
        card.style.background  = tc.bg + '55';
        const badge = document.getElementById('previewBadge');
        badge.textContent  = tc.label;
        badge.style.cssText = `background:${tc.bg};color:${tc.color}`;
        document.getElementById('previewTitle').textContent   = title   || 'Your title...';
        document.getElementById('previewMessage').textContent = message || 'Your message...';
    } else {
        card.classList.remove('visible');
    }
}

function countChars(el) {
    const len = el.value.length;
    const cc  = document.getElementById('charCount');
    cc.textContent = `${len} / 500`;
    cc.className = 'char-count mt-1' + (len > 450 ? ' over' : len > 350 ? ' warn' : '');
}
</script>
</body>
</html>