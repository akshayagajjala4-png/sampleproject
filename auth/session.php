<?php
session_start();

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Redirect if not logged in
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Admin only
function adminOnly() {
    redirectIfNotLoggedIn();
    if ($_SESSION['role'] != 'Admin') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Teacher only
function teacherOnly() {
    redirectIfNotLoggedIn();
    if ($_SESSION['role'] != 'Teacher') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Student only
function studentOnly() {
    redirectIfNotLoggedIn();
    if ($_SESSION['role'] != 'Student') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Parent only
function parentOnly() {
    redirectIfNotLoggedIn();
    if ($_SESSION['role'] != 'Parent') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Logout
function logout() {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}
?>
