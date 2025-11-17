<?php
session_start(); 
if (!isset($_SESSION['accID']) && isset($_COOKIE['rememberMe'])) {
    $_SESSION['accID'] = $_COOKIE['rememberMe'];
}
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 0);
error_reporting(0);


include 'db.php';
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

 // Include database connection
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
        $adminName = $data['Admin'] ?? '';
        $accID = $data['AccID'] ?? '';
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO user_account (email, password, Usertype, status) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $email, $password,$usertype);

        if ($stmt->execute()) {
            $accid = $conn->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO teacher (teacherid, accid, fname, mname, lname) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sisss", $teacherid, $accid, $fname, $mname, $lname);
            if ($stmt2->execute()) {
                $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?, ?, 'Create Teacher Account', NOW())");
                $stmtLog->bind_param("si", $adminName,$accID);
                $stmtLog->execute();
                $stmtLog->close();

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
        $adminName = $data['Admin'] ?? '';
        $accID = $data['AccID'] ?? '';
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO user_account (email, password, UserType, status ) VALUES (?, ?,?, 1)");
        $stmt->bind_param("sss", $email, $password,$usertype);

        if ($stmt->execute()) {
            $accid = $conn->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO admin (adminid, accid, fname, mname, lname) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sisss", $adminid, $accid, $fname, $mname, $lname);
            if ($stmt2->execute()) {
                $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?, ?, 'Create Admin Account', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();

                echo json_encode(['status' => 'success', 'message' => 'Admin registered successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to insert admin: ' . $stmt2->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert user account: ' . $stmt->error]);
        }
        exit();
    }

    // reg principal
     else if ($data['action'] === 'register_principal') {
        file_put_contents('log.txt', json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        $fname = $data['fname'];
        $mname = $data['mname'];
        $lname = $data['lname'];
        $principalid = $data['principalid'];
        $email = $data['email'];
        $usertype = $data['usertype'];
        $adminName = $data['Admin'] ?? '';
        $accID = $data['AccID'] ?? '';
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO user_account (email, password, UserType, status ) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $email, $password, $usertype);

        if ($stmt->execute()) {
            $accid = $conn->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO principal (principalid, accid, fname, mname, lname) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sisss", $principalid, $accid, $fname, $mname, $lname);
            if ($stmt2->execute()) {
                $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?, ?, 'Create Principal Account', NOW())");
                $stmtLog->bind_param("si", $adminName,$accID);
                $stmtLog->execute();
                $stmtLog->close();

                echo json_encode(['status' => 'success', 'message' => 'Principal registered successfully']);
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
    $status = $data['status'];
    $phone = $data['phone'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';

    // Update user_account
    $stmt1 = $conn->prepare("UPDATE user_account SET email = ?, password = ?, Status = ? WHERE accid = ?");
    $stmt1->bind_param("sssi", $email, $password, $status, $accid);

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
    }else{
         file_put_contents('log.txt', " walang Nakuhang error  "  . "\n", FILE_APPEND);
    }

    $yearsecid = $row['yearsecid'];

    // Update student
    $stmt2 = $conn->prepare("UPDATE student SET studid = ?, fname = ?, mname = ?, lname = ? WHERE accid = ?");
    $stmt2->bind_param("ssssi", $studid, $fname, $mname, $lname, $accid);

    // Execute both statements
     $stmt1_result = $stmt1->execute();
    $stmt2_result = $stmt2->execute();

    $schoolYearID = getActiveSchoolYearID($conn);
  ;
file_put_contents('log.txt', "Active SchoolYearID: " . var_export($schoolYearID, true) . "\n", FILE_APPEND);


    $checkEnroll = $conn->prepare("SELECT EnrollmentID FROM Enrollment WHERE StudID = ? AND SchoolYearID = ?");
    $checkEnroll->bind_param("si", $studid, $schoolYearID);
    $checkEnroll->execute();
    $checkEnrollResult = $checkEnroll->get_result();
    file_put_contents('log.txt', "Checking enrollment for StudID={$studid}, SchoolYearID={$schoolYearID}\n", FILE_APPEND);

    if ($checkEnrollResult->num_rows > 0) {
        // Update enrollment
        $enrollmentRow = $checkEnrollResult->fetch_assoc();
        file_put_contents('log.txt', "Active SchoolYearID: " . var_export($enrollmentRow, true) . "\n", FILE_APPEND);
        $enrollmentID = $enrollmentRow['EnrollmentID'];

        $updateEnroll = $conn->prepare("UPDATE Enrollment SET yearsecid = ? WHERE EnrollmentID = ?");
        $updateEnroll->bind_param("ii", $yearsecid, $enrollmentID);
        $enrollmentResult = $updateEnroll->execute();
        if (!$enrollmentResult) {
    file_put_contents('log.txt', "Update failed: " . $updateEnroll->error . "\n", FILE_APPEND);
} else {
    file_put_contents('log.txt', "Update success for EnrollmentID={$enrollmentID}\n", FILE_APPEND);
}

        $updateEnroll->close();
        file_put_contents('log.txt', "Existing enrollment found: " . json_encode($enrollmentRow) . "\n", FILE_APPEND);

    } else {
        // Insert enrollment
        $insertEnroll = $conn->prepare("INSERT INTO Enrollment (StudID, YearSecID, SchoolYearID) VALUES (?, ?, ?)");
        $insertEnroll->bind_param("sii", $studid, $yearsecid, $schoolYearID);
        $enrollmentResult = $insertEnroll->execute();
        $insertEnroll->close();
        file_put_contents('log.txt', "No existing enrollment found — inserting new record.\n", FILE_APPEND);

    }



if ($stmt1_result && $stmt2_result && $enrollmentResult) {
     $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Updated a Student', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
    file_put_contents('log.txt', "galing gumana.\n", FILE_APPEND);
    echo json_encode(['status' => 'success', 'message' => 'Student updated successfully']);
    exit();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update student or enrollment',
        'stmt1_error' => $stmt1->error,
        'stmt2_error' => $stmt2->error,
        'enroll_error' => $conn->error
    ]);
     exit();
}


    $stmt1->close();
    $stmt2->close();
    $stmt->close();
    $checkEnroll->close();
    exit();
}


// UPDATE TEACHER
if ($data['action'] === 'updateTeacher') {
    $accid      = intval($data['accid']);
    $teacherid  = $data['teacherid'];
    $fname      = $data['fname'];
    $mname      = $data['mname'];
    $lname      = $data['lname'];
    $status = $data['status'];
    $email      = $data['email'];
    $password   = password_hash($data['password'], PASSWORD_DEFAULT);
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';
    // Update user_account table
    $stmt1 = $conn->prepare("UPDATE user_account SET email = ?, password = ?, Status = ? WHERE accid = ?");
    $stmt1->bind_param("ssis", $email, $password,$status, $accid);

    // Update teacher table
    $stmt2 = $conn->prepare("UPDATE teacher SET teacherid = ?, fname = ?, mname = ?, lname = ? WHERE accid = ?");
    $stmt2->bind_param("ssssi", $teacherid, $fname, $mname, $lname, $accid);

    if ($stmt1->execute() && $stmt2->execute()) {
          $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Updated a Teacher', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
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
    $adminid = intval($data['adminid']);
    $fname = $data['fname'];
    $mname = $data['mname'];
    $lname = $data['lname'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $accID = $data['AccID'] ?? '';
    // Update user_account
    $stmt1 = $conn->prepare("UPDATE user_account SET email = ?, password = ? WHERE accid = ?");
    $stmt1->bind_param("ssi", $email, $password, $accid);

    // Update admin
    $stmt2 = $conn->prepare("UPDATE admin SET adminid = ?, fname = ?, mname = ?, lname = ? WHERE accid = ?");
    $stmt2->bind_param("isssi", $adminid, $fname, $mname, $lname, $accid);

    if ($stmt1->execute() && $stmt2->execute()) {
        $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Updated a Admin', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        echo json_encode(['status' => 'success', 'message' => 'Admin updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update admin']);
    }
    $stmt1->close();
    $stmt2->close();
    exit();
}

// UPDATE PRINCIPAL
if ($data['action'] === 'updatePrincipal') {
    file_put_contents('log.txt', "updatePrincipal data: " . json_encode($data) . "\n", FILE_APPEND);
    $accid = intval($data['accid']);
    $principalid = $data['principalid'];
    $fname = $data['fname'];
    $mname = $data['mname'];
    $lname = $data['lname'];
    $email = $data['email'];
    $status = $data['status'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';
    // Update user_account
    $stmt1 = $conn->prepare("UPDATE user_account SET email = ?, password = ?, Status = ? WHERE accid = ?");
    $stmt1->bind_param("ssi", $email, $password,$status, $accid);

    // Update principal
    $stmt2 = $conn->prepare("UPDATE principal SET principalid = ?, fname = ?, mname = ?, lname = ? WHERE accid = ?");
    $stmt2->bind_param("ssssi", $principalid, $fname, $mname, $lname, $accid);

    if ($stmt1->execute() && $stmt2->execute()) {
          $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Updated a Principal', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
        echo json_encode(['status' => 'success', 'message' => 'Principal updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update principal']);
    }

    $stmt1->close();
    $stmt2->close();
    exit();
}
 
if ($data['action'] === 'deleteAccount') {
    $accid = $data['accid'] ?? '';
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';
    if (!empty($accid)) {
        $stmt = $conn->prepare("UPDATE user_account SET status = 0 WHERE AccID = ?");
        $stmt->bind_param("i", $accid);

        if ($stmt->execute()) {
            $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Deactivated Account', NOW())");
            $stmtLog->bind_param("si", $adminName,$accID);
            $stmtLog->execute();
            echo json_encode(['status' => 'success', 'message' => 'Account deactivated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to deactivate account']);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing account ID']);
    }

    exit;
}


}

if (isset($data['action']) && $data['action'] === 'getUserAccount') {
    file_put_contents('log.txt', "Request Data: " . json_encode($data) . "\n", FILE_APPEND);

    $sql = "
        SELECT 
            s.studid AS ID,
            s.fname AS Fname,
            s.mname AS Mname,
            s.lname AS Lname,
            ys.YearLevel AS Grade,
            ys.yearsecid AS YearSec,
            ys.SectionName AS Section,
            s.image AS image,
            ua.accid,
            ua.email,
            CASE ua.status WHEN 1 THEN 'Active' ELSE 'Inactive' END AS status,
            'Student' AS role
        FROM student s
        INNER JOIN user_account ua ON s.accid = ua.accid
        INNER JOIN enrollment e ON s.studid = e.studid
        INNER JOIN year_section ys ON e.yearsecid = ys.yearsecid
        INNER JOIN schoolyear sy ON e.schoolyearid = sy.schoolyearid
        WHERE sy.SchoolYearID = (
            SELECT MAX(sy2.SchoolYearID)
            FROM enrollment e2
            INNER JOIN schoolyear sy2 ON e2.schoolyearid = sy2.schoolyearid
            WHERE e2.studid = s.studid
        )

        UNION ALL

        SELECT 
            t.teacherid AS ID,
            t.fname AS Fname,
            t.mname AS Mname,
            t.lname AS Lname,
            NULL AS Grade,
            NULL AS YearSec,
            NULL AS Section,
            t.image AS image,
            ua.accid,
            ua.email,
            CASE ua.status WHEN 1 THEN 'Active' ELSE 'Inactive' END AS status,
            'Teacher' AS role
        FROM teacher t
        INNER JOIN user_account ua ON t.accid = ua.accid

        UNION ALL

        SELECT 
            p.principalid AS ID,
            p.fname AS Fname,
            p.mname AS Mname,
            p.lname AS Lname,
            NULL AS Grade,
            NULL AS YearSec,
            NULL AS Section,
            p.image AS image,
            ua.accid,
            ua.email,
            CASE ua.status WHEN 1 THEN 'Active' ELSE 'Inactive' END AS status,
            'Principal' AS role
        FROM principal p
        INNER JOIN user_account ua ON p.accid = ua.accid
    ";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit();
    }

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    file_put_contents('log.txt', "Response Data: " . json_encode($results) . "\n", FILE_APPEND);

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
