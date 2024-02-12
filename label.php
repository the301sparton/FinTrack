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

// Function to delete label entry from labelDetails
function deleteLabelEntry($label_id, $transaction_id, $pdo)
{
    try {
        // Prepare SQL statement to delete entry
        $stmt = $pdo->prepare("DELETE FROM labelDetails WHERE label_id = :label_id AND transaction_id = :transaction_id");
        $stmt->execute(array(':label_id' => $label_id, ':transaction_id' => $transaction_id));
        return true;
    } catch (PDOException $e) {
        // If an error occurs during deletion, handle it here
        return false;
    }
}

function insertLabelEntry($label_id, $transaction_id, $pdo)
{
    try {
        // Prepare SQL statement to insert entry
        $stmt = $pdo->prepare("INSERT INTO labelDetails (transaction_id, label_id) VALUES (:transaction_id, :label_id)");
        $stmt->execute(array(':transaction_id' => $transaction_id, ':label_id' => $label_id));
        return true;
    } catch (PDOException $e) {
        // If an error occurs during insertion, handle it here
        return false;
    }
}

$provided_account_id = $_SERVER['PHP_AUTH_USER'];
$provided_password = $_SERVER['PHP_AUTH_PW'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $result = [];
    // Authenticate the user
    if (authenticateUser($provided_account_id, $provided_password, $pdo)) {
        $type = $_POST['type'];
        $label_id = $_POST['label_id'] ?? null;
        $transaction_id = $_POST['transaction_id'] ?? null;

        if ($label_id && $transaction_id) {
            if ($type == "deleteLabelForTransaction") {
                // Delete label entry
                $success = deleteLabelEntry($label_id, $transaction_id, $pdo);

                if ($success) {
                    $result['success'] = true;
                    $result["message"] = "Entry deleted successfully.";
                } else {
                    $result['success'] = false;
                    $result["message"] = "Failed to delete entry.";
                }
            } else if ($type == "addLabelToTransaction") {
                // Insert label entry
                $success = insertLabelEntry($label_id, $transaction_id, $pdo);

                if ($success) {
                    $result['success'] = true;
                    $result["message"] = "Entry created successfully.";
                } else {
                    $result['success'] = false;
                    $result["message"] = "Failed to created entry.";
                }
            }
        } else {
            $result['success'] = false;
            $result["message"] = "Both label_id and transaction_id are required.";
        }
        // Output JSON response
        header('Content-Type: application/json');
    } else {
        // Authentication failed
        header('HTTP/1.0 401 Unauthorized');
        $result['success'] = false;
        $result["message"] = "Error: Authentication failed. Please check your credentials.";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

print json_encode($result);