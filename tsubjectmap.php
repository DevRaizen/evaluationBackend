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

    if(isset($data['action']) && $data['action'] === 'getSchoolYear'){
        $stmt = $conn->prepare("SELECT DISTINCT SchoolYear FROM Enrollment ORDER BY SchoolYear DESC");
        $stmt->execute();
        $result = $stmt->get_result();

        $schoolYears = [];
        while ($row = $result->fetch_assoc()) {
            $schoolYears[] = $row['SchoolYear'];
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
    $schoolYear = $data['schoolYear'];

    // 1. Check for subject conflict per section
    foreach ($sections as $section) {
        $check = $conn->prepare("SELECT tys.TeacherID, tps.SubjectID 
                                 FROM teacher_yearsection tys
                                 INNER JOIN teacher_perSubject tps 
                                 ON tys.TeacherID = tps.TeacherID AND tys.SchoolYear = tps.SchoolYear
                                 WHERE tys.YearLevel = ? AND tys.SectionName = ? 
                                 AND tps.SubjectID = ? AND tys.SchoolYear = ? AND tys.TeacherID != ?");
        $check->bind_param("ssiss", $grade, $section, $subjectID, $schoolYear, $teacherID);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => "Section '$section' is already assigned to another teacher for this subject."
            ]);
            exit();
        }
        $check->close();
    }

    // 2. Insert into teacher_perSubject if not already exists
    $checkSub = $conn->prepare("SELECT * FROM teacher_perSubject WHERE TeacherID = ? AND SubjectID = ? AND SchoolYear = ?");
    $checkSub->bind_param("sis", $teacherID, $subjectID, $schoolYear);
    $checkSub->execute();
    $subResult = $checkSub->get_result();

    if ($subResult->num_rows == 0) {
        $insertSub = $conn->prepare("INSERT INTO teacher_perSubject (TeacherID, SubjectID, SchoolYear) VALUES (?, ?, ?)");
        $insertSub->bind_param("sis", $teacherID, $subjectID, $schoolYear);
        $insertSub->execute();
        $insertSub->close();
    }
    $checkSub->close();

    // 3. Insert each section into teacher_yearsection
    foreach ($sections as $section) {
        // Check if already exists for this teacher
        $checkSection = $conn->prepare("SELECT * FROM teacher_yearsection 
                                        WHERE TeacherID = ? AND YearLevel = ? AND SectionName = ? AND SchoolYear = ?");
        $checkSection->bind_param("ssss", $teacherID, $grade, $section, $schoolYear);
        $checkSection->execute();
        $secResult = $checkSection->get_result();

        if ($secResult->num_rows == 0) {
            $insertSection = $conn->prepare("INSERT INTO teacher_yearsection (TeacherID, YearLevel, SectionName, SchoolYear) VALUES (?, ?, ?, ?)");
            $insertSection->bind_param("ssss", $teacherID, $grade, $section, $schoolYear);
            $insertSection->execute();
            $insertSection->close();
        }
        $checkSection->close();
    }

    
    // 4. Insert into teacher_subject_map
foreach ($sections as $section) {
    $checkMap = $conn->prepare("SELECT * FROM teacher_subjectmap 
                                WHERE TeacherID = ? AND SubjectID = ? AND YearLevel = ? AND SectionName = ? AND SchoolYear = ?");
    $checkMap->bind_param("sisss", $teacherID, $subjectID, $grade, $section, $schoolYear);
    $checkMap->execute();
    $mapResult = $checkMap->get_result();

    if ($mapResult->num_rows === 0) {
        $insertMap = $conn->prepare("INSERT INTO teacher_subjectmap 
                                     (TeacherID, SubjectID, YearLevel, SectionName, SchoolYear) 
                                     VALUES (?, ?, ?, ?, ?)");
        $insertMap->bind_param("sisss", $teacherID, $subjectID, $grade, $section, $schoolYear);
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
                                tsm.SchoolYear
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