<?php

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

function getMonthlyData($account_id, $fy_year, $pdo) {
    try {
        // Prepare SQL statement to select all labels
        $stmt = $pdo->query( "SELECT 
        YEAR(date) AS year,
        MONTH(date) AS month,
        MAX(date) AS last_date,
        bal AS last_balance
    FROM 
        transactionDetails
    WHERE 
        account_id = $account_id
        AND (YEAR(date) = $fy_year OR YEAR(date) = $fy_year - 1)
        AND (MONTH(date) >= 4 OR YEAR(date) = $fy_year)
        AND (MONTH(date) <= 3 OR YEAR(date) = $fy_year - 1)
    GROUP BY 
        YEAR(date),
        MONTH(date)
    ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    } catch (PDOException $e) {
        // If an error occurs, handle it here
        return false;
    }
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
        $result = getMonthlyData($provided_account_id, (int) $_POST['fyYear'],$pdo);
        print json_encode($result);
    } else {
         // Authentication failed
         header('HTTP/1.0 401 Unauthorized');
         echo "Error: Authentication failed. Please check your credentials.";
    } 
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}