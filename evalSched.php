<?php
session_start();
if (!isset($_SESSION['accID']) && isset($_COOKIE['rememberMe'])) {
    $_SESSION['accID'] = $_COOKIE['rememberMe'];
}

// Proper headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json"); // Important
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'createSchedule') {
        $schedule = $data['schedule'];

        $title = trim($schedule['title']);
        $startDate = trim($schedule['startDate']);
        $endDate = trim($schedule['endDate']);
        $status = trim($schedule['status']);
        $targetGrades = $schedule['targetGrades']; 
        $schoolYear = trim($schedule['schoolYear']);
        $adminID = (int)$schedule['adminID'];
        $QID = (int)$schedule['questionnaireID']; 
       

        $targetGradeStr = implode(', ', $targetGrades);

        // Validate required fields
        if ($title !== '' && $startDate !== '' && $endDate !== '' && !empty($targetGrades)) {
            $stmt = $conn->prepare("INSERT INTO Evaluation_Settings (Title, StartDate, EndDate, Status, TargetGrade, SchoolYear, AdminID, QID)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssii", $title, $startDate, $endDate, $status,$targetGradeStr, $schoolYear, $adminID, $QID );

            if ($stmt->execute()) {
        
                echo json_encode([  'status' => 'success','scheduleID' => $stmt->insert_id ]);
            } else { 
                   echo json_encode(['status' => 'error',  'message' => 'Database error: ' . $stmt->error ]);
            }
        } else { 
            echo json_encode([  'status' => 'invalid', 'message' => 'Missing required fields' ]);
        }
        exit();
    } 
}
?>
