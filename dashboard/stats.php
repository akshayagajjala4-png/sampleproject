<?php
require_once '../config/database.php';

// Check if a table exists before querying it
function tableExists($conn, $table) {
    $db     = mysqli_get_host_info($conn); // not what we need
    $result = mysqli_query($conn,
        "SELECT COUNT(*) as cnt
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = '" . mysqli_real_escape_string($conn, $table) . "'
         LIMIT 1");
    if (!$result) return false;
    $row = mysqli_fetch_assoc($result);
    return (int)($row['cnt'] ?? 0) > 0;
}

// Safe COUNT — returns 0 if table missing or query fails
function safeCount($conn, $table, $where = '1') {
    if (!tableExists($conn, $table)) return 0;
    $result = mysqli_query($conn,
        "SELECT COUNT(*) as total FROM `$table` WHERE $where");
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

// Safe SUM — returns 0 if table missing or query fails
function safeSum($conn, $table, $column, $where = '1') {
    if (!tableExists($conn, $table)) return 0;
    $result = mysqli_query($conn,
        "SELECT COALESCE(SUM(`$column`), 0) as total FROM `$table` WHERE $where");
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return (float)($row['total'] ?? 0);
}

// ── Counts ──────────────────────────────────────────
$totalStudents    = safeCount($conn, 'students');
$totalTeachers    = safeCount($conn, 'teachers');
$totalClasses     = safeCount($conn, 'classes');
$totalUsers       = safeCount($conn, 'users');
$totalResults     = safeCount($conn, 'results');
$totalNotifications = safeCount($conn, 'notifications');

// Unread notifications (column may not exist yet)
$unreadNotifications = 0;
if (tableExists($conn, 'notifications')) {
    // Check column exists
    $col = mysqli_query($conn,
        "SELECT COUNT(*) as cnt
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name   = 'notifications'
           AND column_name  = 'is_read'");
    if ($col && mysqli_fetch_assoc($col)['cnt'] > 0) {
        $unreadNotifications = safeCount($conn, 'notifications', 'is_read = 0');
    }
}

// ── Attendance ───────────────────────────────────────
$today = date('Y-m-d');
$totalAttendance        = safeCount($conn, 'attendance', "date = '$today' AND status = 'present'");
$totalAttendanceRecords = safeCount($conn, 'attendance', "date = '$today'");
$attendancePct = $totalAttendanceRecords > 0
    ? round(($totalAttendance / $totalAttendanceRecords) * 100)
    : 0;

// ── Fees ─────────────────────────────────────────────
$totalFees   = safeSum($conn,   'fees', 'amount_paid');
$pendingFees = safeCount($conn, 'fees', "status = 'pending'");

// ── Monthly enrollment chart (last 6 months) ─────────
$monthlyData = [];
$hasCreatedAt = false;
if (tableExists($conn, 'students')) {
    $col = mysqli_query($conn,
        "SELECT COUNT(*) as cnt
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name   = 'students'
           AND column_name  = 'created_at'");
    $hasCreatedAt = ($col && mysqli_fetch_assoc($col)['cnt'] > 0);
}

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M',   strtotime("-$i months"));
    $cnt   = 0;
    if ($hasCreatedAt) {
        $res = mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM students
             WHERE DATE_FORMAT(created_at,'%Y-%m') = '$month'");
        if ($res) $cnt = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
    }
    $monthlyData[] = ['label' => $label, 'count' => $cnt];
}
?>