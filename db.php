<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

$servername = "localhost";  
$username = "u311854902_aquadrop";         
$password = "8=4u?LaKm062";             
$dbname = "u311854902_aquadrop";   

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set MySQL timezone to match PHP timezone
    $conn->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>