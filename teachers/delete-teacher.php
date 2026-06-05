<?php
require_once '../auth/session.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM teachers WHERE id='$id'");
header("Location: teacher-list.php");
exit();
?>
