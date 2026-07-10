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

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(isset($data['action']) && $data['action'] === 'getYearSec'){
        $stmt = $conn->prepare("Select * from year_section");
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];

        while($row = $result->fetch_assoc()){
            $results[] = $row;
        }
        
        echo json_encode([
        'status' => 'success',
        'yearsec' => $results
        ]);
        exit();
    }

    if(isset($data['action']) && $data['action'] === 'getSubPerYear'){
        $grade = $data['grade'];
        $stmt = $conn->prepare("SELECT sp.YearLevel, sub.subjectname, sub.subjectid from subject_peryear sp
                                INNER join subject sub on sub.SubjectID =  sp.SubjectID
                                where sp.YearLevel = ?");
        $stmt->bind_param("s",$grade);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];

        while($row = $result->fetch_assoc()){
            $results[] = $row;
        }
        
        echo json_encode([
        'status' => 'success',
        'subject' => $results
        ]);
        exit();
    }

  if (isset($data['action']) && $data['action'] === 'getSchoolYear') {
    $stmt = $conn->prepare("SELECT SchoolYearID, SchoolYear FROM schoolyear ORDER BY SchoolYear DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $schoolYears = [];
    while ($row = $result->fetch_assoc()) {
        $schoolYears[] = [
            'SchoolYearID' => $row['SchoolYearID'],
            'SchoolYear' => $row['SchoolYear']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'schoolYears' => $schoolYears
    ]);
    exit();
}


if (isset($data['action']) && $data['action'] === 'getActiveSchoolYear') {
    $stmt = $conn->prepare("SELECT SchoolYearID FROM schoolyear WHERE Status = 'Active' ORDER BY SchoolYear DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $schoolYears = [];
    while ($row = $result->fetch_assoc()) {
        $schoolYears[] = [
            'SchoolYearID' => $row['SchoolYearID'],
        ];
    }

    echo json_encode([
        'status' => 'success',
        'schoolYears' => $schoolYears
    ]);
    exit();
}


if (isset($data['action']) && $data['action'] === 'saveSubjectMapping') {
    $teacherID = $data['teacherID'];
    $subjectID = intval($data['subjectID']);
    $grade = $data['grade'];
    $sections = $data['sections']; // Array of section names
    $schoolYearID = $data['schoolYearID'];
    $TeacherName = $data['Teacher'] ?? '';
    $accID = $data['AccID'] ?? '';

    foreach ($sections as $section) {

        // 🔍 1. Check if another teacher already assigned this subject for the same section
      $checkOther = $conn->prepare("
    SELECT tsm.*, Concat(t.fname, ' ', t.mname, ' ', t.lname) as TeacherName, s.SubjectName
    FROM teacher_subjectmap tsm
    INNER JOIN teacher t ON tsm.TeacherID = t.TeacherID
    inner join subject s on s.SubjectID = tsm.SubjectID
    WHERE tsm.YearLevel = ? 
      AND tsm.SectionName = ? 
      AND tsm.SubjectID = ? 
      AND tsm.SchoolYearID = ? 
      AND tsm.TeacherID != ?
");

        $checkOther->bind_param("ssiis", $grade, $section, $subjectID, $schoolYearID, $teacherID);
        $checkOther->execute();
        $resOther = $checkOther->get_result();

        if ($resOther->num_rows > 0) {
            $row = $resOther->fetch_assoc();
            $otherTeacherName = $row['TeacherName'];
            $SubjectName = $row['SubjectName'];

            echo json_encode([
                'status' => 'error',
                'message' => "$section is already assigned to $otherTeacherName for the subject $SubjectName."
            ]);
            exit();
        }

        $checkOther->close();


        // 🔍 2. Check if this teacher already assigned this same subject & section (avoid duplicates)
        $checkSelf = $conn->prepare("
            SELECT tsm.*, s.SubjectName FROM teacher_subjectmap tsm
            inner Join subject s on s.SubjectID = tsm.SubjectID
            WHERE tsm.TeacherID = ? 
              AND tsm.SubjectID = ? 
              AND tsm.YearLevel = ? 
              AND tsm.SectionName = ? 
              AND tsm.SchoolYearID = ?
        ");
        $checkSelf->bind_param("sissi", $teacherID, $subjectID, $grade, $section, $schoolYearID);
        $checkSelf->execute();
        $resSelf = $checkSelf->get_result();

        if ($resSelf->num_rows > 0) {
            $row = $resSelf->fetch_assoc();
            $SubjectName = $row['SubjectName'];
            echo json_encode([
                'status' => 'error',
                'message' => "You are already assigned to the subject $SubjectName for section $section."
            ]);
            exit();
        }
        $checkSelf->close();


        // ✅ 3. If no conflict, insert new mapping
        $insert = $conn->prepare("
            INSERT INTO teacher_subjectmap (TeacherID, SubjectID, YearLevel, SectionName, SchoolYearID)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insert->bind_param("sissi", $teacherID, $subjectID, $grade, $section, $schoolYearID);
        $insert->execute();
        $insert->close();
    }
         $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Saved Subject Mappings', NOW())");
        $stmtLog->bind_param("si", $TeacherName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
    echo json_encode([
        'status' => 'success',
        'message' => 'Subject and section mapping saved successfully.'
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'deleteSubjectMapping') {
    $teacherID = $data['teacherID'];
    $subjectID = intval($data['subjectID']);
    $schoolYearID = $data['schoolYearID'];
    $sectionName = $data['sectionName'] ?? ''; // Section to delete
    $TeacherName = $data['Teacher'] ?? '';
    $accID = $data['AccID'] ?? '';

    file_put_contents('log.txt', "Request Data: " . json_encode($data) . "\n", FILE_APPEND);

    // 1️⃣ Check if any evaluations already exist for this teacher & subject in the school year
    $checkEval = $conn->prepare("
        SELECT 1 FROM evaluation
        WHERE TeacherID = ?
          AND SubjectID = ?
          AND SchoolYearID = ?
        LIMIT 1
    ");
    $checkEval->bind_param("sii", $teacherID, $subjectID, $schoolYearID);
    $checkEval->execute();
    $resEval = $checkEval->get_result();

    if ($resEval->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => "Cannot delete mapping. Students in this subject already have evaluations."
        ]);
        $checkEval->close();
        exit();
    }
    $checkEval->close();

    // 2️⃣ Check if the mapping exists before attempting delete
    $checkMap = $conn->prepare("
        SELECT 1 FROM teacher_subjectmap
        WHERE TeacherID = ?
          AND SubjectID = ?
          AND SectionName = ?
          AND SchoolYearID = ?
        LIMIT 1
    ");
    $checkMap->bind_param("sisi", $teacherID, $subjectID, $sectionName, $schoolYearID);
    $checkMap->execute();
    $resMap = $checkMap->get_result();

    if ($resMap->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => "No subject mapping found to delete."
        ]);
        $checkMap->close();
        exit();
    }
    $checkMap->close();

    // 3️⃣ Delete the mapping
    $delete = $conn->prepare("
        DELETE FROM teacher_subjectmap
        WHERE TeacherID = ?
          AND SubjectID = ?
          AND SectionName = ?
          AND SchoolYearID = ?
    ");
    $delete->bind_param("sisi", $teacherID, $subjectID, $sectionName, $schoolYearID);
    $delete->execute();

    if ($delete->affected_rows > 0) {
        // 4️⃣ Log the deletion
        $stmtLog = $conn->prepare("
            INSERT INTO logs (Name, AccID, Activity, TimeStamp) 
            VALUES (?, ?, 'Deleted Subject Mapping', NOW())
        ");
        $stmtLog->bind_param("si", $TeacherName, $accID);
        $stmtLog->execute();
        $stmtLog->close();

        echo json_encode([
            'status' => 'success',
            'message' => "Subject mapping deleted successfully."
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => "Failed to delete subject mapping."
        ]);
    }

    $delete->close();
    exit();
}



if (isset($data['action']) && $data['action'] === 'getTeacherMappings') {
    $teacherID = $data['teacherID'];

    $stmt = $conn->prepare("SELECT 
                                s.SubjectID,
                                s.SubjectName,
                                tsm.YearLevel,
                                tsm.SectionName,
                                tsm.SchoolYearID,
                                sy.SchoolYear
                            FROM teacher_subjectmap tsm
                            INNER JOIN subject s ON s.SubjectID = tsm.SubjectID
                            inner join SchoolYear sy on sy.SchoolYearID = tsm.SchoolYearID
                            WHERE tsm.TeacherID = ?");
    $stmt->bind_param("s", $teacherID);
    $stmt->execute();
    $result = $stmt->get_result();

    $mappings = [];
    while ($row = $result->fetch_assoc()) {
        $mappings[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'mappings' => $mappings
    ]);
    exit();
}


if (isset($data['action']) && $data['action'] === 'getTeacherSubjectMap') {
    file_put_contents('log.txt', "Request Data: " . json_encode($data) . "\n", FILE_APPEND);
    $schoolYearID = $data['schoolYearID'];

    $sql = "SELECT 
                tsm.tsmID,
                tsm.TeacherID,
                tsm.SubjectID,
                s.SubjectName,
                tsm.YearLevel,
                tsm.SectionName,
                tsm.SchoolYearID,
                CONCAT(t.fname, ' ', t.mname, ' ', t.lname) AS TeacherName,
                t.image AS TeacherImage
            FROM teacher_subjectmap tsm
            INNER JOIN teacher t ON tsm.TeacherID = t.TeacherID
            inner join subject s on s.SubjectId = tsm.SubjectID
            WHERE tsm.SchoolYearID = ?
            ORDER BY t.lname, t.fname";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teacherID = $row['TeacherID'];
        if (!isset($teachers[$teacherID])) {
        $teachers[$teacherID] = [
            'TeacherID' => $row['TeacherID'],
            'TeacherName' => $row['TeacherName'],
            'TeacherImage' => $row['TeacherImage'],
            'SchoolYearID' => $row['SchoolYearID'],
            'subjects' => []  // nested subjects array
        ];
    }

    // Push each subject into the teacher's subjects array
    $teachers[$teacherID]['subjects'][] = [
        'tsmID' => $row['tsmID'],
        'SubjectID' => $row['SubjectID'],
        'SubjectName' => $row['SubjectName'],
        'YearLevel' => $row['YearLevel'],
        'SectionName' => $row['SectionName']
    ];
    }

    echo json_encode([
    'status' => 'success',
    'data' => array_values($teachers) 
]);
    exit;
}


}

?>