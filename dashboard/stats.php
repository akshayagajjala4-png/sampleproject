<?php
require_once '../config/database.php';

// Total Students
$totalStudents = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM students")
)['total'];

// Total Teachers
$totalTeachers = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM teachers")
)['total'];

// Total Classes
$totalClasses = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM classes")
)['total'];

// Total Users
$totalUsers = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM users")
)['total'];

// Today's Attendance
// Will work after attendance module is built
$totalAttendance = 0;

// Total Results
// Will work after exam module is built
$totalResults = 0;

// Total Notifications
// Will work after notifications module is built
$totalNotifications = 0;
?>
