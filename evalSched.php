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
            $stmt->bind_param("ssssssii", $title, $startDate, $endDate, $status,$targetGradeStr, $schoolYear, $adminID, $QID);

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

     if (isset($data['action']) && $data['action'] === 'getEvaluationSettings') {

        $stmt = $conn->prepare( "SELECT * FROM Evaluation_Settings Where Status = 'Active' ORDER BY StartDate DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];

         while($row = $result->fetch_assoc()){
            $results[] = $row;
        }

         echo json_encode([
        'status' => 'success',
        'evalsettings' => $results
    ]);
    exit();   
    }

    if (isset($data['action']) && $data['action'] === 'getEvaluationAllSettings') {

        $stmt = $conn->prepare( "SELECT * FROM Evaluation_Settings  ORDER BY StartDate DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];

         while($row = $result->fetch_assoc()){
            $results[] = $row;
        }

         echo json_encode([
        'status' => 'success',
        'evalsettings' => $results
    ]);
    exit();   
    }

    if (isset($data['action']) && $data['action'] === 'updateEvaluationSettings') {
        $schedule = $data['schedule'];
        $id = (int)$schedule['id'];
        $title = trim($schedule['title']);
        $startDate = trim($schedule['startDate']);
        $endDate = trim($schedule['endDate']);

        if ($id && $title !== '' && $startDate !== '' && $endDate !== '') {
        $stmt = $conn->prepare("UPDATE Evaluation_Settings  SET Title = ?, StartDate = ?, EndDate = ?
                                 WHERE ESetID = ?");
        $stmt->bind_param("sssi", $title, $startDate, $endDate, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'invalid', 'message' => 'Missing fields']);
    }
    exit();
    }

    if (isset($data['action']) && $data['action'] === 'deleteEvaluationSetting') {
    $id = intval($data['id']);

    $stmt = $conn->prepare("DELETE FROM Evaluation_Settings WHERE ESetID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    exit();
    }

}
?>
