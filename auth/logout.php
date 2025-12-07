<?php
session_start();
session_unset();
session_destroy();
header('Location: /diabetes_monitoring/auth/login.php');
exit();
?>
