<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

// Log access to this script
error_log("process_missing_person.php was accessed");

// Process only if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Missing person report POST request received");
    
    // Debug: Log all POST data
    error_log("POST data: " . print_r($_POST, true));
    
    // Check if required fields are set
    if (!isset($_POST['fullName']) || !isset($_POST['age']) || !isset($_POST['lastSeen']) || !isset($_POST['location']) || !isset($_POST['contactInfo'])) {
        error_log("Missing required fields for missing person report");
        session_start();
        $_SESSION['missing_person_error'] = "Please fill in all required fields.";
        header("Location: ../pages/lost.php#report-tab");
        exit;
    }
    
    try {
        // Get form data and sanitize
        $fullName = $conn->real_escape_string($_POST['fullName']);
        $age = intval($_POST['age']);
        $gender = isset($_POST['gender']) ? $conn->real_escape_string($_POST['gender']) : '';
        $lastSeen = $conn->real_escape_string($_POST['lastSeen']);
        $location = $conn->real_escape_string($_POST['location']);
        $contactInfo = $conn->real_escape_string($_POST['contactInfo']);
        $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
        $circumstances = isset($_POST['circumstances']) ? $conn->real_escape_string($_POST['circumstances']) : '';
        
        // Handle photo upload if present
        $photoUrl = '';
        if (isset($_FILES['photoUpload']) && $_FILES['photoUpload']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/missing_persons/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate a unique filename
            $fileName = time() . '_' . basename($_FILES['photoUpload']['name']);
            $targetPath = $uploadDir . $fileName;
            
            // Move the uploaded file
            if (move_uploaded_file($_FILES['photoUpload']['tmp_name'], $targetPath)) {
                $photoUrl = 'uploads/missing_persons/' . $fileName;
                error_log("Photo uploaded successfully: " . $photoUrl);
            } else {
                error_log("Failed to upload photo: " . $_FILES['photoUpload']['error']);
            }
        }
        
        error_log("Missing person data: name=$fullName, age=$age, gender=$gender, location=$location");
        
        // Insert data into the missing_persons table
        $sql = "INSERT INTO missing_persons (full_name, age, gender, physical_description, last_seen_date, last_location, reporter_contact, circumstances, photo_url, status) 
                VALUES ('$fullName', $age, '$gender', '$description', '$lastSeen', '$location', '$contactInfo', '$circumstances', '$photoUrl', 'missing')";
        
        error_log("Executing SQL: $sql");
        
        if ($conn->query($sql) === TRUE) {
            error_log("Missing person report submitted successfully, inserted ID: " . $conn->insert_id);
            
            // Set a session variable to indicate success for displaying on the page
            session_start();
            $_SESSION['missing_person_success'] = true;
            $_SESSION['missing_person_message'] = "Your missing person report for $fullName has been submitted and will be published after review.";
            $_SESSION['missing_person_id'] = $conn->insert_id;
            
            // Redirect back to lost.php
            header("Location: ../pages/lost.php#report-tab");
            exit;
        } else {
            error_log("SQL Error: " . $conn->error);
            
            // Set error message
            session_start();
            $_SESSION['missing_person_error'] = "Error processing your report. Please try again.";
            
            // Redirect back to lost.php
            header("Location: ../pages/lost.php#report-tab");
            exit;
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        
        // Set error message
        session_start();
        $_SESSION['missing_person_error'] = "An error occurred: " . $e->getMessage();
        
        // Redirect back to lost.php
        header("Location: ../pages/lost.php#report-tab");
        exit;
    }
} else {
    error_log("Not a POST request to process_missing_person");
    // If accessed directly, redirect to the homepage
    header("Location: ../pages/lost.php");
    exit;
}
?>