<?php
require_once __DIR__ . '/includes/auth.php';
session_unset();
session_destroy();
session_start();
$_SESSION['flash_message'] = "You have been logged out.";
$_SESSION['flash_type'] = "success";
header("Location: login.php");
exit();
