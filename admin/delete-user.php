<?php
require_once '../auth/session.php';
require_once '../config/database.php';
adminOnly();

$id = $_GET['id'];

// Prevent deleting yourself
if ($id == $_SESSION['user_id']) {
    header("Location: manage-users.php?error=cannot_delete_yourself");
    exit();
}

mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
header("Location: manage-users.php?success=deleted");
exit();
?>
