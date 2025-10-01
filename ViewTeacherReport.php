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

include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['action']) && $data['action'] === 'getAllTeachers') {
    try {
        $stmt = $pdo->query("SELECT TeacherID, Fname, Mname, Lname, image 
                             FROM teacher");
        $teachers = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $teachers[] = [
                'TeacherID'   => $row['TeacherID'],
                'Fname'       => $row['Fname'],
                'Mname'       => $row['Mname'],
                'Lname'       => $row['Lname'],
                'image'       => !empty($row['image']) ? $row['image'] : 'user.png'
            ];
        }

        echo json_encode([
            'status' => 'success',
            'teachers' => $teachers
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

?>
