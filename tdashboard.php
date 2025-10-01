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
     file_put_contents('log.txt', json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    if (isset($data['action']) && $data['action'] === 'getTeacherDashboardData') {
    $TeacherID = $data['TeacherID'] ?? '';
    $SchoolYear = $data['SchoolYear'] ?? '';

    $sql = "SELECT 
    e.TeacherID,
    CONCAT(t.Fname, ' ', t.Lname) AS TeacherName,
   	r.catID,
    c.categoryName,
    e.SchoolYear,
    AVG(r.Score) AS AvgScore,
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
    AND e.SchoolYear = ?   
    GROUP BY 
        e.TeacherID, TeacherName, r.catID, c.categoryName,
        e.SchoolYear
    ORDER BY  AvgScore DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$TeacherID, $SchoolYear);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
     file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
    echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    $stmt->close();
}
   
}

?>