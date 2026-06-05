<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get role (always lowercase)
function getUserRole() {
    return strtolower($_SESSION['role'] ?? '');
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
    if (getUserRole() !== 'admin') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Teacher only
function teacherOnly() {
    redirectIfNotLoggedIn();
    if (getUserRole() !== 'teacher') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Student only
function studentOnly() {
    redirectIfNotLoggedIn();
    if (getUserRole() !== 'student') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Parent only
function parentOnly() {
    redirectIfNotLoggedIn();
    if (getUserRole() !== 'parent') {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Logout
function logout() {
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?logged_out=1");
    exit();
}
