<?php
// Database connection parameters
$hostname = "localhost"; // Use localhost for XAMPP
$username = "root";      // Default XAMPP username
$password = "";          // Default XAMPP password is empty
$database = "code_white";

// Create database connection with error handling
try {
    // Create database connection
    $conn = new mysqli($hostname, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        // Log error but don't expose details
        error_log("Database connection failed: " . $conn->connect_error);
        // Instead show a generic message
        throw new Exception("Database connection error. Please try again later.");
    }

    // Set character set
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Log the error
    error_log("Database error: " . $e->getMessage());
    
    // If this is accessed directly, provide a user-friendly message
    if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
        echo "Database connection error. Please try again later.";
        exit;
    }
    
    // Otherwise, make the error available through $db_error
    $db_error = $e->getMessage();
}
?>