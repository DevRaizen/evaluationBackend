<?php
$host = getenv("MYSQLHOST") ?: "localhost";
$user = getenv("MYSQLUSER") ?: "root";
$password = getenv("MYSQLPASSWORD") ?: "";
$database = getenv("MYSQLDATABASE") ?: "tval_db";
$port = getenv("MYSQLPORT") ?: 3306;

// MySQLi connection
$conn = new mysqli($host, $user, $password, $database, (int)$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PDO connection
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');


$result = $conn->query("SHOW TABLES");

$tables = [];

while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo json_encode($tables);

exit;

 // current datetime
$updateSql = "UPDATE evaluation_settings
              SET status = 'Inactive'
              WHERE status = 'Active' AND endDate <= ?";
$stmt = $pdo->prepare($updateSql);
$stmt->execute([$today]);


$updatedRows = $stmt->rowCount();

// 2️ Activate those within valid date range
$updateActiveSql = "
    UPDATE evaluation_settings
    SET status = 'Active' 
    WHERE startDate <= ? And endDate > ?
";
$stmtActive = $pdo->prepare($updateActiveSql);
$stmtActive->execute([$today, $today]);
$activeRows = $stmtActive->rowCount();


/*
$backupFile = __DIR__ . "/tval_backup.sql";

// Full path to mysqldump, escaped for Windows
$mysqldumpPath = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

// Build command
if (!empty($password)) {
    $command = "\"$mysqldumpPath\" -h$host -u$user -p$password $database > \"$backupFile\"";
} else {
    $command = "\"$mysqldumpPath\" -h$host -u$user $database > \"$backupFile\"";
}

// Execute backup
exec($command . " 2>&1", $output, $result);

if ($result === 0) {
    echo "✅ Backup successful: $backupFile\n";
} else {
    echo "❌ Backup failed. Output:\n";
    echo implode("\n", $output);
}
// ------------------------------------------------------------------

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
