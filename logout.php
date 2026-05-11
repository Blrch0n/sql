<?php
require_once "config/security.php";
session_destroy();
header("Location: login.php");
exit;
?>