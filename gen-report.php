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

if (isset($data['action']) && $data['action'] === 'getGradeLevels') {
    // ✅ Query distinct grade levels from year_section
    $stmt = $conn->prepare("SELECT DISTINCT YearLevel FROM year_section ORDER BY YearLevel ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row['YearLevel'];
    }

    // ✅ Optionally include "All" option at the top
    array_unshift($grades, 'All');

    echo json_encode([
        'status' => 'success',
        'grades' => $grades
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'getEvaluationAverageByGrade') {
    $schoolYearID = $data['SchoolYearID'] ?? '';
    $gradeLevel = $data['GradeLevel'] ?? 'All';
    $evalSetID  = $data['ESetID'] ?? '';

    if (!$schoolYearID || !$evalSetID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    // 🧠 Base SQL (includes all grades, subjects, and feedback)
    $sql = "
        SELECT 
            ys.YearLevel,
           Round( AVG(r.Score),1 ) AS AvgScore,
            GROUP_CONCAT(DISTINCT f.Comment SEPARATOR ' || ') AS Feedbacks
        FROM evaluation e
        INNER JOIN result r ON e.EvalID = r.EvalID
        LEFT JOIN feedback f ON e.EvalID = f.EvalID
        INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
        INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
        WHERE e.SchoolYearID = ?
          AND e.ESetID = ?
    ";

    // ➕ Add filter for specific grade only
    if (strtolower($gradeLevel) !== 'all') {
        $sql .= " AND ys.YearLevel = ? ";
    }

    $sql .= " GROUP BY ys.YearLevel ORDER BY ys.YearLevel ASC ";

    $stmt = $conn->prepare($sql);

    if (strtolower($gradeLevel) !== 'all') {
        $stmt->bind_param("iss", $schoolYearID, $evalSetID, $gradeLevel);
    } else {
        $stmt->bind_param("is", $schoolYearID, $evalSetID);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    // 🧩 Handle combined average if GradeLevel = "All"
    if (strtolower($gradeLevel) === 'all' && count($data) > 0) {
        $overallAvg = array_sum(array_column($data, 'AvgScore')) / count($data);
        $combinedFeedback = implode(' || ', array_unique(array_reduce($data, function ($carry, $item) {
            return array_merge($carry, explode(' || ', $item['Feedbacks'] ?? ''));
        }, [])));

        $data = [[
            'YearLevel' => 'All Grades (7-10)',
            'AvgScore' => round($overallAvg, 2),
            'Feedbacks' => $combinedFeedback
        ]];
    } 
    else if (count($data) === 1) {
        // ✅ For single grade, make sure it's one clean row
        $data[0]['AvgScore'] = round($data[0]['AvgScore'], 2);
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'getTrendAnalysisByGrade') {
    $gradeLevel = $data['GradeLevel'] ?? 'All';
    $evalSetID = $data['ESetID'] ?? null;

    // Base query: get average per grade per year
    $sql = "
        SELECT 
            sy.SchoolYear,
            ys.YearLevel,
            ROUND(AVG(r.Score), 1) AS AvgScore
        FROM evaluation e
        INNER JOIN result r ON e.EvalID = r.EvalID
        INNER JOIN enrollment enr ON e.StudID = enr.StudID AND e.SchoolYearID = enr.SchoolYearID
        INNER JOIN year_section ys ON enr.YearSecID = ys.YearSecID
        INNER JOIN schoolyear sy ON e.SchoolYearID = sy.SchoolYearID
        WHERE 1=1
    ";

    // Optional: filter by specific evaluation form
    if ($evalSetID) {
        $sql .= " AND e.ESetID = ? ";
    }

    // Optional: filter by grade
    if (strtolower($gradeLevel) !== 'all') {
        $sql .= " AND ys.YearLevel = ? ";
    }

    $sql .= " GROUP BY sy.SchoolYear, ys.YearLevel
              ORDER BY sy.SchoolYear ASC";

    $stmt = $conn->prepare($sql);

    // Bind parameters dynamically
    if ($evalSetID && strtolower($gradeLevel) !== 'all') {
        $stmt->bind_param("is", $evalSetID, $gradeLevel);
    } elseif ($evalSetID) {
        $stmt->bind_param("i", $evalSetID);
    } elseif (strtolower($gradeLevel) !== 'all') {
        $stmt->bind_param("s", $gradeLevel);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Group per SchoolYear → average across grades
    $tempData = [];
    while ($row = $result->fetch_assoc()) {
        $sy = $row['SchoolYear'];
        if (!isset($tempData[$sy])) {
            $tempData[$sy] = ['scores' => []];
        }
        $tempData[$sy]['scores'][] = (float)$row['AvgScore'];
    }

    $trendData = [];
    foreach ($tempData as $sy => $values) {
        $avgOfGrades = array_sum($values['scores']) / count($values['scores']);
        $trendData[] = [
            'SchoolYear' => $sy, // only the string
            'AvgScore' => round($avgOfGrades, 2)
        ];
    }

    echo json_encode([
        'status' => 'success',
        'trend' => $trendData
    ]);
    exit();
}


if (isset($data['action']) && $data['action'] === 'getEvaluationResponseCountByGrade') {
    $schoolYearID = $data['SchoolYearID'] ?? '';
    $gradeLevel = $data['GradeLevel'] ?? 'All';
    $evalSetID  = $data['ESetID'] ?? '';

    if (!$schoolYearID || !$evalSetID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    // ✅ Count total evaluations submitted by students (not per question)
    $sql = "
        SELECT 
            ys.YearLevel,
            COUNT(e.EvalID) AS TotalEvaluations
        FROM evaluation e
        INNER JOIN enrollment enr 
            ON e.StudID = enr.StudID 
           AND e.SchoolYearID = enr.SchoolYearID
        INNER JOIN year_section ys 
            ON enr.YearSecID = ys.YearSecID
        WHERE e.SchoolYearID = ?
          AND e.ESetID = ?
    ";

    // ➕ Add grade filter if specific level selected
    if (strtolower($gradeLevel) !== 'all') {
        $sql .= " AND ys.YearLevel = ? ";
    }

    $sql .= " GROUP BY ys.YearLevel ORDER BY ys.YearLevel ASC";

    $stmt = $conn->prepare($sql);

    if (strtolower($gradeLevel) !== 'all') {
        $stmt->bind_param("iss", $schoolYearID, $evalSetID, $gradeLevel);
    } else {
        $stmt->bind_param("is", $schoolYearID, $evalSetID);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'YearLevel' => $row['YearLevel'],
            'TotalEvaluations' => (int)$row['TotalEvaluations']
        ];
    }

    // 🧩 Combine if "All" selected
    if (strtolower($gradeLevel) === 'all' && count($data) > 0) {
        $total = array_sum(array_column($data, 'TotalEvaluations'));
        $data = [[
            'YearLevel' => 'All Grades (7–10)',
            'TotalEvaluations' => $total
        ]];
    }

    echo json_encode([
        'status' => 'success',
        'responses' => $data
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'getEvaluationCategoryBreakdownByGrade') {
    $schoolYearID = (int)($data['SchoolYearID'] ?? 0);
    $gradeLevel = strtolower($data['GradeLevel'] ?? 'all');
    $evalSetID  = $data['ESetID'] ?? '';

    if (!$schoolYearID || !$evalSetID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    // ============================================================
    // ✅ SCENARIO 1: SPECIFIC GRADE LEVEL
    // ============================================================
    if ($gradeLevel !== 'all') {
        $sql = "
            SELECT 
                c.CatID,
                c.CategoryName,
                AVG(r.Score) AS AvgScore
            FROM evaluation e
            INNER JOIN result r ON e.EvalID = r.EvalID
            INNER JOIN question q ON r.QuesID = q.QuesID
            INNER JOIN category c ON q.CatID = c.CatID
            INNER JOIN enrollment enr 
                ON enr.StudID = e.StudID 
               AND enr.SchoolYearID = e.SchoolYearID
            INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
            WHERE e.SchoolYearID = ? 
              AND e.ESetID = ?
              AND ys.YearLevel = ?
            GROUP BY c.CatID, c.CategoryName
            ORDER BY c.CatID ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $schoolYearID, $evalSetID, $gradeLevel);
        $stmt->execute();
        $result = $stmt->get_result();

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'CatID' => $row['CatID'],
                'CategoryName' => $row['CategoryName'],
                'AvgScore' => round((float)$row['AvgScore'], 1)
            ];
        }
        $stmt->close();

        // 🔹 Overall average for this grade
        $sqlOverall = "
            SELECT AVG(r.Score) AS OverallAvg
            FROM evaluation e
            INNER JOIN result r ON e.EvalID = r.EvalID
            INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
            INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
            WHERE e.SchoolYearID = ? 
              AND e.ESetID = ?
              AND ys.YearLevel = ?
        ";
        $stmtOverall = $conn->prepare($sqlOverall);
        $stmtOverall->bind_param("iss", $schoolYearID, $evalSetID, $gradeLevel);
        $stmtOverall->execute();
        $overallResult = $stmtOverall->get_result()->fetch_assoc();
        $overallAvg = (float)($overallResult['OverallAvg'] ?? 0);
        $stmtOverall->close();

        // 🔹 Normalize (optional, keeps category alignment)
        $categoryCount = count($categories);
        if ($categoryCount > 0 && $overallAvg > 0) {
            $totalCategoryAvg = array_sum(array_column($categories, 'AvgScore'));
            $rawMean = $totalCategoryAvg / $categoryCount;
            $ratio = ($rawMean > 0) ? ($overallAvg / $rawMean) : 1;
            foreach ($categories as &$cat) {
                $cat['AvgScore'] = round($cat['AvgScore'] * $ratio, 1);
            }
        }
    }

    // ============================================================
    // ✅ SCENARIO 2: ALL GRADE LEVELS (7–10) — WEIGHTED AVERAGE
    // ============================================================
    else {
        $grades = [7, 8, 9, 10];
        $perGradeResults = [];
        $gradeOverallAvgs = [];

        // 🔹 Step 0: Get all categories that appear in this eval set
        $sqlAllCats = "
            SELECT DISTINCT c.CatID, c.CategoryName
            FROM evaluation e
            INNER JOIN result r ON e.EvalID = r.EvalID
            INNER JOIN question q ON r.QuesID = q.QuesID
            INNER JOIN category c ON q.CatID = c.CatID
            WHERE e.SchoolYearID = ? AND e.ESetID = ?
        ";
        $stmtAllCats = $conn->prepare($sqlAllCats);
        $stmtAllCats->bind_param("is", $schoolYearID, $evalSetID);
        $stmtAllCats->execute();
        $resAllCats = $stmtAllCats->get_result();
        $allCategories = [];
        while ($row = $resAllCats->fetch_assoc()) {
            $allCategories[(int)$row['CatID']] = $row['CategoryName'];
        }
        $stmtAllCats->close();

        // 🔹 Loop over grades 7–10
        foreach ($grades as $grade) {
            $sql = "
                SELECT 
                    c.CatID,
                    AVG(r.Score) AS AvgScore,
                    COUNT(r.Score) AS RespCount
                FROM evaluation e
                INNER JOIN result r ON e.EvalID = r.EvalID
                INNER JOIN question q ON r.QuesID = q.QuesID
                INNER JOIN category c ON q.CatID = c.CatID
                INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
                INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
                WHERE e.SchoolYearID = ? AND e.ESetID = ? AND ys.YearLevel = ?
                GROUP BY c.CatID
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $schoolYearID, $evalSetID, $grade);
            $stmt->execute();
            $res = $stmt->get_result();

            $categoriesThisGrade = [];
            while ($row = $res->fetch_assoc()) {
                $categoriesThisGrade[(int)$row['CatID']] = [
                    'avg' => (float)$row['AvgScore'],
                    'count' => (int)$row['RespCount']
                ];
            }
            $stmt->close();

            // 🔹 Get overall avg per grade
            $sqlOverall = "
                SELECT AVG(r.Score) AS OverallAvg
                FROM evaluation e
                INNER JOIN result r ON e.EvalID = r.EvalID
                INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
                INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
                WHERE e.SchoolYearID = ? AND e.ESetID = ? AND ys.YearLevel = ?
            ";
            $stmtOverall = $conn->prepare($sqlOverall);
            $stmtOverall->bind_param("iss", $schoolYearID, $evalSetID, $grade);
            $stmtOverall->execute();
            $overallRow = $stmtOverall->get_result()->fetch_assoc();
            $overallAvgThisGrade = (float)($overallRow['OverallAvg'] ?? 0);
            $stmtOverall->close();
            $gradeOverallAvgs[$grade] = $overallAvgThisGrade;

            // 🔹 Normalize within grade
            if (!empty($categoriesThisGrade) && $overallAvgThisGrade > 0) {
                $rawMean = array_sum(array_column($categoriesThisGrade, 'avg')) / count($categoriesThisGrade);
                $ratio = ($rawMean > 0) ? ($overallAvgThisGrade / $rawMean) : 1;
                foreach ($categoriesThisGrade as &$vals) {
                    $vals['avg'] = $vals['avg'] * $ratio;
                }
                unset($vals);
            }

            // 🔹 Store results per category
            foreach ($allCategories as $catID => $catName) {
                if (!isset($perGradeResults[$catID])) {
                    $perGradeResults[$catID] = [
                        'CategoryName' => $catName,
                        'grades' => []
                    ];
                }
                if (isset($categoriesThisGrade[$catID])) {
                    $perGradeResults[$catID]['grades'][$grade] = $categoriesThisGrade[$catID];
                }
            }
        }

        // 🔹 Compute weighted average for all grades
        $categories = [];
        foreach ($perGradeResults as $catID => $info) {
            $weightedSum = 0;
            $totalCount = 0;
            foreach ($info['grades'] as $grade => $vals) {
                $weightedSum += $vals['avg'] * $vals['count'];
                $totalCount += $vals['count'];
            }
            $finalAvg = $totalCount > 0 ? ($weightedSum / $totalCount) : 0;
            $categories[] = [
                'CatID' => $catID,
                'CategoryName' => $info['CategoryName'],
                'AvgScore' => round($finalAvg, 1)
            ];
        }

        // 🔹 Compute overall average across all grades
        $sqlOverallAll = "
            SELECT AVG(r.Score) AS OverallAvg
            FROM evaluation e
            INNER JOIN result r ON e.EvalID = r.EvalID
            INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
            INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
            WHERE e.SchoolYearID = ? AND e.ESetID = ?
        ";
        $stmtOverallAll = $conn->prepare($sqlOverallAll);
        $stmtOverallAll->bind_param("is", $schoolYearID, $evalSetID);
        $stmtOverallAll->execute();
        $overallResultAll = $stmtOverallAll->get_result()->fetch_assoc();
        $overallAvg = (float)($overallResultAll['OverallAvg'] ?? 0);
        $stmtOverallAll->close();
    }

    // ============================================================
    // ✅ FINAL OUTPUT
    // ============================================================
    echo json_encode([
        'status' => 'success',
        'overallAverage' => round($overallAvg, 1),
        'categories' => $categories
    ]);
    exit();
}



if (isset($data['action']) && $data['action'] === 'getSubmissionCountByGrade') {
    $schoolYearID = $data['SchoolYearID'] ?? '';
    $evalSetID  = $data['ESetID'] ?? '';

    if (!$schoolYearID || !$evalSetID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    $sql = "
        SELECT 
            ys.YearLevel AS GradeLevel,
            COUNT(DISTINCT e.StudID) AS SubmissionCount
        FROM evaluation e
        INNER JOIN enrollment enr ON e.StudID = enr.StudID AND e.SchoolYearID = enr.SchoolYearID
        INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
        WHERE e.SchoolYearID = ? 
          AND e.ESetID = ?
        GROUP BY ys.YearLevel
        ORDER BY ys.YearLevel ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $schoolYearID, $evalSetID);
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



}

?>