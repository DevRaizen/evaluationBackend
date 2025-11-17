<?php
session_start(); 
if (!isset($_SESSION['accID']) && isset($_COOKIE['rememberMe'])) {
    $_SESSION['accID'] = $_COOKIE['rememberMe'];
}
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php'; // Include database connection
$data = json_decode(file_get_contents("php://input"), true);

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

if (isset($data['action']) && $data['action'] === 'getSchoolYears') {
    $stmt = $conn->prepare("SELECT SchoolYearID, SchoolYear, Status FROM schoolyear ORDER BY SchoolYear DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $schoolYears = [];
    while ($row = $result->fetch_assoc()) {
        $schoolYears[] = [
            'SchoolYearID' => $row['SchoolYearID'],
            'SchoolYear' => $row['SchoolYear'],
            'Status' => $row['Status'] // ✅ include this
        ];
    }

    echo json_encode([
        'status' => 'success',
        'schoolYears' => $schoolYears
    ]);
    exit();
}



if (isset($data['action']) && $data['action'] === 'addSchoolYear') {
    $schoolYear = trim($data['SchoolYear']);
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';
    // Check if the school year already exists
    $check = $conn->prepare("SELECT COUNT(*) AS count FROM schoolyear WHERE SchoolYear = ?");
    $check->bind_param("s", $schoolYear);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'School year already exists.']);
        exit();
    }

    // Insert new school year (default status = 'Inactive')
    $stmt = $conn->prepare("INSERT INTO schoolyear (SchoolYear, Status) VALUES (?, 'Inactive')");
    $stmt->bind_param("s", $schoolYear);

    if ($stmt->execute()) {
        $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Added School Year', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
        echo json_encode(['status' => 'success', 'message' => 'School year added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add school year.']);
    }
    exit();
}

/* ===========================
   🟣 Update School Year Status
   =========================== */
if (isset($data['action']) && $data['action'] === 'setActiveSchoolYear') {
    $newActiveID = $data['NewActiveID'] ?? null;
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';

    if (!$newActiveID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing SchoolYearID']);
        exit();
    }

    // Inactivate all school years first
    $conn->query("UPDATE schoolyear SET Status = 'Inactive'");

    // Activate the selected school year
    $stmt = $conn->prepare("UPDATE schoolyear SET Status = 'Active' WHERE SchoolYearID = ?");
    $stmt->bind_param('i', $newActiveID);

    if ($stmt->execute()) {
        $stmtLog = $conn->prepare("INSERT INTO logs (Name,AccID, Activity, TimeStamp) VALUES (?, ?,'Set New School Year', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
        echo json_encode(['status' => 'success', 'message' => 'School year set as active successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update school year']);
    }
    exit();
}

if (isset($data['action']) && $data['action'] === 'getLogs') {
    $result = $conn->query("SELECT LogID, Name, Activity, TimeStamp FROM logs ORDER BY TimeStamp DESC");

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $logs]);
    exit();
}

/* =========================== */
if (isset($data['action']) && $data['action'] === 'addSection') {
    $gradeLevel = $data['grade'] ?? '';
    $sectionName = $data['sectionName'] ?? '';
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';

    if (empty($gradeLevel) || empty($sectionName)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields (YearLevel or SectionName).'
        ]);
        exit;
    }

    // Check for duplicates
    $check = $conn->prepare("SELECT * FROM year_section WHERE YearLevel = ? AND SectionName = ?");
    $check->bind_param("ss", $gradeLevel, $sectionName);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This section already exists for the selected grade.'
        ]);
        exit;
    }

    // Insert new section
    $res = $conn->query("SELECT YearSecID FROM year_section ORDER BY YearSecID DESC LIMIT 1");
    $newID = 1; // default if table is empty
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $newID = (int)$row['YearSecID'] + 1;
    }

    // Insert new section with generated ID
    $stmt = $conn->prepare("INSERT INTO year_section (YearSecID, YearLevel, SectionName) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $newID, $gradeLevel, $sectionName);


    if ($stmt->execute()) {
        $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?,'Added Section', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
        echo json_encode([
            'status' => 'success',
            'message' => 'Section successfully added.'
        ]);
        exit;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to add section.'
        ]);
        exit;
    }

    $stmt->close();
    $check->close();
}

if ($data['action'] === 'updateEmail') {
    file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);

    $accid = $data['accid'] ?? '';
    $newemail = $data['newemail'] ?? '';

    if (!empty($accid) && !empty($newemail)) {
        // Step 1: Check if the new email already exists in another account
        $check = $conn->prepare("SELECT AccID FROM user_account WHERE Email = ? AND AccID != ?");
        $check->bind_param("si", $newemail, $accid);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
            $check->close();
            exit;
        }
        $check->close();

        // Step 2: Proceed with update if email not found
        $stmt = $conn->prepare("UPDATE user_account SET Email = ? WHERE AccID = ?");
        $stmt->bind_param("si", $newemail, $accid);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Email updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update email']);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing email or account ID']);
    }

    exit;
}


if ($data['action'] === 'updatePassword') {
    $accid = $data['accid'] ?? '';
    $newpassword = $data['newpassword'] ?? '';

    if (!empty($accid) && !empty($newpassword)) {
        $hashed = password_hash($newpassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE user_account SET Password = ? WHERE AccID = ?");
        $stmt->bind_param("si", $hashed, $accid);
        echo $stmt->execute() 
            ? json_encode(['status' => 'success', 'message' => 'Password updated successfully'])
            : json_encode(['status' => 'error', 'message' => 'Failed to update password']);
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing password or account ID']);
    }
    exit;
}
?>
