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


if($_SERVER['REQUEST_METHOD'] === 'POST'){
   
    $data = json_decode(file_get_contents("php://input"), true);
     
    if (isset($data['action']) && $data['action'] === 'getTeacherDashboardData') {
    $TeacherID = $data['TeacherID'] ?? '';
    $SchoolYearID = $data['SchoolYearID'] ?? '';

    $sql = "SELECT 
    e.TeacherID,
    CONCAT(t.Fname, ' ', t.Lname) AS TeacherName,
   	r.catID,
    c.categoryName,
    e.SchoolYearID,
    ROUND(AVG(r.Score),1) AS AvgScore,
    GROUP_CONCAT(f.Comment SEPARATOR ' || ') AS Feedbacks
    FROM evaluation e
    Inner JOIN result r 
        ON e.EvalID = r.EvalID
    Inner Join category c
        ON c.catID = r.catID
    LEFT JOIN feedback f 
        ON e.EvalID = f.EvalID
    INNER JOIN teacher t 
        ON e.TeacherID = t.TeacherID
    WHERE e.TeacherID = ?    
    AND e.SchoolYearID = ?   
    GROUP BY 
        e.TeacherID, TeacherName, r.catID, c.categoryName,
        e.SchoolYearID
    ORDER BY  AvgScore DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si",$TeacherID, $SchoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    $stmt->close();
}

if (isset($data['action']) && $data['action'] === 'getTeacherStudentCount') {
    $teacherID = $data['TeacherID'] ?? '';
    $schoolYearID = $data['SchoolYearID'] ?? '';

    $sql = "SELECT 
                tsm.TeacherID,
                CONCAT(t.Fname, ' ', t.Lname) AS TeacherName,
                COUNT(e.StudID) AS StudentCount
            FROM teacher_subjectmap tsm
            INNER JOIN teacher t ON t.TeacherID = tsm.TeacherID
            INNER JOIN subject s ON s.SubjectID = tsm.SubjectID
            INNER JOIN year_section ys 
                ON ys.YearLevel = tsm.YearLevel 
               AND ys.SectionName = tsm.SectionName
            INNER JOIN enrollment e 
                ON e.YearSecID = ys.YearSecID
               AND e.SchoolYearID = tsm.SchoolYearID
            WHERE tsm.TeacherID = ? AND tsm.SchoolYearID = ?
            GROUP BY tsm.TeacherID";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $teacherID, $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $rows
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'getTeacherResponseCount') {
     file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
    $teacherID  = $data['TeacherID'] ?? '';
    $schoolYearID = $data['SchoolYearID'] ?? '';

    if (!$teacherID || !$schoolYearID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT 
            e.TeacherID,
            COUNT( e.StudID) AS ResponseCount
        FROM evaluation e
        WHERE e.TeacherID = ?
          AND e.SchoolYearID = ?
        GROUP BY e.TeacherID
    ");
    $stmt->bind_param("si", $teacherID, $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $row = $result->fetch_assoc();
    
      if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'success', 'data' => ['TeacherID' => $teacherID, 'ResponseCount' => 0]]);
    }
    exit;
    $stmt->close();
    exit();
}

if (isset($data['action']) && $data['action'] === 'getTeacherFeedback') {
    $teacherID  = $data['TeacherID'] ?? '';
    $schoolYearID = $data['SchoolYearID'] ?? '';

    if (!$teacherID || !$schoolYearID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            e.TeacherID,
            CONCAT(t.Fname, ' ', t.Lname) AS TeacherName,
            GROUP_CONCAT(DISTINCT f.Comment SEPARATOR ' || ') AS AllFeedbacks
        FROM evaluation e
        LEFT JOIN feedback f ON e.EvalID = f.EvalID
        INNER JOIN teacher t ON e.TeacherID = t.TeacherID
        WHERE e.TeacherID = ? AND e.SchoolYearID = ?
        GROUP BY e.TeacherID
    ");
    $stmt->bind_param("si", $teacherID, $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'feedback' => $rows
    ]);
    exit;
}


}

?>