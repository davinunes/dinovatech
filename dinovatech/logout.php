<?php
session_start();
session_destroy();
setcookie('dinovatech_remember', '', time() - 3600, '/');
header("Location: login.php");
exit();
?>