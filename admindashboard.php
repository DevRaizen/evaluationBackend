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

if(isset($data['action']) && $data['action'] === 'count_students') {
       
        $stmt = $conn->prepare("SELECT count(*) as count FROM student s
                                inner join user_account ua on ua.AccID = s.AccID 
                                where ua.status = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc(); 

         echo json_encode([
        'status' => 'success',
        'count' => $row['count']
    ]);
    exit();
}
   
if(isset($data['action']) && $data['action'] === 'count_teachers'){
    $stmt = $conn->prepare("SELECT count(*) as count from teacher");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc(); 
    echo json_encode([
        'status' => 'success',
        'count' => $row['count']
    ]);
}

if (isset($data['action']) && $data['action'] === 'count_students_by_grade') {
    
    $schoolYearID = getActiveSchoolYearID($conn);  

    $sql = "
    SELECT yr.YearLevel as grade, COUNT(e.studid) as count from enrollment e
    Inner JOIN year_section yr on e.YearSecID = yr.YearSecID 
    inner join student s on e.studid = s.studid
    inner join user_account ua on s.AccID = ua.AccID
    WHERE yr.YearLevel in('7','8','9','10') and e.SchoolYearID = ? and ua.status = 1
    GROUP by yr.YearLevel ORDER by yr.YearLevel;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['grade']] = (int)$row['count'];
    }

    // Ensure all grades appear even if 0 students
    foreach (['7','8','9','10'] as $g) {
        if (!isset($counts[$g])) {
            $counts[$g] = 0;
        }
    }

    echo json_encode([
        'status' => 'success',
        'counts' => $counts
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'getTop3TeachersByAverage') {
    $schoolYearID = (int)($data['SchoolYearID'] ?? 0);

    if (!$schoolYearID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing SchoolYearID']);
        exit();
    }

    $sql = "
        SELECT 
            e.TeacherID,
            CONCAT(t.Fname, ' ', t.Lname) AS TeacherName,
            t.Image AS TeacherImage,
            e.SchoolYearID,
            r.CatID,
            ROUND(AVG(r.Score), 1) AS AvgScore
        FROM evaluation e
        INNER JOIN result r ON e.EvalID = r.EvalID
        INNER JOIN teacher t ON e.TeacherID = t.TeacherID
        WHERE e.SchoolYearID = ?
        GROUP BY e.TeacherID, e.SchoolYearID, t.Fname, t.Lname, t.Image, r.CatID
        ORDER BY e.TeacherID ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'success', 'top3' => []]);
        exit();
    }

    $teacherData = [];
    while ($row = $result->fetch_assoc()) {
        $tid = $row['TeacherID'];
        if (!isset($teacherData[$tid])) {
            $teacherData[$tid] = [
                'TeacherID' => $tid,
                'TeacherName' => $row['TeacherName'],
                'TeacherImage' => $row['TeacherImage'],
                'SchoolYearID' => $row['SchoolYearID'],
                'CategoryScores' => [],
                'FinalAvg' => 0
            ];
        }
        $teacherData[$tid]['CategoryScores'][] = (float)$row['AvgScore'];
    }

    foreach ($teacherData as &$teacher) {
        if (count($teacher['CategoryScores']) > 0) {
            $teacher['FinalAvg'] = round(array_sum($teacher['CategoryScores']) / count($teacher['CategoryScores']), 1);
        }
    }
    unset($teacher);

    // Sort by average descending
    usort($teacherData, fn($a, $b) => $b['FinalAvg'] <=> $a['FinalAvg']);

    // Get unique averages
    $uniqueAverages = array_values(array_unique(array_map(fn($t) => $t['FinalAvg'], $teacherData)));

    // Get top 3 unique averages
    $top3Averages = array_slice($uniqueAverages, 0, 3);

    // Filter teachers who belong to those top 3 averages
    $topTeachers = array_filter($teacherData, fn($t) => in_array($t['FinalAvg'], $top3Averages));

    echo json_encode([
        'status' => 'success',
        'top3' => array_values($topTeachers)
    ]);
    exit();
}


if (isset($data['action']) && $data['action'] === 'getHighestCategory') {
    file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
    $teacherID = $data['TeacherID'] ?? null;
    $schoolYearID = (int)($data['SchoolYearID'] ?? 0);

    if (!$teacherID || !$schoolYearID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing TeacherID or SchoolYearID']);
        exit();
    }

    
    $sql = "
        SELECT r.CatID, c.categoryName, ROUND(AVG(r.Score),1) AS AvgScore
        FROM evaluation e
        INNER JOIN result r ON e.EvalID = r.EvalID
        INNER JOIN category c on c.CatID = r.CatID
        WHERE e.TeacherID = ? AND e.SchoolYearID = ?
        GROUP BY r.CatID
        ORDER BY AvgScore DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $teacherID, $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'TeacherID' => $teacherID,
            'HighestCategoryName' => $row['categoryName'], 
            'HighestCategoryScore' => (float)$row['AvgScore']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No category found for this teacher'
        ]);
    }
    exit();
}



if (isset($data['action']) && $data['action'] === 'getRawSubmissionCountByGrade') {
     $schoolYearID = getActiveSchoolYearID($conn);  
    $evalSetID  = $data['ESetID'] ?? '';

    if (!$schoolYearID || !$evalSetID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    $sql = "
        SELECT 
            ys.YearLevel AS GradeLevel,
            COUNT(e.StudID) AS SubmissionCount
        FROM evaluation e
        INNER JOIN enrollment enr ON e.StudID = enr.StudID AND e.SchoolYearID = enr.SchoolYearID
        INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
        WHERE e.SchoolYearID = ? 
          AND e.ESetID = ?
        GROUP BY ys.YearLevel
        ORDER BY ys.YearLevel ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $schoolYearID, $evalSetID);
    $stmt->execute();
    $result = $stmt->get_result();

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[(int)$row['GradeLevel']] = (int)$row['SubmissionCount'];
    }

    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'grades' => $grades
        ]
    ]);
    exit();
}


?>
