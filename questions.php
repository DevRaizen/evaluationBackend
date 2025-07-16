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
        SELECT q.questionText, q.type, q.catID, c.categoryName 
        FROM questionnaire qa
        JOIN question q ON qa.QuesID = q.QuesID
        JOIN category c ON q.catID = c.catID
        WHERE qa.QID = ?
    ");
    $stmt->bind_param("i", $qid);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }

    echo json_encode($questions);
}

if ($action === 'getAllQIDs') {
    $stmt = $conn->prepare("SELECT DISTINCT QID FROM questionnaire");
    $stmt->execute();
    $result = $stmt->get_result();

    $qids = [];
    while ($row = $result->fetch_assoc()) {
        $qids[] = $row['QID'];
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

    echo json_encode(['status' => 'success', 'inserted' => $inserted]);
}


if ($action === 'addSingleQuestion') {
    $qid = $data['QID'];
    $question = $data['question'];
    $text = trim($question['text']);
    $type = $question['type'];
    $categoryName = $question['category'];

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
    $stmt = $conn->prepare("SELECT * FROM questionnaire WHERE QID = ? AND QuesID = ?");
    $stmt->bind_param("ii", $qid, $quesID);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO questionnaire (QID, QuesID) VALUES (?, ?)");
        $stmt->bind_param("ii", $qid, $quesID);
        $stmt->execute();
    }

    echo json_encode(['status' => 'success', 'QID' => $qid, 'QuesID' => $quesID]);
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


?>
