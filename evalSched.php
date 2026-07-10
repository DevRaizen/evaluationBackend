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
function getActiveSchoolYearID($conn) {
    $sql = "SELECT SchoolYearID 
            FROM SchoolYear 
            WHERE Status = 'Active' 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['SchoolYearID'];
    } else {
        // fallback if no active school year is found
        return 1;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

   if (isset($data['action']) && $data['action'] === 'createSchedule') {
    file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
    $schedule = $data['schedule'];

    $title = trim($schedule['title']);
    $startDate = trim($schedule['startDate']);
    $endDate = trim($schedule['endDate']);
    $status = trim($schedule['status']);
    $targetGrades = $schedule['targetGrades']; 
    $schoolYearID = getActiveSchoolYearID($conn);
    $adminID = $schedule['adminID'];
    $QID = (int)$schedule['questionnaireID']; 
    $adminName = $schedule['Admin'] ?? '';
    $accID = $schedule['AccID'] ?? '';
    $targetGradeStr = implode(', ', $targetGrades);

        $checkOverlap = $conn->prepare("
            SELECT COUNT(*) AS cnt 
            FROM Evaluation_Settings
            WHERE SchoolYearID = ?
            AND (
                    (StartDate <= ? AND EndDate >= ?)  -- New start inside existing
                OR (StartDate <= ? AND EndDate >= ?)  -- New end inside existing
                OR (? <= StartDate AND ? >= EndDate)  -- New covers existing schedule
            )
        ");
        $checkOverlap->bind_param(
            "issssss",
            $schoolYearID,
            $startDate, $startDate,
            $endDate, $endDate,
            $startDate, $endDate
        );

        $checkOverlap->execute();
        $overlapResult = $checkOverlap->get_result()->fetch_assoc();
        $checkOverlap->close();

        if ((int)$overlapResult['cnt'] > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'The schedule overlaps with an existing evaluation schedule.'
            ]);
            exit();
        }


    // Validate required fields
    if ($title !== '' && $startDate !== '' && $endDate !== '' && !empty($targetGrades)) {
        $stmt = $conn->prepare("INSERT INTO Evaluation_Settings (Title, StartDate, EndDate, Status, TargetGrade, SchoolYearID, AdminID, QID)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssisi", $title, $startDate, $endDate, $status, $targetGradeStr, $schoolYearID, $adminID, $QID);

        if ($stmt->execute()) {
            $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?,'Schedule an Evaluation', NOW())");
            $stmtLog->bind_param("si", $adminName,$accID);
            $stmtLog->execute();
            $stmtLog->close();
            echo json_encode([
                'status' => 'success',
                'scheduleID' => $stmt->insert_id
            ]);
        } else { 
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $stmt->error
            ]);
        }
    } else { 
        echo json_encode([
            'status' => 'invalid',
            'message' => 'Missing required fields'
        ]);
    }
    exit();
}


     if (isset($data['action']) && $data['action'] === 'getEvaluationSettings') {

        $stmt = $conn->prepare( "SELECT * FROM Evaluation_Settings es inner join schoolyear sy 
        on es.SchoolYearID = sy.SchoolYearID
         Where es.Status = 'Active' and sy.Status = 'Active' ORDER BY StartDate DESC");
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

        $stmt = $conn->prepare( "SELECT es.*,sy.SchoolYear FROM Evaluation_Settings es 
        inner join schoolyear sy on sy.SchoolYearID = es.SchoolYearID
         WHERE es.Status IN ('Active', 'Inactive') ORDER BY StartDate DESC");
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
        file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
        $schedule = $data['schedule'];
        $id = (int)$schedule['id'];
        $title = trim($schedule['title']);
        $startDate = trim($schedule['startDate']);
        $endDate = trim($schedule['endDate']);
        $adminName = $data['Admin'] ?? '';
        $accID = $data['AccID'] ?? '';
        if ($id && $title !== '' && $startDate !== '' && $endDate !== '') {

            $checkOverlap = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM Evaluation_Settings
    WHERE ESetID != ?
    AND (
            (? BETWEEN StartDate AND EndDate)
        OR  (? BETWEEN StartDate AND EndDate)
        OR  (? <= StartDate )
    )
");

$checkOverlap->bind_param(
    "isss",
    $id,
    $startDate,
    $endDate,
    $startDate
);

$checkOverlap->execute();
$overlapResult = $checkOverlap->get_result()->fetch_assoc();
$checkOverlap->close();

if ((int)$overlapResult['cnt'] > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Your new schedule falls inside an existing schedule.'
    ]);
    exit();
}

        $stmt = $conn->prepare("UPDATE Evaluation_Settings  SET Title = ?, StartDate = ?, EndDate = ?
                                 WHERE ESetID = ?");
        $stmt->bind_param("sssi", $title, $startDate, $endDate, $id);
        if ($stmt->execute()) {
            $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?, ?,'Updated Evaluation Schedule', NOW())");
            $stmtLog->bind_param("si", $adminName,$accID);
            $stmtLog->execute();
            $stmtLog->close();
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

    // Check if the Evaluation_Settings is used in evaluation table
    $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM evaluation WHERE ESetID = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($result['cnt'] > 0) {
        // Already used → just mark as Deleted
        $updateStmt = $conn->prepare("UPDATE Evaluation_Settings SET Status = 'Deleted' WHERE ESetID = ?");
        $updateStmt->bind_param("i", $id);
        if ($updateStmt->execute()) {
            echo json_encode([
                'status' => 'updated',
                'message' => 'Record already used — status set to Deleted instead.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $updateStmt->error
            ]);
        }
        $updateStmt->close();
    } else {
        // Not used → safe to delete
        $deleteStmt = $conn->prepare("DELETE FROM Evaluation_Settings WHERE ESetID = ?");
        $deleteStmt->bind_param("i", $id);
        if ($deleteStmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Deleted successfully.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $deleteStmt->error
            ]);
        }
        $deleteStmt->close();
    }

    exit();
}


}
?>
