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

    // 1. Check for subject conflict per section
    foreach ($sections as $section) {
        $check = $conn->prepare("SELECT tys.TeacherID, tps.SubjectID 
                                 FROM teacher_yearsection tys
                                 INNER JOIN teacher_perSubject tps 
                                 ON tys.TeacherID = tps.TeacherID AND tys.SchoolYearID = tps.SchoolYearID
                                 WHERE tys.YearLevel = ? AND tys.SectionName = ? 
                                 AND tps.SubjectID = ? AND tys.SchoolYearID = ? AND tys.TeacherID != ?");
        $check->bind_param("ssiis", $grade, $section, $subjectID, $schoolYearID, $teacherID);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => "Section '$section' is already assigned to You for this subject."
            ]);
            exit();
        }
        $check->close();
    }

    // 2. Insert into teacher_perSubject if not already exists
    $checkSub = $conn->prepare("SELECT * FROM teacher_perSubject WHERE TeacherID = ? AND SubjectID = ? AND SchoolYearID = ?");
    $checkSub->bind_param("sii", $teacherID, $subjectID, $schoolYearID);
    $checkSub->execute();
    $subResult = $checkSub->get_result();

    if ($subResult->num_rows == 0) {
        $insertSub = $conn->prepare("INSERT INTO teacher_perSubject (TeacherID, SubjectID, SchoolYearID) VALUES (?, ?, ?)");
        $insertSub->bind_param("sii", $teacherID, $subjectID, $schoolYearID);
        $insertSub->execute();
        $insertSub->close();
    }
    $checkSub->close();

    // 3. Insert each section into teacher_yearsection
    foreach ($sections as $section) {
        // Check if already exists for this teacher
        $checkSection = $conn->prepare("SELECT * FROM teacher_yearsection 
                                        WHERE TeacherID = ? AND YearLevel = ? AND SectionName = ? AND SchoolYearID = ?");
        $checkSection->bind_param("sssi", $teacherID, $grade, $section, $schoolYearID);
        $checkSection->execute();
        $secResult = $checkSection->get_result();

        if ($secResult->num_rows == 0) {
            $insertSection = $conn->prepare("INSERT INTO teacher_yearsection (TeacherID, YearLevel, SectionName, SchoolYearID) VALUES (?, ?, ?, ?)");
            $insertSection->bind_param("sssi", $teacherID, $grade, $section, $schoolYearID);
            $insertSection->execute();
            $insertSection->close();
        }
        $checkSection->close();
    }

    
    // 4. Insert into teacher_subject_map
foreach ($sections as $section) {
    $checkMap = $conn->prepare("SELECT * FROM teacher_subjectmap 
                                WHERE TeacherID = ? AND SubjectID = ? AND YearLevel = ? AND SectionName = ? AND SchoolYearID = ?");
    $checkMap->bind_param("sissi", $teacherID, $subjectID, $grade, $section, $schoolYearID);
    $checkMap->execute();
    $mapResult = $checkMap->get_result();

    if ($mapResult->num_rows === 0) {
        $insertMap = $conn->prepare("INSERT INTO teacher_subjectmap 
                                     (TeacherID, SubjectID, YearLevel, SectionName, SchoolYearID) 
                                     VALUES (?, ?, ?, ?, ?)");
        $insertMap->bind_param("sissi", $teacherID, $subjectID, $grade, $section, $schoolYearID);
        $insertMap->execute();
        $insertMap->close();
    }else{
            echo json_encode([
            'status' => 'error',
            'message' => "Section is already assigned to other teacher for this subject."
        ]);
        exit();
    }

    $checkMap->close();
}
    echo json_encode([
    'status' => 'success',
    'message' => 'Subject and sections saved successfully.'
]);
exit();
}


if (isset($data['action']) && $data['action'] === 'getTeacherMappings') {
    $teacherID = $data['teacherID'];

    $stmt = $conn->prepare("SELECT 
                                s.SubjectID,
                                s.SubjectName,
                                tsm.YearLevel,
                                tsm.SectionName,
                                tsm.SchoolYearID
                            FROM teacher_subjectmap tsm
                            INNER JOIN subject s ON s.SubjectID = tsm.SubjectID
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

}

?>