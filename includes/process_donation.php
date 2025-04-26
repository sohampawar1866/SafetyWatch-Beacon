<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

// Log access to this script
error_log("process_donation.php was accessed");

// Process only if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Donation POST request received");
    
    // Debug: Log all POST data
    error_log("POST data: " . print_r($_POST, true));
    
    // Check if required fields are set
    if (!isset($_POST['fullName']) || !isset($_POST['email']) || !isset($_POST['amount']) || !isset($_POST['crisisType']) || !isset($_POST['paymentMethod'])) {
        error_log("Missing required fields for donation");
        session_start();
        $_SESSION['donation_error'] = "Please fill in all required fields.";
        header("Location: ../pages/donate.php#donation-form");
        exit;
    }
    
    try {
        // Get form data and sanitize
        $fullName = $conn->real_escape_string($_POST['fullName']);
        $email = $conn->real_escape_string($_POST['email']);
        $amount = $conn->real_escape_string($_POST['amount']);
        $crisisType = $conn->real_escape_string($_POST['crisisType']);
        $paymentMethod = $conn->real_escape_string($_POST['paymentMethod']);
        $message = isset($_POST['message']) ? $conn->real_escape_string($_POST['message']) : '';
        
        // Generate a transaction ID
        $transactionId = 'TXN' . time() . rand(1000, 9999);
        
        error_log("Donation data: name=$fullName, email=$email, amount=$amount, crisis=$crisisType, payment=$paymentMethod");
        
        // Insert data into the donations table
        $sql = "INSERT INTO donations (full_name, email, amount, crisis_type, payment_method, message, transaction_id, status) 
                VALUES ('$fullName', '$email', '$amount', '$crisisType', '$paymentMethod', '$message', '$transactionId', 'pending')";
        
        error_log("Executing SQL: $sql");
        
        if ($conn->query($sql) === TRUE) {
            error_log("Donation submitted successfully, inserted ID: " . $conn->insert_id);
            
            // Set a session variable to indicate success for displaying on the donate page
            session_start();
            $_SESSION['donation_success'] = true;
            $_SESSION['donation_message'] = "Thank you for your donation of $" . number_format($amount, 2) . " to the $crisisType Relief Fund. Your transaction ID is $transactionId.";
            $_SESSION['transaction_id'] = $transactionId;
            
            // Redirect back to donate page
            header("Location: ../pages/donate.php#donation-success");
            exit;
        } else {
            error_log("SQL Error: " . $conn->error);
            
            // Set error message
            session_start();
            $_SESSION['donation_error'] = "Error processing your donation. Please try again.";
            
            // Redirect back to donate.php
            header("Location: ../pages/donate.php#donation-form");
            exit;
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        
        // Set error message
        session_start();
        $_SESSION['donation_error'] = "An error occurred: " . $e->getMessage();
        
        // Redirect back to donate.php
        header("Location: ../pages/donate.php#donation-form");
        exit;
    }
} else {
    error_log("Not a POST request to process_donation");
    // If accessed directly, redirect to the homepage
    header("Location: ../pages/donate.php");
    exit;
}
?>