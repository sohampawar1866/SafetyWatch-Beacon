<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

// Log access to this script
error_log("process_distress.php was accessed");

// Process only if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST request received");
    
    // Debug: Log all POST data
    error_log("POST data: " . print_r($_POST, true));
    
    // Check if required fields are set
    if (!isset($_POST['name']) || !isset($_POST['location']) || !isset($_POST['message']) || !isset($_POST['severity'])) {
        error_log("Missing required fields");
        echo "Missing required form fields";
        exit;
    }
    
    try {
        // Get form data and sanitize
        $name = $conn->real_escape_string($_POST['name']);
        $contact = isset($_POST['contact']) ? $conn->real_escape_string($_POST['contact']) : '';
        $location = $conn->real_escape_string($_POST['location']);
        $messageType = isset($_POST['type']) ? $conn->real_escape_string($_POST['type']) : 'Other';
        $severity = $conn->real_escape_string($_POST['severity']);
        $message = $conn->real_escape_string($_POST['message']);
        
        error_log("Sanitized data: name=$name, location=$location, type=$messageType, severity=$severity");
        
        // Insert data into the distress_messages table
        $sql = "INSERT INTO distress_messages (name, contact, location, message_type, severity, message) 
                VALUES ('$name', '$contact', '$location', '$messageType', '$severity', '$message')";
        
        error_log("Executing SQL: $sql");
        
        if ($conn->query($sql) === TRUE) {
            error_log("SQL executed successfully, inserted ID: " . $conn->insert_id);
            
            // Set a session variable to indicate success for displaying on the index page
            session_start();
            $_SESSION['distress_submitted'] = true;
            $_SESSION['distress_message'] = "Your distress message has been submitted. Help is on the way.";
            
            // Redirect back to index.php
            header("Location: ../index.php#distress-section");
            exit;
        } else {
            error_log("SQL Error: " . $conn->error);
            
            // Set error message
            session_start();
            $_SESSION['distress_error'] = "Error: " . $conn->error;
            
            // Redirect back to index.php
            header("Location: ../index.php#distress-section");
            exit;
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        
        // Set error message
        session_start();
        $_SESSION['distress_error'] = "An error occurred: " . $e->getMessage();
        
        // Redirect back to index.php
        header("Location: ../index.php#distress-section");
        exit;
    }
} else {
    error_log("Not a POST request");
    // If accessed directly, redirect to the homepage
    header("Location: ../index.php");
    exit;
}
?>