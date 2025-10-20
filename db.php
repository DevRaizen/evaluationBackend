<?php
$host = "localhost";
$user = "root";  
$password = "";
$database = "tval_db";  

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$today = date('Y-m-d H:i:s'); // current datetime
$updateSql = "UPDATE evaluation_settings
              SET status = 'Inactive'
              WHERE status = 'Active' AND endDate < ?";
$stmt = $pdo->prepare($updateSql);
$stmt->execute([$today]);


$updatedRows = $stmt->rowCount();

/*
$query = "SELECT AccID, Password FROM User_Account";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $accID = $row['AccID'];
    $plainPassword = $row['Password'];

    // Skip if already hashed (just a basic check — bcrypt hashes start with $2y$ or $2a$)
    if (strpos($plainPassword, '$2y$') === 0 || strpos($plainPassword, '$2a$') === 0) {
        continue;
    }

    // 2. Hash the password
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

    // 3. Update the hashed password in the DB
    $update = $conn->prepare("UPDATE User_Account SET Password = ? WHERE AccID = ?");
    $update->bind_param("si", $hashedPassword, $accID);
    $update->execute();
}

echo "Password migration completed.";
*/

?>
