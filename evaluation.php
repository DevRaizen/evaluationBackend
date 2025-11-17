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
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include 'db.php'; // Include database connection
function getCurrentSchoolYear(): string {
    $month = date('n');
    if ($month >= 6) {
        $start = date('Y');
        $end = $start + 1;
    } else {
        $end = date('Y');
        $start = $end - 1;
    }
    return $start . '-' . $end;
}


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $data = json_decode(file_get_contents("php://input"), true);
    file_put_contents('log.txt', json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    $action = $data['action'] ?? '';

    if ($action === 'submitEvaluation') {
    $studID     = $data['StudID'];
    $teacherID  = $data['TeacherID'];
    $subjectID  = $data['SubjectID'];
    $eSetID     = $data['ESetID'];
    $schoolYearID = $data['SchoolYearID'];
    $answers    = $data['answers'];          // now a nested array
    $optional   = $data['Optionalanswers'];  // if you need it
    $studName = $data['Student'] ?? '';
    $accID = $data['AccID'] ?? '';

    $conn->begin_transaction();

    try {
        // 1. Create evaluation record
        $stmt = $conn->prepare("
            INSERT INTO evaluation (ESetID, TeacherID, StudID, SubjectID, SchoolYearID, EvalDate)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssi", $eSetID, $teacherID, $studID, $subjectID, $schoolYearID);
        $stmt->execute();
        $evalID = $stmt->insert_id;
        $stmt->close();

        // 2. Loop categories → questions
        foreach ($answers as $catID => $questions) {
            foreach ($questions as $quesID => $answer) {

                if (is_numeric($answer)) {
                    // numeric score → insert into result
                    $stmt = $conn->prepare("
                        INSERT INTO result (EvalID, QuesID, catID, Score)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiii", $evalID, $quesID, $catID, $answer);
                    $stmt->execute();
                    $stmt->close();

                } else if (trim($answer) !== '') {
                    // text comment → insert into feedback
                    $stmt = $conn->prepare("
                        INSERT INTO feedback (EvalID, StudID, Comment, timestamp)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->bind_param("iss", $evalID, $studID, $answer);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 3. (Optional) handle $optional if needed
         $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?,?, 'Submitted Evaluation', NOW())");
        $stmtLog->bind_param("si", $studName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
        $conn->commit();
        echo json_encode(['status' => 'success', 'evalID' => $evalID]);

    } catch (Exception $e) {
        $conn->rollback();
    }
    exit();
}

}
?>