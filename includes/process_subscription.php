<?php
// Include database connection
require_once 'db_connect.php';

// Process only if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email and sanitize
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email already exists
    $checkSql = "SELECT * FROM subscribers WHERE email = '$email'";
    $checkResult = $conn->query($checkSql);
    
    $response = array();
    
    if ($checkResult->num_rows > 0) {
        // Email already exists
        $response['success'] = false;
        $response['message'] = "This email is already subscribed.";
    } else {
        // Insert email into subscribers table
        $sql = "INSERT INTO subscribers (email) VALUES ('$email')";
        
        if ($conn->query($sql) === TRUE) {
            // Start session for success message
            session_start();
            $_SESSION['subscription_success'] = true;
            $_SESSION['subscription_message'] = "Thank you for subscribing to our emergency alerts! You will now receive important notifications about crisis situations.";
            
            $response['success'] = true;
            $response['message'] = "Thank you for subscribing to our alerts!";
        } else {
            $response['success'] = false;
            $response['message'] = "Error: " . $conn->error;
        }
    }
    
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>