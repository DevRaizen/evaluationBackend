<?php
session_start(); 
if (!isset($_SESSION['accID']) && isset($_COOKIE['rememberMe'])) {
    $_SESSION['accID'] = $_COOKIE['rememberMe'];
}
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php'; // Include database connection
$data = json_decode(file_get_contents("php://input"), true);


if(isset($data['action']) && $data['action'] === 'count_students') {
       
        $stmt = $conn->prepare("SELECT count(*) as count FROM Student ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc(); 

         echo json_encode([
        'status' => 'success',
        'count' => $row['count']
    ]);
    exit();
}
   
if(isset($data['action']) && $data['action'] === 'count_teachers'){
    $stmt = $conn->prepare("SELECT count(*) as count from Teacher");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc(); 
    echo json_encode([
        'status' => 'success',
        'count' => $row['count']
    ]);
}

?>
