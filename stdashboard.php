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
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include 'db.php'; // Include database connection
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


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $data = json_decode(file_get_contents("php://input"), true);
    if(isset($data['action']) && $data['action'] === 'getTeacher'){
        // Kunin ko muna yung YearSection ID nung Student sa enrollment table
      $studID = $data['StudID']; 

      $stmt = $conn->prepare("Select yearsecid from enrollment Where StudID = ?");
      $stmt->bind_param("s",$studID);
      $stmt->execute();
      $res = $stmt->get_result();

      if($res->num_rows === 0){
         echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        exit();
      }
      $row = $res->fetch_assoc();
      $yearsecID = $row['yearsecid'];
      $stmt->close();

        // kuhanin ko namna kung ano yung yearLevel at Section nung YearSecID na nakuha kay student
        $stmt = $conn->prepare("select YearLevel, SectionName from year_section Where YearSecId = ?");
        $stmt->bind_param("i",$yearsecID);
        $stmt->execute(); 
        $res = $stmt->get_result();

        if($res->num_rows === 0){
            echo json_encode(['status' => 'error', 'message' => 'Year section not found']);
            exit();
        }
        $sectionRow = $res->fetch_assoc();
        $YearLevel = $sectionRow['YearLevel'];
        $SectionName = $sectionRow['SectionName'];
        $stmt->close();

        $SchoolYearID = getActiveSchoolYearID($conn);

        //kuhanin kona yung teacher sa isang section at yung Subject Nila gamit yung YearLevel at Section name
        $stmt = $conn->prepare("Select t.Fname, t.Mname, t.Lname, s.SubjectName, tsm.YearLevel, tsm.SectionName, tsm.TeacherID, tsm.SubjectID, t.image, tsm.SchoolYearID
                                from teacher_subjectmap tsm 
                                INNER JOIN teacher t on tsm.TeacherID = t.TeacherID
                                inner join subject s on tsm.SubjectID = s.subjectID
                                WHERE
                                tsm.SchoolYearID = ? and tsm.YearLevel = ? and tsm.SectionName = ?");
        $stmt->bind_param("iss", $SchoolYearID,$YearLevel,$SectionName);
        $stmt->execute();
        $res = $stmt->get_result();

        $mappings = [];

        while($row = $res->fetch_assoc()){
            $mappings[] = $row;
        }
        $stmt->close();
       
        echo json_encode([
            'status' => 'success',
            'mappings' => $mappings
        ]);

        exit();
    }

    if (isset($data['action']) && $data['action'] === 'getUnevaluatedTeachers') {
    $studID = $data['StudID'] ?? '';
    $eSetID = $data['ESetID'] ?? '';

   
    // 1️ Find the YearSecID of the student
    $stmt = $conn->prepare("SELECT YearSecID FROM enrollment WHERE StudID = ?");
    $stmt->bind_param("s", $studID);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        exit();
    }
    $row = $res->fetch_assoc();
    $yearsecID = $row['YearSecID'];
    $stmt->close();

    //   Get YearLevel & SectionName
    $stmt = $conn->prepare("SELECT YearLevel, SectionName FROM year_section WHERE YearSecID = ?");
    $stmt->bind_param("i", $yearsecID);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Year section not found']);
        exit();
    }
    $secRow     = $res->fetch_assoc();
    $YearLevel  = $secRow['YearLevel'];
    $Section    = $secRow['SectionName'];
    $stmt->close();

    // 3️ Current School Year helper (same as your existing code)
    $SchoolYearID = getActiveSchoolYearID($conn);

    /* 
       4️  Fetch all teachers for this section/year
           BUT skip those already evaluated by this student
           for the same ESetID & SchoolYear
    */
    $sql = "
        SELECT t.TeacherID,
               t.Fname,
               t.Mname,
               t.Lname,
               t.image,
               s.SubjectID,
               s.SubjectName,
               tsm.YearLevel,
               tsm.SectionName,
               tsm.SchoolYearID
        FROM teacher_subjectmap tsm
        INNER JOIN teacher t ON tsm.TeacherID = t.TeacherID
        INNER JOIN subject s ON tsm.SubjectID = s.SubjectID
        WHERE tsm.SchoolYearID = ?
          AND tsm.YearLevel  = ?
          AND tsm.SectionName = ?
          AND NOT EXISTS (
                SELECT 1
                FROM evaluation e
                WHERE e.StudID    = ?
                  AND e.TeacherID = tsm.TeacherID
                  AND e.SubjectID = tsm.SubjectID
                  AND e.ESetID    = ?
                  AND e.SchoolYearID = ?
          )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssii",
        $SchoolYearID,
        $YearLevel,
        $Section,
        $studID,
        $eSetID,
        $SchoolYearID
    );
    $stmt->execute();
    $res = $stmt->get_result();

    $mappings = [];
    while ($row = $res->fetch_assoc()) {
        $mappings[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'status'   => 'success',
        'mappings' => $mappings
    ]);
    exit();
}



}

?>