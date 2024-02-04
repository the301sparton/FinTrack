<?php
// Assuming you have a database connection established

// Function to authenticate the user based on account_id and password hash

$host = 'localhost';
$dbname = 'FinTrack';
$username = 'root';
$password = '';

function authenticateUser($account_id, $password, $pdo)
{
    // You need to replace 'your_db_table' and 'password_hash_column' with your actual table and column names
    $query = "SELECT * FROM accountDetails WHERE account_id = :account_id AND pass = :password";

    // Use prepared statements to prevent SQL injection
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->execute();

    // If a matching user is found, return true; otherwise, return false
    return $stmt->rowCount() > 0;
}

// Function to retrieve account data and transaction details with labels
function getAccountData($account_id, $pdo)
{
    // You need to replace 'your_db_table' with your actual table name
    $accountQuery = "SELECT * FROM accountDetails WHERE account_id = :account_id";
    $transactionQuery = "SELECT t.*, GROUP_CONCAT(lt.label_name) AS labels
                        FROM transactionDetails t
                        LEFT JOIN labelDetails ld ON t.transaction_id = ld.transaction_id
                        LEFT JOIN labelType lt ON ld.label_id = lt.label_id
                        WHERE t.account_id = :account_id
                        GROUP BY t.transaction_id";

    // Use prepared statements to prevent SQL injection
    $accountStmt = $pdo->prepare($accountQuery);
    $accountStmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
    $accountStmt->execute();

    $transactionStmt = $pdo->prepare($transactionQuery);
    $transactionStmt->bindParam(':account_id', $account_id, PDO::PARAM_INT);
    $transactionStmt->execute();

    // Fetch account data
    $accountData = $accountStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch transaction details with labels
    $transactionData = $transactionStmt->fetchAll(PDO::FETCH_ASSOC);
    $accountData["transactionData"] = $transactionData;
    return $accountData;
}

// Check if Basic Authentication credentials are provided
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication Required';
    exit;
}

$provided_account_id = $_SERVER['PHP_AUTH_USER'];
$provided_password = $_SERVER['PHP_AUTH_PW'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Authenticate the user
    if (authenticateUser($provided_account_id, $provided_password, $pdo)) {
        // Get account data and transaction details
        $result = getAccountData($provided_account_id, $pdo);

        // Output JSON response
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        // Authentication failed
        echo "Error: Authentication failed. Please check your credentials.";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

?>