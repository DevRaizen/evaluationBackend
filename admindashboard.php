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

if (isset($data['action']) && $data['action'] === 'count_students_by_grade') {
    
    $schoolYear = '2025-2026';  

    $sql = "
    SELECT yr.YearLevel as grade, COUNT(e.studid) as count from enrollment e
    Inner JOIN year_section yr on e.YearSecID = yr.YearSecID 
    WHERE yr.YearLevel in('7','8','9','10') and e.SchoolYear = ?
    GROUP by yr.YearLevel ORDER by yr.YearLevel;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $schoolYear);
    $stmt->execute();
    $result = $stmt->get_result();

    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['grade']] = (int)$row['count'];
    }

    // Ensure all grades appear even if 0 students
    foreach (['7','8','9','10'] as $g) {
        if (!isset($counts[$g])) {
            $counts[$g] = 0;
        }
    }

    echo json_encode([
        'status' => 'success',
        'counts' => $counts
    ]);
    exit();
}


?>
