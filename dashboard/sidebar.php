<?php
$current = basename($_SERVER['PHP_SELF']);

function sidebarLink($href, $icon, $label, $current, $match) {
    $active = ($current === $match)
        ? 'background:#28a745; color:white;'
        : '';
    echo "<li style='padding:2px 10px;'>
        <a href='$href' style='display:block; padding:10px 15px; color:#ccc;
           text-decoration:none; border-radius:8px; $active'>
           $icon $label
        </a>
    </li>";
}
?>

<div style="width:240px; min-height:100vh; background:#1e2a3a;
            position:fixed; top:0; left:0; padding-top:20px; overflow-y:auto;">

    <!-- Logo -->
    <div style="text-align:center; padding:15px;
                border-bottom:1px solid #2d3f53;">
        <h4 style="color:white; margin:0;">🏫 MindMerge</h4>
        <small style="color:#aaa;">SmartCampus</small>
    </div>

    <!-- User Info -->
    <div style="text-align:center; padding:15px;
                border-bottom:1px solid #2d3f53;">
        <div style="font-size:35px;">👤</div>
        <p style="color:white; margin:5px 0;">
            <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
        </p>
        <span style="background:#28a745; color:white; padding:2px 10px;
                     border-radius:10px; font-size:12px;">
            <?= ucfirst($_SESSION['role'] ?? 'Admin') ?>
        </span>
    </div>

    <!-- Menu -->
    <ul style="list-style:none; padding:10px 0; margin:0;">

        <!-- Section: Main -->
        <li style="color:#ffffff50; font-size:11px; padding:10px 20px 5px;
                   letter-spacing:1px; text-transform:uppercase;">
            Main Menu
        </li>
        <?php sidebarLink('../dashboard/dashboard.php',
            '📊', 'Dashboard', $current, 'dashboard.php'); ?>

        <!-- Section: Academic -->
        <li style="color:#ffffff50; font-size:11px; padding:10px 20px 5px;
                   letter-spacing:1px; text-transform:uppercase;">
            Academic
        </li>
        <?php sidebarLink('../students/student-list.php',
            '🎓', 'Students', $current, 'student-list.php'); ?>
        <?php sidebarLink('../teachers/teacher-list.php',
            '👨‍🏫', 'Teachers', $current, 'teacher-list.php'); ?>
        <?php sidebarLink('../attendance/mark-attendance.php',
            '📅', 'Attendance', $current, 'mark-attendance.php'); ?>
        <?php sidebarLink('../timetable/timetable.php',
            '🗓️', 'Timetable', $current, 'timetable.php'); ?>
        <?php sidebarLink('../exams/exam-schedule.php',
            '📝', 'Exams', $current, 'exam-schedule.php'); ?>

        <!-- Section: Finance -->
        <li style="color:#ffffff50; font-size:11px; padding:10px 20px 5px;
                   letter-spacing:1px; text-transform:uppercase;">
            Finance
        </li>
        <?php sidebarLink('../fees/fees.php',
            '💰', 'Fees', $current, 'fees.php'); ?>

        <!-- Section: Communication -->
        <li style="color:#ffffff50; font-size:11px; padding:10px 20px 5px;
                   letter-spacing:1px; text-transform:uppercase;">
            Communication
        </li>
        <?php sidebarLink('../notifications/notifications.php',
            '🔔', 'Notifications', $current, 'notifications.php'); ?>
        <?php sidebarLink('../reports/reports.php',
            '📊', 'Reports', $current, 'reports.php'); ?>

        <!-- Section: Admin (only if role is admin) -->
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <li style="color:#ffffff50; font-size:11px; padding:10px 20px 5px;
                   letter-spacing:1px; text-transform:uppercase;">
            Admin
        </li>
        <?php sidebarLink('../admin/admin.php',
            '⚙️', 'Roles & Permissions', $current, 'admin.php'); ?>
        <?php sidebarLink('../admin/settings.php',
            '🔧', 'System Settings', $current, 'settings.php'); ?>
        <?php sidebarLink('../admin/manage-users.php',
            '👥', 'Manage Users', $current, 'manage-users.php'); ?>
        <?php endif; ?>

        <!-- Logout -->
        <li style="padding:10px 10px; margin-top:10px;">
            <a href="../auth/logout.php"
               style="display:block; padding:10px 15px; color:#ff6b6b;
                      text-decoration:none; border-radius:8px;
                      border:1px solid #ff6b6b;">
                🚪 Logout
            </a>
        </li>

    </ul>
</div>

<!-- Push main content to the right -->
<div style="margin-left:240px;">