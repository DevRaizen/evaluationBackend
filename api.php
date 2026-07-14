<?php
session_start(); 
if (!isset($_SESSION['accID']) && isset($_COOKIE['rememberMe'])) {
    $_SESSION['accID'] = $_COOKIE['rememberMe'];
}
header("Content-Type: application/json; charset=UTF-8");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


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

    // 1️⃣ Get YearSecID
    $stmt = $conn->prepare("SELECT YearSecID FROM Year_Section WHERE YearLevel = ? AND SectionName = ?");
    $stmt->bind_param("ss", $grade, $section);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $yearSecID = $row['YearSecID'];
    } else {
        $insertYearSec = $conn->prepare("INSERT INTO Year_Section (YearLevel, SectionName) VALUES (?, ?)");
        $insertYearSec->bind_param("ss", $grade, $section);
        $insertYearSec->execute();
        $yearSecID = $conn->insert_id;
    }

    // 2️⃣ Insert into User_Account
    $stmt = $conn->prepare("INSERT INTO User_Account (Email, Password, UserType, Status) VALUES (?, ?, 'Student', 2)");
    $stmt->bind_param("ss", $email, $hashedPassword);
    $stmt->execute();
    $accID = $conn->insert_id;

    // 3️⃣ Insert into Student table
    $stmt2 = $conn->prepare("INSERT INTO Student (StudID, AccID, Fname, Mname, Lname) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("sisss", $StudId, $accID, $fname, $mname, $lname);
    $success = $stmt2->execute();

    if ($success) {
        // 4️⃣ Get active SchoolYearID from schoolyear table
        $schoolYearStmt = $conn->prepare("SELECT SchoolYearID FROM schoolyear WHERE Status = 'Active' LIMIT 1");
        $schoolYearStmt->execute();
        $schoolYearResult = $schoolYearStmt->get_result();
        $schoolYearRow = $schoolYearResult->fetch_assoc();
        $schoolYearID = $schoolYearRow['SchoolYearID'] ?? 1; // fallback to 1 if not found

        // 5️⃣ Insert into Enrollment table with SchoolYearID
        $enrollStmt = $conn->prepare("INSERT INTO Enrollment (StudID, YearSecID, SchoolYearID) VALUES (?, ?, ?)");
        $enrollStmt->bind_param("sii", $StudId, $yearSecID, $schoolYearID);
        $success2 = $enrollStmt->execute();

        if ($success2) {
            echo json_encode(["status" => "success", "message" => "Student registered and enrolled successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Student registered, but enrollment failed."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert into Student table."]);
    }

    exit(); 
}


    // Login User
    if (isset($data['action']) && $data['action'] == 'login') {
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
    $stmt = $conn->prepare("SELECT * FROM user_account WHERE Email = ? And  (status = 1 or status = 2 or status = 3)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
          if ($user['status'] == 2) {
        echo json_encode([
            "status" => "blocked",
            "message" => "Your account is pending approval. Please wait for Admin approval."
        ]);
        exit();
    }
    if ($user['status'] == 3) {
        echo json_encode([
            "status" => "blocked",
            "message" => "Your Account  Rejected."
        ]);
        exit();
    }
      
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
                $stmt2 = $conn->prepare("
                                       SELECT 
                                        s.StudID, s.Fname, s.Mname, s.Lname, s.AccID,
                                        ua.Email, ua.Password,
                                        ys.YearLevel AS Grade, ys.SectionName AS Section,
                                        sy.SchoolYearID, sy.SchoolYear
                                    FROM Student s
                                    INNER JOIN User_Account ua ON s.AccID = ua.AccID
                                    INNER JOIN Enrollment e ON s.StudID = e.StudID
                                    INNER JOIN Year_Section ys ON e.YearSecID = ys.YearSecID
                                    INNER JOIN SchoolYear sy ON e.SchoolYearID = sy.SchoolYearID
                                    WHERE s.AccID = ?
                                    ORDER BY sy.SchoolYearID DESC
                                    LIMIT 1
                                    ");
                $stmt2->bind_param("i", $accID);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $userData = $res2->fetch_assoc();
                $userData['UserType'] = 'Student';
                 if ($userData) {
                            // Insert log
                            $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Login', NOW())");
                            $fullName = $userData['Fname'] . ' ' . $userData['Mname'] . ' ' . $userData['Lname'];
                            $stmtLog->bind_param("si", $fullName,$accID);
                            $stmtLog->execute();
                            $stmtLog->close();

                            // Continue with your login logic
                        }
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

                        if ($userData) {
                            // Insert log
                            $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Login', NOW())");
                            $fullName = $userData['Fname'] . ' ' . $userData['Mname'] . ' ' . $userData['Lname'];
                            $stmtLog->bind_param("si", $fullName,$accID);
                            $stmtLog->execute();
                            $stmtLog->close();

                            // Continue with your login logic
                        }
            } 
            elseif ($usertype === 'Teacher') {
               $stmt2 = $conn->prepare("SELECT t.TeacherID, t.Fname, t.Mname, t.Lname, t.AccID, ua.Email, ua.Password
                         FROM Teacher t
                         JOIN User_Account ua ON t.AccID = ua.AccID
                         WHERE t.AccID = ?");
                $stmt2->bind_param("i", $accID);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $userData = $res2->fetch_assoc();
                $userData['UserType'] = 'Teacher';
                 if ($userData) {
                            // Insert log
                           $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Login', NOW())");
                            $fullName = $userData['Fname'] . ' ' . $userData['Mname'] . ' ' . $userData['Lname'];
                            $stmtLog->bind_param("si", $fullName,$accID);
                            $stmtLog->execute();
                            $stmtLog->close();

                            // Continue with your login logic
                        }

            } 
            elseif ($usertype === 'Principal') {
               $stmt2 = $conn->prepare("SELECT p.PrincipalID, p.Fname, p.Mname, p.Lname, p.AccID, ua.Email, ua.Password
                         FROM Principal p
                         JOIN User_Account ua ON p.AccID = ua.AccID
                         WHERE p.AccID = ?");
                    $stmt2->bind_param("i", $accID);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    $userData = $res2->fetch_assoc();
                    $userData['UserType'] = 'Principal';
                     if ($userData) {
                            // Insert log
                            $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Login', NOW())");
                            $fullName = $userData['Fname'] . ' ' . $userData['Mname'] . ' ' . $userData['Lname'];
                            $stmtLog->bind_param("si", $fullName,$accID);
                            $stmtLog->execute();
                            $stmtLog->close();

                            // Continue with your login logic
                        }

            }

            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "sessionId" => $sessionid,
                "accountInfo" => $userData
            ]);
            exit(); 
        } 
        else {
            echo json_encode(["status" => "error", "message" => "Incorrect password"]);
            exit(); 
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Email not found"]);
        exit(); 
    }

    $stmt->close();
}

// Get all pending student accounts (status = 2)
if (isset($data['action']) && $data['action'] == 'get_pending_students') {
    $schoolYearID =  getActiveSchoolYearID($conn);

    $stmt = $conn->prepare("
        SELECT 
            s.StudID,
            CONCAT(s.Fname, ' ', s.Mname, ' ', s.Lname) AS FullName,
            ua.Email,
            ys.YearLevel AS Grade,
            ys.SectionName AS Section,
            sy.SchoolYear
        FROM User_Account ua
        INNER JOIN Student s ON ua.AccID = s.AccID
        LEFT JOIN Enrollment e ON s.StudID = e.StudID
        LEFT JOIN Year_Section ys ON e.YearSecID = ys.YearSecID
        inner join SchoolYear sy on e.SchoolYearID = sy.SchoolYearID
        WHERE ua.Status = 2 and e.SchoolYearID = ?
    ");
    $stmt->bind_param("i", $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $pendingStudents = [];
    while ($row = $result->fetch_assoc()) {
        $pendingStudents[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $pendingStudents
    ]);
    exit();
}


if (isset($data['action']) && $data['action'] == 'approve_student') {
    $studID = $data['StudID'] ?? '';
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';

    $stmt = $conn->prepare("UPDATE user_account ua 
                            JOIN student s ON ua.AccID = s.AccID 
                            SET ua.Status = 1 
                            WHERE s.StudID = ?");
    $stmt->bind_param("s", $studID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Insert log
        $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?, ?, 'Approved Student', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
        echo json_encode(['status' => 'success', 'message' => 'Student approved successfully']);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No matching student found']);
    }
    $stmt->close();
    exit();
}


elseif (isset($data['action']) && $data['action'] == 'reject_student') {
    $studID = $data['StudID'] ?? '';
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';
    $schoolYearID =  getActiveSchoolYearID($conn);
    // Step 1: Get AccID before deleting
    $getAcc = $conn->prepare("SELECT AccID FROM student WHERE StudID = ?");
    $getAcc->bind_param("s", $studID);
    $getAcc->execute();
    $res = $getAcc->get_result();
    $accID = null;

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $accID = $row['AccID'];
    }
    $getAcc->close();

    if ($accID) {
        // Step 2: Delete from enrollment first
        $delEnroll = $conn->prepare("DELETE FROM enrollment WHERE StudID = ? and SchoolyearID = ?");
        $delEnroll->bind_param("si", $studID,$schoolYearID);
        $delEnroll->execute();
        $delEnroll->close();

        // Step 3: Delete from student table
        $delStud = $conn->prepare("DELETE FROM student WHERE StudID = ?");
        $delStud->bind_param("s", $studID);
        $delStud->execute();
        $delStud->close();

        // Step 4: Delete from user_account table
        $delAcc = $conn->prepare("DELETE FROM user_account WHERE AccID = ?");
        $delAcc->bind_param("i", $accID);
        $delAcc->execute();
        if ($delAcc->affected_rows > 0) {
        // Successfully deleted
        $response = ['status' => 'success', 'message' => 'User account deleted successfully'];
        } 
        else {
        // ❌ If delete failed, update status to 0 instead
        $updateAcc = $conn->prepare("UPDATE user_account SET Status = 0 WHERE AccID = ?");
        $updateAcc->bind_param("i", $accID);
        $updateAcc->execute();
        }
        $delAcc->close();
        
         $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?, ?, 'Rejected Student', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();

        echo json_encode(['status' => 'success', 'message' => 'Student and related records deleted successfully']);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Student not found']);
    }
    exit();
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
