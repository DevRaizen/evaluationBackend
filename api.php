<?php
session_start(); 
if (!isset($_SESSION['accID']) && isset($_COOKIE['rememberMe'])) {
    $_SESSION['accID'] = $_COOKIE['rememberMe'];
}
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $fname      = isset($data['fname']) ? $data['fname'] : '';
    $mname      = isset($data['mname']) ? $data['mname'] : '';
    $lname      = isset($data['lname']) ? $data['lname'] : '';
    $StudId   = isset($data['studid']) ? $data['studid'] : '';
    $grade      = isset($data['grade']) ? $data['grade'] : '';
    $section    = isset($data['section']) ? $data['section'] : '';
    $phone      = isset($data['phone_number']) ? $data['phone_number'] : '';
    $email      = isset($data['email']) ? $data['email'] : '';
    $password = isset($data['password']) ? $data['password'] : '';
    if (isset($data['action']) && $data['action'] == 'check_email') {
    $email = $data['email'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM User_Account WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["exists" => true]);
    } else {
        echo json_encode(["exists" => false]);
    }
    exit();
}

    // Check if StudID exists
    if (isset($data['action']) && $data['action'] == 'check_studid') {
        $studid = $data['studid'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM Student WHERE StudID = ?");
        $stmt->bind_param("s", $studid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["exists" => true]);
        } else {
            echo json_encode(["exists" => false]);
        }
        exit();
    }
    // Register
    if (isset($data['action']) && $data['action'] == 'register') {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("SELECT YearSecID FROM Year_Section WHERE YearLevel = ? AND SectionName = ?");
        $stmt->bind_param("ss", $grade, $section);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $yearSecID = $row['YearSecID'];
        }else {
            $insertYearSec = $conn->prepare("INSERT INTO Year_Section (YearLevel, SectionName) VALUES (?, ?)");
            $insertYearSec->bind_param("ss", $grade, $section);
            $insertYearSec->execute();
            $yearSecID = $conn->insert_id;
        }

        $stmt = $conn->prepare("INSERT INTO User_Account (Email, Password, UserType) VALUES (?, ?, 'student')");
        $stmt->bind_param("ss", $email, $hashedPassword);
        $stmt->execute();
        $accID = $conn->insert_id;
        
        $stmt2 = $conn->prepare("INSERT INTO Student (StudID, AccID, YearSecID, Fname, Mname, Lname) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("siisss", $StudId, $accID, $yearSecID, $fname, $mname, $lname);
        $success = $stmt2->execute();

        if ($success) {
        echo json_encode(["status" => "success", "message" => "Student registered successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to insert into Student table."]);
        }
    }
    // Login User
    elseif (isset($data['action']) && $data['action'] == 'login') {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $rememberMeChecked = $data['rememberme'] ?? '';
    if (isset($_SESSION['user'])) {
        echo json_encode([
            "message" => "User already logged in",
            "user" => $_SESSION['user'],
            "sessionId" => session_id()
        ]);
        exit();
    }
  

    // Step 1: Check user from User_Account
    $stmt = $conn->prepare("SELECT * FROM User_Account WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['Password'])) {
            $sessionid = session_id();
            $_SESSION['accID'] = $user['AccID'];
            if ($rememberMeChecked) { // only if user ticks "remember me"
            setcookie('rememberMe', $user['AccID'], time() + (86400 * 30), "/"); // 30 days
}

            $accID = $user['AccID'];
            $usertype = $user['UserType'];
            $userData = [];

            // Step 2: Get user info from correct table
            if ($usertype === 'Student') {
                $stmt2 = $conn->prepare("SELECT s.StudID, s.Fname, s.Mname, s.Lname, s.AccID, ua.Email, ua.Password, ys.YearLevel AS Grade, ys.SectionName AS Section
                         FROM Student s
                         Inner JOIN User_Account ua ON s.AccID = ua.AccID
                         Inner JOIN Year_Section ys ON s.YearSecID = ys.YearSecID
                         WHERE s.AccID = ?");
                $stmt2->bind_param("i", $accID);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $userData = $res2->fetch_assoc();
                $userData['UserType'] = 'Student';
            } 
            elseif ($usertype === 'Admin') {
                $stmt2 = $conn->prepare("SELECT a.AdminID, a.Fname, a.Mname, a.Lname, a.AccID, ua.Email, ua.Password
                         FROM Admin a
                         JOIN User_Account ua ON a.AccID = ua.AccID
                         WHERE a.AccID = ?");
                        $stmt2->bind_param("i", $accID);
                        $stmt2->execute();
                        $res2 = $stmt2->get_result();
                        $userData = $res2->fetch_assoc();
                        $userData['UserType'] = 'Admin';
            } 
            elseif ($usertype === 'teacher') {
               $stmt2 = $conn->prepare("SELECT t.TeacherID, t.Fname, t.Mname, t.Lname, t.AccID, ua.Email, ua.Password
                         FROM Teacher t
                         JOIN User_Account ua ON t.AccID = ua.AccID
                         WHERE t.AccID = ?");
                $stmt2->bind_param("i", $accID);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $userData = $res2->fetch_assoc();
                $userData['UserType'] = 'Teacher';

            } 
            elseif ($usertype === 'principal') {
               $stmt2 = $conn->prepare("SELECT p.TeacherID, p.Fname, p.Mname, p.Lname, p.AccID, ua.Email, ua.Password
                         FROM Principal p
                         JOIN User_Account ua ON p.AccID = ua.AccID
                         WHERE p.AccID = ?");
                    $stmt2->bind_param("i", $accID);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    $userData = $res2->fetch_assoc();
                    $userData['UserType'] = 'principal';

            }

            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "sessionId" => $sessionid,
                "accountInfo" => $userData
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Email not found"]);
    }

    $stmt->close();
}


    elseif (isset($data['action']) && $data['action'] == 'logout') {
        session_unset();     // Clear all session variables
        session_destroy();   // Destroy the session

        if (isset($_COOKIE['rememberMe'])) {
                setcookie('rememberMe', '', time() - 3600, "/"); // Set cookie to expire in the past
        }
        
        echo json_encode([
        'status' => 'success',
        'message' => 'User successfully logged out'
        ]);
    }

    else {
        echo json_encode(["message" => "Invalid action"]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
?>
