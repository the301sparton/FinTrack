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

// Function to import CSV data into transactionDetails table
function importCSVData($csvFilePath, $account_id, $pdo) {
    // You need to replace 'your_db_table' with your actual table name
    $query = "INSERT INTO transactionDetails (date, chq_no, particulars, dr, cr, bal, sol, account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Use prepared statements to prevent SQL injection
    $stmt = $pdo->prepare($query);
    
    if (($handle = fopen($csvFilePath, "r")) !== false) {
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // Assuming the CSV columns are in the same order as in the database table
            $stmt->execute([date('Y-m-d', strtotime($data[0])), $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $account_id]);
        }
        fclose($handle);
        
        echo "CSV data imported successfully!";
    } else {
        echo "Error: Unable to open the CSV file.";
    }
}


// Check if a file is uploaded and Basic Authentication credentials are provided
if ($_FILES && $_FILES['csvFile']['error'] == UPLOAD_ERR_OK) {
    $tmpFilePath = $_FILES['csvFile']['tmp_name'];

    // Get Basic Authentication credentials
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
            // File moved successfully, proceed with CSV import
            importCSVData($tmpFilePath,$provided_account_id, $pdo);
        } else {
            // Authentication failed
            echo "Error: Authentication failed. Please check your credentials.";
        }
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }

} else {
    echo "Error: No file uploaded or an error occurred during the upload.";
}
?>