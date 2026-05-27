<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM students WHERE id='$id'");
header("Location: student-list.php");
exit();
?>
