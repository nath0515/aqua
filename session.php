<?php 
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>