<?php 
require ('db.php');
require ('session.php');

$sql = "SELECT status FROM store_status WHERE ss_id = 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$status = $stmt->fetchColumn();

if($status){
    $sql = "UPDATE store_status SET status = 0 WHERE ss_id = 1";
}
else{
    $sql = "UPDATE store_status SET status = 1 WHERE ss_id = 1";
}
$stmt = $conn->prepare($sql);
$stmt->execute();
header('Location: index.php'); //sa reports today papunta
exit();
?>