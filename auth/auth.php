<?php
require_once '../config/database.php';
require_once 'session.php';

function loginUser($email, $password) {
    global $conn;

    $email = mysqli_real_escape_string($conn, $email);

    $query = "SELECT u.*, r.role_name 
              FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.email = '$email'";

    $result = mysqli_query($conn, $query);
    $user   = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role_name'];
        return $user['role_name'];
    }
    return false;
}
?>
