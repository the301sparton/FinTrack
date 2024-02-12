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
function getAccountData($account_id, $pdo) {
    $result = array();

    // Retrieve account details
    $query = "SELECT * FROM accountDetails WHERE account_id = :account_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':account_id' => $account_id));
    $account_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($account_row) {
        // Add account details to the result array
        $result = $account_row;

        // Retrieve transactions for the account
        $query = "SELECT * FROM transactionDetails WHERE account_id = :account_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':account_id' => $account_id));
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($transactions) {
            foreach ($transactions as &$transaction) {
                $transaction_id = $transaction['transaction_id'];

                // Retrieve labels for each transaction
                $labels_query = "SELECT labelType.label_id, label_name, label_color 
                                FROM labelDetails 
                                JOIN labelType ON labelDetails.label_id = labelType.label_id 
                                WHERE transaction_id = :transaction_id";
                $stmt = $pdo->prepare($labels_query);
                $stmt->execute(array(':transaction_id' => $transaction_id));
                $labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $transaction['labels'] = $labels;
            }

            // Add transactions to the result array
            $result['transactions'] = $transactions;
        }
    }

    return $result;
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
        header('HTTP/1.0 401 Unauthorized');
        echo "Error: Authentication failed. Please check your credentials.";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}