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

        if (isset($data['action']) && $data['action'] === 'getEvaluationAllSettings') {

        $stmt = $conn->prepare( "SELECT * FROM Evaluation_Settings ORDER BY StartDate DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];

         while($row = $result->fetch_assoc()){
            $results[] = $row;
        }

         echo json_encode([
        'status' => 'success',
        'evalsettings' => $results
    ]);
    exit();   
    }

if (isset($data['action']) && $data['action'] === 'getGradeLevels') {
    // ✅ Query distinct grade levels from year_section
    $stmt = $conn->prepare("SELECT DISTINCT YearLevel FROM year_section ORDER BY YearLevel asc");
    $stmt->execute();
    $result = $stmt->get_result();

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row['YearLevel'];
    }


    array_unshift($grades, 'All');

    echo json_encode([
        'status' => 'success',
        'grades' => $grades
    ]);
    exit();
}


if (isset($data['action']) && $data['action'] === 'getEvaluationAverageByGrade') {
     file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
    $schoolYearID = (int)($data['SchoolYearID'] ?? 0);
    $gradeLevel   = $data['GradeLevel'] ?? 'All';
    $QIDraw       = $data['QID'] ?? '';

    if (!$schoolYearID || $QIDraw === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    // Detect QID bind type dynamically
    $qidIsInt = is_numeric($QIDraw) && ctype_digit((string)$QIDraw);
    $QID      = $qidIsInt ? (int)$QIDraw : (string)$QIDraw;

    $isAll = (strtolower($gradeLevel) === 'all');

    if ($isAll) {
    // One query → 4 per-grade manual-like averages (equal weight across categories inside each grade)
    $sql = "
        SELECT
            -- manual-like avg per grade (equal weight across categories)
            ROUND(AVG(CASE WHEN t.YearLevel = 7 THEN t.CategoryAvg END), 1) AS G7,
            ROUND(AVG(CASE WHEN t.YearLevel = 8 THEN t.CategoryAvg END), 1) AS G8,
            ROUND(AVG(CASE WHEN t.YearLevel = 9 THEN t.CategoryAvg END), 1) AS G9,
            ROUND(AVG(CASE WHEN t.YearLevel = 10 THEN t.CategoryAvg END), 1) AS G10
        FROM (
            SELECT 
                ys.YearLevel,
                c.CatID,
                AVG(r.Score) AS CategoryAvg
            FROM evaluation e
            INNER JOIN evaluation_settings es ON es.ESetID = e.ESetID
            INNER JOIN questionnaire qa ON es.QID = qa.QID
            INNER JOIN result r ON e.EvalID = r.EvalID
            INNER JOIN question q ON r.QuesID = q.QuesID
            INNER JOIN category c ON q.CatID = c.CatID
            INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
            INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
            WHERE e.SchoolYearID = ?
              AND es.QID = ?
            GROUP BY ys.YearLevel, c.CatID
        ) AS t
    ";

    // If QID is numeric in your DB, bind as "ii", else "is"
    $qidIsInt = is_numeric($QID) && ctype_digit((string)$QID);
    $types  = $qidIsInt ? "ii" : "is";
    $params = [$schoolYearID, $QID];

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: ['G7'=>null,'G8'=>null,'G9'=>null,'G10'=>null];
    $stmt->close();

    // Build per-grade map (already rounded to 1 dp by SQL)
    $perGrade = [
        7  => $row['G7']  !== null ? (float)$row['G7']  : null,
        8  => $row['G8']  !== null ? (float)$row['G8']  : null,
        9  => $row['G9']  !== null ? (float)$row['G9']  : null,
        10 => $row['G10'] !== null ? (float)$row['G10'] : null,
    ];

    // Average only the grades that exist (non-null)
    $vals = array_values(array_filter($perGrade, fn($v) => $v !== null));
    $allAvg = count($vals) ? array_sum($vals) / count($vals) : null;

    $dataOut = [[
        'YearLevel' => 'All Grades (7–10)',
        'AvgScore'  => $allAvg !== null ? round($allAvg, 2) : null,
        'PerGrade'  => [
            'G7'  => $perGrade[7],
            'G8'  => $perGrade[8],
            'G9'  => $perGrade[9],
            'G10' => $perGrade[10],
        ],
    ]];
} else {
        // --- SPECIFIC GRADE: same logic you had (manual-like for that grade) ---
        $gradeAsInt = (int)$gradeLevel;

        $sql = "
            SELECT 
                ROUND(AVG(CategoryAvg), 1) AS ManualLikeAvg
            FROM (
                SELECT 
                    c.CatID,
                    AVG(r.Score) AS CategoryAvg
                FROM evaluation e
                INNER JOIN evaluation_settings es ON es.ESetID = e.ESetID
                INNER JOIN questionnaire qa ON es.QID = qa.QID
                INNER JOIN result r ON e.EvalID = r.EvalID
                INNER JOIN question q ON r.QuesID = q.QuesID
                INNER JOIN category c ON q.CatID = c.CatID
                INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
                INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
                WHERE e.SchoolYearID = ?
                  AND es.QID = ?
                  AND ys.YearLevel = ?
                GROUP BY c.CatID
            ) AS CatAverages
        ";

        $types  = $qidIsInt ? "iii" : "isi";
        $params = [$schoolYearID, $QID, $gradeAsInt];

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        $single = $row['ManualLikeAvg'] ?? null;
        $dataOut = [[
            'YearLevel' => $gradeAsInt,
            'AvgScore'  => $single !== null ? round((float)$single, 2) : null,
        ]];
       file_put_contents('log.txt', json_encode($dataOut) . "\n", FILE_APPEND);
    }


    echo json_encode([
        'status' => 'success',
        'data'   => $dataOut
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'getTrendAnalysisByGrade') {
    $gradeLevel = $data['GradeLevel'] ?? 'All';
    $evalSetID  = $data['ESetID'] ?? null;

    // Build the "manual-like" per-grade-per-year averages:
    //   INNER: AVG(r.Score) per SchoolYear × YearLevel × Category
    //   OUTER: AVG(CategoryAvg) per SchoolYear × YearLevel, then ROUND(…,1)
    $sql = "
        SELECT
            t.SchoolYear,
            t.YearLevel,
            ROUND(AVG(t.CategoryAvg), 1) AS GradeManualAvg
        FROM (
            SELECT 
                sy.SchoolYear,
                ys.YearLevel,
                c.CatID,
                AVG(r.Score) AS CategoryAvg
            FROM evaluation e
            INNER JOIN result r 
                ON e.EvalID = r.EvalID
            INNER JOIN enrollment enr 
                ON e.StudID = enr.StudID AND e.SchoolYearID = enr.SchoolYearID
            INNER JOIN year_section ys 
                ON enr.YearSecID = ys.YearSecID
            INNER JOIN schoolyear sy 
                ON e.SchoolYearID = sy.SchoolYearID
            INNER JOIN question q 
                ON r.QuesID = q.QuesID
            INNER JOIN category c 
                ON q.CatID = c.CatID
            WHERE 1=1
    ";

    $types = "";
    $params = [];

    if (!empty($evalSetID)) {
        $sql .= " AND e.ESetID = ? ";
        $types .= "i";
        $params[] = (int)$evalSetID;
    }

    if (strtolower($gradeLevel) !== 'all') {
        $sql .= " AND ys.YearLevel = ? ";
        $types .= "i";
        $params[] = (int)$gradeLevel;
    }

    $sql .= "
            GROUP BY sy.SchoolYear, ys.YearLevel, c.CatID
        ) AS t
        GROUP BY t.SchoolYear, t.YearLevel
        ORDER BY t.SchoolYear ASC, t.YearLevel ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    // Collect per-year, per-grade manual-like avgs (already rounded to 1dp by SQL)
    $perYear = []; // e.g., ['2024-2025' => [7=>3.1, 8=>3.3, ...]]
    while ($row = $res->fetch_assoc()) {
        $sy  = $row['SchoolYear'];
        $yl  = (int)$row['YearLevel'];
        $avg = ($row['GradeManualAvg'] !== null) ? (float)$row['GradeManualAvg'] : null;

        if (!isset($perYear[$sy])) $perYear[$sy] = [];
        $perYear[$sy][$yl] = $avg;
    }
    $stmt->close();

    $trendData = [];

    if (strtolower($gradeLevel) === 'all') {
        // Average across the grades that exist for that year (mean of the per-grade rounded values)
        foreach ($perYear as $sy => $gradeMap) {
            // Consider only 7–10, and only non-null grades for that year
            $vals = [];
            foreach ([7,8,9,10] as $g) {
                if (array_key_exists($g, $gradeMap) && $gradeMap[$g] !== null) {
                    $vals[] = (float)$gradeMap[$g]; // already 1dp from SQL
                }
            }
            if (count($vals) > 0) {
                $allAvg = array_sum($vals) / count($vals);
                $trendData[] = [
                    'SchoolYear' => $sy,
                    'AvgScore'   => round($allAvg, 2)
                ];
            } else {
                $trendData[] = [
                    'SchoolYear' => $sy,
                    'AvgScore'   => null
                ];
            }
        }
    } else {
        // Specific grade: just return that grade’s manual-like per-year average
        $gWanted = (int)$gradeLevel;
        foreach ($perYear as $sy => $gradeMap) {
            $val = $gradeMap[$gWanted] ?? null;  // already rounded(…,1)
            $trendData[] = [
                'SchoolYear' => $sy,
                'AvgScore'   => ($val !== null) ? round((float)$val, 2) : null
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'trend'  => $trendData
    ]);
    exit();
}

if (isset($data['action']) && $data['action'] === 'getEvaluationResponseCountByGrade') {
    $schoolYearID = $data['SchoolYearID'] ?? '';
    $gradeLevel   = $data['GradeLevel'] ?? 'All';
    $QID          = $data['QID'] ?? '';



    if (!$schoolYearID || !$QID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    // ✅ Count unique students who submitted evaluation
    $sql = "
        SELECT 
            ys.YearLevel, 
            COUNT(e.StudID) AS TotalResponses
        FROM evaluation e
        INNER JOIN evaluation_settings es ON es.ESetID = e.ESetID
        LEFT JOIN enrollment enr ON e.StudID = enr.StudID AND e.SchoolYearID = enr.SchoolYearID
        LEFT JOIN year_section ys ON enr.YearSecID = ys.YearSecID
        WHERE e.SchoolYearID = ?
          AND es.QID = ?
    ";

    // ➕ Filter for specific grade if not 'All'
    if (strtolower($gradeLevel) !== 'all') {
        $sql .= " AND ys.YearLevel = ? ";
    }

    $sql .= " GROUP BY ys.YearLevel ORDER BY ys.YearLevel ASC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit();
    }

    // Bind parameters (use "s" if SchoolYearID is string in DB)
    if (strtolower($gradeLevel) !== 'all') {
        $stmt->bind_param("sss", $schoolYearID, $QID, $gradeLevel);
    } else {
        $stmt->bind_param("ss", $schoolYearID, $QID);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'YearLevel' => $row['YearLevel'],
            'TotalResponses' => (int)$row['TotalResponses']
        ];
    }

    
    if (strtolower($gradeLevel) === 'all' && count($data) > 0) {
        $total = array_sum(array_column($data, 'TotalResponses'));
        $data = [[
            'YearLevel' => 'All Grades (7–10)',
            'TotalResponses' => $total
        ]];
    }

   

    echo json_encode([
        'status' => 'success',
        'responses' => $data
    ]);
    exit();
}
if (isset($data['action']) && $data['action'] === 'getEvaluationCategoryBreakdownByGrade') {
     file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
    $schoolYearID = (int)($data['SchoolYearID'] ?? 0);
    $gradeLevel = strtolower($data['GradeLevel'] ?? 'all');
    $QIDraw  = $data['QID'] ?? '';
    $qidIsInt = is_numeric($QIDraw) && ctype_digit((string)$QIDraw);
    $QID      = $qidIsInt ? (int)$QIDraw : (string)$QIDraw;

    if (!$schoolYearID || $QID === '') {
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
                AVG(r.Score) AS AvgScore -- keep unrounded here; round when returning
            FROM evaluation e
            INNER JOIN evaluation_settings es ON es.ESetID = e.ESetID
            INNER JOIN questionnaire qa ON es.QID = qa.QID
            INNER JOIN result r ON e.EvalID = r.EvalID
            INNER JOIN question q ON r.QuesID = q.QuesID
            INNER JOIN category c ON q.CatID = c.CatID
            INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
            INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
            WHERE e.SchoolYearID = ? 
              AND es.QID = ? 
              AND ys.YearLevel = ?
            GROUP BY c.CatID, c.CategoryName
            ORDER BY c.CatID ASC
        ";
        $types = $qidIsInt ? "iii" : "isi";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, $schoolYearID, $QID, $gradeLevel);
        $stmt->execute();
        $result = $stmt->get_result();

        $categories = [];
        $unrounded = [];
        while ($row = $result->fetch_assoc()) {
            $avg = (float)$row['AvgScore'];
            $categories[] = [
                'CatID' => $row['CatID'],
                'CategoryName' => $row['CategoryName'],
                'AvgScore' => round($avg, 1)
            ];
            $unrounded[] = $avg;
            file_put_contents('log.txt', json_encode($categories) . "\n", FILE_APPEND);
        }
        $stmt->close();

        // 🔹 Overall average for this grade = mean of per-category means (not raw AVG(r.Score))
        $sqlOverall = "
            SELECT ROUND(AVG(CategoryAvg), 1) AS OverallAvg
            FROM (
                SELECT c.CatID, AVG(r.Score) AS CategoryAvg
                FROM evaluation e
                INNER JOIN evaluation_settings es ON es.ESetID = e.ESetID
                INNER JOIN questionnaire qa ON es.QID = qa.QID
                INNER JOIN result r ON e.EvalID = r.EvalID
                INNER JOIN question q ON r.QuesID = q.QuesID
                INNER JOIN category c ON q.CatID = c.CatID
                INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
                INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
                WHERE e.SchoolYearID = ? AND es.QID = ? AND ys.YearLevel = ?
                GROUP BY c.CatID
            ) x
        ";
        $stmtOverall = $conn->prepare($sqlOverall);
        $typesOverall = $qidIsInt ? "iii" : "isi";
        $stmtOverall->bind_param($typesOverall, $schoolYearID, $QID, $gradeLevel);
        $stmtOverall->execute();
        $overallResult = $stmtOverall->get_result()->fetch_assoc();
        $overallAvg = (float)($overallResult['OverallAvg'] ?? 0);
        $stmtOverall->close();
    }

    // ============================================================
    // ✅ SCENARIO 2: ALL GRADES (7–10)
    // ============================================================
   else {
    $grades = [7, 8, 9, 10];
    $perGradeResults = [];
    $allCategories = [];

    // 🔹 Step 0: Get all categories
    $sqlAllCats = "
        SELECT DISTINCT c.CatID, c.CategoryName
        FROM evaluation e
        INNER JOIN evaluation_settings es ON es.ESetID = e.ESetID
        INNER JOIN questionnaire qa ON es.QID = qa.QID
        INNER JOIN result r ON e.EvalID = r.EvalID
        INNER JOIN question q ON r.QuesID = q.QuesID
        INNER JOIN category c ON q.CatID = c.CatID
        WHERE e.SchoolYearID = ? AND es.QID = ?
    ";
    $stmtAllCats = $conn->prepare($sqlAllCats);
    $typesAllCats = $qidIsInt ? "ii" : "is";
    $stmtAllCats->bind_param($typesAllCats, $schoolYearID, $QID);
    $stmtAllCats->execute();
    $resAllCats = $stmtAllCats->get_result();
    while ($row = $resAllCats->fetch_assoc()) {
        $allCategories[(int)$row['CatID']] = $row['CategoryName'];
    }
    $stmtAllCats->close();

    // 🔹 Step 1: Loop over each grade level (7–10) and collect SUM / COUNT
    foreach ($grades as $grade) {
        $sql = "
            SELECT 
                c.CatID,
                SUM(r.Score) AS SumScore,
                COUNT(r.Score) AS RespCount
            FROM evaluation e
            INNER JOIN evaluation_settings es ON es.ESetID = e.ESetID
            INNER JOIN questionnaire qa ON es.QID = qa.QID
            INNER JOIN result r ON e.EvalID = r.EvalID
            INNER JOIN question q ON r.QuesID = q.QuesID
            INNER JOIN category c ON q.CatID = c.CatID
            INNER JOIN enrollment enr ON enr.StudID = e.StudID AND enr.SchoolYearID = e.SchoolYearID
            INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
            WHERE e.SchoolYearID = ? AND es.QID = ? AND ys.YearLevel = ?
            GROUP BY c.CatID
        ";
        $typesLoop = $qidIsInt ? "iii" : "isi";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typesLoop, $schoolYearID, $QID, $grade);
        $stmt->execute();
        $res = $stmt->get_result();

        $categoriesThisGrade = [];
        while ($row = $res->fetch_assoc()) {
            $catID = (int)$row['CatID'];
            $categoriesThisGrade[$catID] = [
                'sum'   => (float)$row['SumScore'],
                'count' => (int)$row['RespCount']
            ];
        }
        $stmt->close();

        // Store per-grade data aligned to the full category list
        foreach ($allCategories as $catID => $catName) {
            if (!isset($perGradeResults[$catID])) {
                $perGradeResults[$catID] = [
                    'CategoryName' => $catName,
                    'grades' => [] // ex: [7 => ['sum'=>..,'count'=>..], 8=>...]
                ];
            }
            $perGradeResults[$catID]['grades'][$grade] = $categoriesThisGrade[$catID] ?? ['sum' => 0, 'count' => 0];
        }
    }

    // ✅ Compute category Avgs as mean of the PER-GRADE (UNROUNDED) avgs; round only at the end
    $categories = [];
    $finalCatAvgs = [];

    ksort($perGradeResults, SORT_NUMERIC);

    foreach ($perGradeResults as $catID => $info) {
        $perGradeAvgs = [];

        foreach ($grades as $g) {
            $vals = $info['grades'][$g] ?? ['sum'=>0,'count'=>0];
            if (!empty($vals['count'])) {
                // keep unrounded per-grade category avg
                $perGradeAvgs[] = $vals['sum'] / $vals['count'];
            }
        }

        // average only grades that actually had responses
        $finalCatAvg = count($perGradeAvgs) > 0
            ? array_sum($perGradeAvgs) / count($perGradeAvgs)
            : null;

        $categories[] = [
            'CatID' => $catID,
            'CategoryName' => $info['CategoryName'],
            'AvgScore' => $finalCatAvg === null ? null : round($finalCatAvg, 1)
        ];

        if ($finalCatAvg !== null) {
            $finalCatAvgs[] = $finalCatAvg;
        }
         file_put_contents('log.txt', json_encode($categories) . "\n", FILE_APPEND);
    }

    // ✅ Overall “All” average = mean of the final category means (unrounded), rounded once
    $overallAvg = count($finalCatAvgs) > 0
        ? round(array_sum($finalCatAvgs) / count($finalCatAvgs), 1)
        : 0.0;
}

     file_put_contents('log.txt', json_encode($overallAvg) . "\n", FILE_APPEND);
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
    $QID  = $data['QID'] ?? '';

    if (!$schoolYearID || !$QID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit();
    }

    $sql = "
        SELECT 
            ys.YearLevel AS GradeLevel,
            COUNT(DISTINCT e.StudID) AS SubmissionCount
        FROM evaluation e
        inner join evaluation_settings es on es.ESetID = e.ESetID
        inner join questionnaire qa on es.QID = qa.QID
        INNER JOIN enrollment enr ON e.StudID = enr.StudID AND e.SchoolYearID = enr.SchoolYearID
        INNER JOIN year_section ys ON ys.YearSecID = enr.YearSecID
        WHERE e.SchoolYearID = ? 
          AND es.QID = ?
        GROUP BY ys.YearLevel
        ORDER BY ys.YearLevel ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $schoolYearID, $QID);
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

if (isset($data['action']) && $data['action'] === 'count_students_by_grade') {
    
    $schoolYearID = $data['SchoolYearID'] ?? '';;  

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

}

?>