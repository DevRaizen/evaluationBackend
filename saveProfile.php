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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saveProfile') {

    $userid = $_POST['userid'] ?? '';
    $userRole = $_POST['userRole'] ?? '';

    if (!$userid || !$userRole) {
        http_response_code(200);
        echo json_encode(['status' => 'error', 'message' => 'Missing user ID or role']);
        exit;
    }

    if (isset($_FILES['userImage']) && $_FILES['userImage']['error'] === 0) {
        $originalName = basename($_FILES['userImage']['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = uniqid('img_', true) . '.' . $ext;

        $safeId = preg_replace('/[^a-zA-Z0-9-_]/', '_', $userid);

        // Determine directory and prepare SELECT query
        if ($userRole === 'Teacher') {
            $baseDir = 'TeacherProfile/' . $safeId . '/';
            $selectStmt = $pdo->prepare("SELECT image FROM teacher WHERE TeacherID = ?");
            $updateStmt = $pdo->prepare("UPDATE teacher SET image = ? WHERE TeacherID = ?");
        } else if ($userRole === 'Student') {
            $baseDir = 'StudentProfile/' . $safeId . '/';
            $selectStmt = $pdo->prepare("SELECT image FROM student WHERE StudID = ?");
            $updateStmt = $pdo->prepare("UPDATE student SET image = ? WHERE StudID = ?");
        } else {
            http_response_code(200);
            echo json_encode(['status' => 'error', 'message' => 'Invalid user role']);
            exit;
        }

        // Create user-specific directory
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $targetPath = $baseDir . $uniqueName;

        // Delete old image if exists
        $selectStmt->execute([$userid]);
        $existing = $selectStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing && isset($existing['image'])) {
            $oldImagePath = __DIR__ . '/' . $existing['image'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Move new image
        if (move_uploaded_file($_FILES['userImage']['tmp_name'], $targetPath)) {
            if ($updateStmt->execute([$targetPath, $userid])) {
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Profile image updated']);
            } else {
                http_response_code(200);
                echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
            }
        } else {
            http_response_code(200);
            echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
        }
    } else {
        http_response_code(200);
        echo json_encode(['status' => 'error', 'message' => 'No image uploaded']);
    }

    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   

    if (isset($data['action']) && $data['action'] === 'getProfile') {
        $userid = $data['userid'] ?? '';
        $userRole = $data['userRole'] ?? '';

        if (!$userid || !$userRole) {
            echo json_encode(['status' => 'error', 'message' => 'Missing data']);
            exit;
        }

        if ($userRole === 'Teacher') {
            $stmt = $pdo->prepare("SELECT image FROM teacher WHERE TeacherID = ?");
        } else if ($userRole === 'Student') {
            $stmt = $pdo->prepare("SELECT image FROM student WHERE StudID = ?");
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
            exit;
        }

        $stmt->execute([$userid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $image = isset($row['image']) && !empty($row['image']) ? $row['image'] : 'user.png';

    echo json_encode([
        'status' => 'success',
        'image' => $image  
    ]);
        exit;
    }
}

?>
