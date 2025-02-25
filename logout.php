<?php
session_start();
session_destroy();
session_start();
$_SESSION['logout_message'] = "You have successfully logged out.";
header('Location: login.php');
exit;
?>