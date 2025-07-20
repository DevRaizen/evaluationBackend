<?php
session_start(); 
if (!isset($_SESSION['accID']) && isset($_COOKIE['rememberMe'])) {
    $_SESSION['accID'] = $_COOKIE['rememberMe'];
}
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // Include database connection
$data = json_decode(file_get_contents("php://input"), true);

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(isset($data['action']) && $data['action'] === 'getYearSec'){
        $stmt = $conn->prepare("Select * from year_section");
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];

        while($row = $result->fetch_assoc()){
            $results[] = $row;
        }
        
        echo json_encode([
        'status' => 'success',
        'yearsec' => $results
        ]);
        exit();
    }

    if(isset($data['action']) && $data['action'] === 'getSubPerYear'){
        $grade = $data['grade'];
        $stmt = $conn->prepare("SELECT sp.YearLevel, sub.subjectname, sub.subjectid from subject_peryear sp
                                INNER join subject sub on sub.SubjectID =  sp.SubjectID
                                where sp.YearLevel = ?");
        $stmt->bind_param("s",$grade);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];

        while($row = $result->fetch_assoc()){
            $results[] = $row;
        }
        
        echo json_encode([
        'status' => 'success',
        'subject' => $results
        ]);
        exit();
    }
}

?>