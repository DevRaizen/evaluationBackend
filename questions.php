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
$action = $data['action'] ?? '';  // Safe fallback if `action` is missing



if ($action === 'getQuestionsByQID') {
    $qid = $data['QID'];

    $stmt = $conn->prepare("
        SELECT q.questionText, q.QuesID, q.type, q.catID, c.categoryName 
        FROM questionnaire qa
        JOIN question q ON qa.QuesID = q.QuesID
        JOIN category c ON q.catID = c.catID
        WHERE qa.QID = ? and q.Status = 'Active'
    ");
    $stmt->bind_param("i", $qid);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }

    echo json_encode([
    'status' => 'success',
    'questions' => $questions   
]);
}

if ($action === 'getAllQIDs') {
    $stmt = $conn->prepare("SELECT DISTINCT QID,QTitle FROM questionnaire  ");
    $stmt->execute();
    $result = $stmt->get_result();

    $qids = [];
    while ($row = $result->fetch_assoc()) {
        $qids[] = [
            'QID' => $row['QID'],
            'QTitle' => $row['QTitle'],

        ];
    }

    echo json_encode($qids);
}

if ($action === 'getAllCategories') {
    $stmt = $conn->prepare("SELECT * FROM category");
    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    echo json_encode($categories);
}

if ($action === 'saveQuestionnaire') {
    $qid = $data['QID'];
    $questions = $data['questions']; // structured array: [{category, list: [{text, type}]}]
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';
    $inserted = 0;

    foreach ($questions as $group) {
        $categoryName = $group['category'];
        
        // Get or create category
        $stmt = $conn->prepare("SELECT catID FROM category WHERE categoryName = ?");
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $catID = $row['catID'];
        } else {
            $stmt = $conn->prepare("INSERT INTO category (categoryName) VALUES (?)");
            $stmt->bind_param("s", $categoryName);
            $stmt->execute();
            $catID = $stmt->insert_id;
        }

        foreach ($group['list'] as $q) {
            $questionText = trim($q['text']);
            $questionType = $q['type'];

            // Check if this exact question already exists
            $stmt = $conn->prepare("SELECT QuesID FROM question WHERE questionText = ? AND type = ? AND catID = ?");
            $stmt->bind_param("ssi", $questionText, $questionType, $catID);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                $quesID = $row['QuesID']; // Reuse existing
            } else {
                // Insert new question
                $stmt = $conn->prepare("INSERT INTO question (questionText, type, catID) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $questionText, $questionType, $catID);
                $stmt->execute();
                $quesID = $stmt->insert_id;
            }

            // Now insert into questionnaire table
            $stmt = $conn->prepare("INSERT INTO questionnaire (QID, QuesID) VALUES (?, ?)");
            $stmt->bind_param("ii", $qid, $quesID);
            $stmt->execute();
            $inserted++;
        }
    }
        $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Add New Questionnaire', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
    echo json_encode(['status' => 'success', 'inserted' => $inserted]);
}


if ($action === 'addSingleQuestion') {
    $qid = $data['QID'];
    $question = $data['question'];
    $text = trim($question['text']);
    $type = $question['type'];
    $categoryName = $question['category'];
    $adminName = $data['Admin'] ?? '';

    // 1. Get or create category
    $stmt = $conn->prepare("SELECT catID FROM category WHERE categoryName = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $catID = $row['catID'];
    } else {
        $stmt = $conn->prepare("INSERT INTO category (categoryName) VALUES (?)");
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $catID = $stmt->insert_id;
    }

    // 2. Check if question already exists
    $stmt = $conn->prepare("SELECT QuesID FROM question WHERE questionText = ? AND type = ?");
    $stmt->bind_param("ss", $text, $type);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $quesID = $row['QuesID'];
    } else {
        // Insert new question
        $stmt = $conn->prepare("INSERT INTO question (questionText, type, catID) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $text, $type, $catID);
        $stmt->execute();
        $quesID = $stmt->insert_id;
    }

    // 3. Insert into questionnaire if not exists
    $stmt = $conn->prepare("SELECT QTitle FROM questionnaire WHERE QID = ?");
    $stmt->bind_param("i", $qid);
    $stmt->execute();
    $res = $stmt->get_result();
    $qTitle = '';

    if ($row = $res->fetch_assoc()) {
        $qTitle = $row['QTitle'];
    }
    $stmt->close();

    // Check if this question already exists in the questionnaire table
    $stmt = $conn->prepare("SELECT 1 FROM questionnaire WHERE QID = ? AND QuesID = ?");
    $stmt->bind_param("ii", $qid, $quesID);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO questionnaire (QID, QTitle, QuesID) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $qid, $qTitle, $quesID);
        $stmt->execute();

         $stmtLog = $conn->prepare("INSERT INTO logs (Name, Activity, TimeStamp) VALUES (?, 'Added Singe Question to Questionnaire', NOW())");
        $stmtLog->bind_param("s", $adminName);
        $stmtLog->execute();
        $stmtLog->close();
    }

    echo json_encode(['status' => 'success', 'QID' => $qid, 'QuesID' => $quesID]);
}

if (isset($data['action']) && $data['action'] === 'deleteQuestion') {
    $quesID = $data['QuesID'] ?? null;

    if (!$quesID) {
        echo json_encode(['status' => 'error', 'message' => 'Missing QuestionID']);
        exit();
    }

    // 🧠 Update question status to inactive instead of deleting
    $sql = "UPDATE question SET Status = 'Inactive' WHERE QuesID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'SQL prepare failed: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $quesID);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Question set to Inactive']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update question']);
    }

    $stmt->close();
    exit();
}

if ($action === 'updateQuestionText') {
    $quesID = $data['QuesID'] ?? null;
    $newText = trim($data['questionText'] ?? '');

    if (!$quesID || $newText === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing or invalid input']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE question SET questionText = ? WHERE QuesID = ?");
    $stmt->bind_param("si", $newText, $quesID);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Question updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update question']);
    }

    $stmt->close();
    exit();
}



if ($action === 'addCategory') {
    $categoryName = trim($data['categoryName']);

    if ($categoryName !== '') {
        // Check if category already exists
        $stmt = $conn->prepare("SELECT catID FROM category WHERE categoryName = ?");
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            echo json_encode(['status' => 'exists']);
        } else {
            $stmt = $conn->prepare("INSERT INTO category (categoryName) VALUES (?)");
            $stmt->bind_param("s", $categoryName);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'catID' => $stmt->insert_id]);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    } else {
        echo json_encode(['status' => 'invalid']);
    }
}

if ($action === 'deleteCategory') {
    $catID = $data['catID'];

    // Optional: Check if category is used before deletion (prevent orphaned questions)
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM question WHERE catID = ?");
    $checkStmt->bind_param("i", $catID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Category in use, cannot delete.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM category WHERE catID = ?");
    $stmt->bind_param("i", $catID);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'deleted' => true]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed.']);
    }
}

if ($action === 'updateCategory') {
    $catID = $data['catID'];
    $categoryName = trim($data['categoryName']);

    if ($catID && $categoryName !== '') {
        $stmt = $conn->prepare("UPDATE category SET categoryName = ? WHERE catID = ?");
        $stmt->bind_param("si", $categoryName, $catID);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
}



if ($action === 'updateQuestionaireCategory') {
    $qid = $data['QID'];
    $categoryName = trim($data['categoryName']);

    if ($qid && $categoryName !== '') {
        $stmt = $conn->prepare("SELECT catID FROM category WHERE categoryName = ?");
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $catID = $row['catID'];

            $stmt = $conn->prepare("UPDATE questionnaire SET catID = ? WHERE QID = ?");
            $stmt->bind_param("ii", $catID, $qid);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Update failed']);
            }
        } else {
            // Di makita 
            echo json_encode(['status' => 'error', 'message' => 'Category not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
}

// student part

if ($action === 'getStudentQuestionsByQID') {
    $qid = $data['QID'];
    $stmt = $conn->prepare("
        SELECT q.QuesID, q.questionText, q.type, c.categoryName, c.catID
        FROM questionnaire qa
        JOIN question q ON qa.QuesID = q.QuesID
        JOIN category c ON q.catID = c.catID
        WHERE qa.QID = ? and q.Status = 'Active'
        ORDER BY  q.QuesID 
    "); 
    $stmt->bind_param("i",$qid);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }

    echo json_encode([
    'status' => 'success',
    'questions' => $questions
]);
}

if ($action === 'saveNewQuestionnaire') {
    file_put_contents('log.txt', json_encode($data) . "\n", FILE_APPEND);
    $title = trim($data['title']);
    $questions = $data['questions'];
    $adminName = $data['Admin'] ?? '';
    $accID = $data['AccID'] ?? '';
    if (empty($title) || empty($questions)) {
        echo json_encode(['status' => 'error', 'message' => 'Title or questions cannot be empty']);
        exit;
    }

    // 1️ Generate new QID manually
    $result = $conn->query("SELECT MAX(QID) AS max_id FROM questionnaire");
    $row = $result->fetch_assoc();
    $newQID = ($row['max_id'] ?? 0) + 1;

    // 2 Loop through each question and handle insertions
    foreach ($questions as $question) {
        $text = trim($question['text']);
        $type = $question['type'];
        $categoryName = $question['category'];

        // 🔹 Get or create category
        $stmt = $conn->prepare("SELECT catID FROM category WHERE categoryName = ?");
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $catID = $row['catID'];
        } else {
            $stmt = $conn->prepare("INSERT INTO category (categoryName) VALUES (?)");
            $stmt->bind_param("s", $categoryName);
            $stmt->execute();
            $catID = $stmt->insert_id;
            $stmt->close();
        }

        // 🔹 Check if the question already exists
        $stmt = $conn->prepare("SELECT QuesID FROM question WHERE questionText = ? AND type = ? AND catID = ?");
        $stmt->bind_param("ssi", $text, $type, $catID);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $quesID = $row['QuesID']; // Use existing question
        } else {
            // Insert new question manually generating ID
            $status = 'Active';
            $stmt = $conn->prepare("SELECT MAX(QuesID) AS max_qid FROM question");
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $newQuesID = ($row['max_qid'] ?? 0) + 1;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO question (QuesID, questionText, type, Status, catID) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $newQuesID, $text, $type, $status, $catID);
            $stmt->execute();
            $quesID = $newQuesID;
            $stmt->close();
        }

        
        $stmt = $conn->prepare("INSERT INTO questionnaire (QID, QTitle, QuesID) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $newQID, $title, $quesID);
        $stmt->execute();
        $stmt->close();
    }
    $stmtLog = $conn->prepare("INSERT INTO logs (Name, AccID, Activity, TimeStamp) VALUES (?, ?, 'Created New Questionnaire', NOW())");
        $stmtLog->bind_param("si", $adminName,$accID);
        $stmtLog->execute();
        $stmtLog->close();
    echo json_encode(['status' => 'success', 'QID' => $newQID]);
}

if (isset($data['action']) && $data['action'] === 'getExistingQuestions') {
    try {
        // Fetch all questions from existing questionnaires
        $sql = "SELECT q.QuesID, q.QuestionText, q.Type, c.categoryName
                FROM question q
                INNER JOIN category c ON c.CatID = q.CatID
                ORDER BY c.categoryName, q.QuestionText ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $questions[] = [
                'id' => $row['QuesID'],
                'text' => $row['QuestionText'],
                'type' => $row['Type'],
                'category' => $row['categoryName']
            ];
        }

        if (count($questions) > 0) {
            echo json_encode([
                'status' => 'success',
                'questions' => $questions
            ]);
        } else {
            echo json_encode([
                'status' => 'empty',
                'message' => 'No existing questions found.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error fetching existing questions: ' . $e->getMessage()
        ]);
    }
}

?>
