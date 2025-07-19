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

include 'db.php'; // Include database connection
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (!$data || !isset($data['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit();
    }

    if ($data['action'] === 'register_teacher') {
        $fname = $data['fname'];
        $mname = $data['mname'];
        $lname = $data['lname'];
        $teacherid = $data['teacherid'];
        $email = $data['email'];
        $usertype = $data['usertype'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO user_account (email, password, Usertype, status) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $email, $password,$usertype);

        if ($stmt->execute()) {
            $accid = $conn->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO teacher (teacherid, accid, fname, mname, lname) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sisss", $teacherid, $accid, $fname, $mname, $lname);
            if ($stmt2->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Teacher registered successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to insert teacher: ' . $stmt2->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert user account: ' . $stmt->error]);
        }
        exit();
    }

    // REGISTER ADMIN
    else if ($data['action'] === 'register_admin') {
        $fname = $data['fname'];
        $mname = $data['mname'];
        $lname = $data['lname'];
        $adminid = $data['adminid'];
        $email = $data['email'];
        $usertype = $data['usertype'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO user_account (email, password, UserType, status ) VALUES (?, ?,?, 1)");
        $stmt->bind_param("sss", $email, $password);

        if ($stmt->execute()) {
            $accid = $conn->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO admin (adminid, accid, fname, mname, lname) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sisss", $adminid, $accid, $fname, $mname, $lname);
            if ($stmt2->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Admin registered successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to insert admin: ' . $stmt2->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert user account: ' . $stmt->error]);
        }
        exit();
    }

    // update 

// UPDATE STUDENT
if (isset($data['action']) && $data['action'] === 'updateStudent') {
    file_put_contents('log.txt', json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    $accid = intval($data['accid']);
    $studid = $data['studid'];
    $fname = $data['fname'];
    $mname = $data['mname'];
    $lname = $data['lname'];
    $grade = $data['grade'];
    $section = $data['section'];
    $phone = $data['phone'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Update user_account
    $stmt1 = $conn->prepare("UPDATE user_account SET email = ?, password = ? WHERE accid = ?");
    $stmt1->bind_param("ssi", $email, $password, $accid);

    // Fetch yearSecID
    $stmt = $conn->prepare("SELECT yearsecid FROM year_section WHERE YearLevel = ? AND SectionName = ?");
    $stmt->bind_param("ss", $grade, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
          echo json_encode([
        'status' => 'error',
        'message' => 'Grade and section not found',
        'debug' => [
            'grade' => $grade,
            'section' => $section
        ]
    ]);
        exit();
    }

    $yearsecid = $row['yearsecid'];

    // Update student
    $stmt2 = $conn->prepare("UPDATE student SET studid = ?, fname = ?, mname = ?, lname = ?, yearSecID = ? WHERE accid = ?");
    $stmt2->bind_param("ssssii", $studid, $fname, $mname, $lname, $yearsecid, $accid);

    // Execute both statements
   $stmt1_result = $stmt1->execute();
    $stmt2_result = $stmt2->execute();

if ($stmt1_result && $stmt2_result) {
    echo json_encode(['status' => 'success', 'message' => 'Student updated successfully']);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update student',
        'stmt1_error' => $stmt1->error,
        'stmt2_error' => $stmt2->error
    ]);
}


    $stmt1->close();
    $stmt2->close();
    exit();
}


// UPDATE TEACHER
if ($data['action'] === 'updateTeacher') {
    $accid      = intval($data['accid']);
    $teacherid  = $data['teacherid'];
    $fname      = $data['fname'];
    $mname      = $data['mname'];
    $lname      = $data['lname'];
    $email      = $data['email'];
    $password   = password_hash($data['password'], PASSWORD_DEFAULT);

    // Update user_account table
    $stmt1 = $conn->prepare("UPDATE user_account SET email = ?, password = ? WHERE accid = ?");
    $stmt1->bind_param("ssi", $email, $password, $accid);

    // Update teacher table
    $stmt2 = $conn->prepare("UPDATE teacher SET teacherid = ?, fname = ?, mname = ?, lname = ? WHERE accid = ?");
    $stmt2->bind_param("ssssi", $teacherid, $fname, $mname, $lname, $accid);

    if ($stmt1->execute() && $stmt2->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Teacher updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update teacher']);
    }

    $stmt1->close();
    $stmt2->close();
    exit();
}


// UPDATE ADMIN
if ($data['action'] === 'updateAdmin') {
    $accid = intval($data['accid']);
    $adminid = $data['adminid'];
    $fname = $data['fname'];
    $mname = $data['mname'];
    $lname = $data['lname'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Update user_account
    $stmt1 = $conn->prepare("UPDATE user_account SET email = ?, password = ? WHERE accid = ?");
    $stmt1->bind_param("ssi", $email, $password, $accid);

    // Update admin
    $stmt2 = $conn->prepare("UPDATE admin SET adminid = ?, fname = ?, mname = ?, lname = ? WHERE accid = ?");
    $stmt2->bind_param("ssssi", $adminid, $fname, $mname, $lname, $accid);

    if ($stmt1->execute() && $stmt2->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Admin updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update admin']);
    }
    $stmt1->close();
    $stmt2->close();
    exit();
}
}

if(isset($data['action']) && $data['action'] === 'getUserAccount') {
       
        $stmt = $conn->prepare("SELECT s.studid AS ID, s.fname AS Fname,s.mname AS Mname,s.lname AS Lname, s.yearsecid as YearSec, s.image as image, ua.accid, ua.email, CASE ua.status 
        WHEN 1 THEN 'Active' ELSE 'Inactive' END AS status,'Student' AS role
        FROM student s
        INNER JOIN user_account ua ON s.accid = ua.accid
        UNION ALL
        SELECT t.teacherid AS ID, t.fname AS Fname, t.mname AS Mname, t.lname AS Lname, null as YearSec, t.image as image, ua.accid, ua.email,CASE ua.status 
        WHEN 1 THEN 'Active' ELSE 'Inactive' END AS status, 'Teacher' AS role
        FROM teacher t
        INNER JOIN user_account ua ON t.accid = ua.accid;
        ");
                
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];
        
        while($row = $result->fetch_assoc()){
            $results[] = $row;
        }

         echo json_encode([
        'status' => 'success',
        'account' => $results
    ]);
    exit();
}
   

if(isset($data['action']) && $data['action'] === 'getYearSection'){
    $yearsecid = intval($data['yearsecid']);

    $stmt = $conn->prepare("Select * from year_section where yearsecid = ?");
    $stmt->bind_param("i", $yearsecid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
            $results = $result->fetch_assoc();
            echo json_encode([
                'status' => 'success',
                'account' => $results
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No record found for the provided yearsecid'
            ]);
        }

        $stmt->close();
        exit();
}



?>
