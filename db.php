<?php
$servername = "localhost";  
$username = "u311854902_aquadrop";         
$password = "8=4u?LaKm062";             
$dbname = "u311854902_aquadrop";   

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>