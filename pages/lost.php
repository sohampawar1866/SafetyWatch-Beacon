<?php
// Start session to access session variables
session_start();

// Include database connection
require_once '../includes/db_connect.php';

// Check if we have a database error
if (isset($db_error)) {
    // Set a flag to display a user-friendly message in the page
    $has_db_error = true;
} else {
    $has_db_error = false;
    
    // Function to escape output
    function escapeOutput($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    // Only run database queries if we have a working connection
    if (!$has_db_error) {
        // Get missing persons statistics
        try {
            $missingCountSql = "SELECT COUNT(*) as count FROM missing_persons WHERE status = 'missing'";
            $foundCountSql = "SELECT COUNT(*) as count FROM missing_persons WHERE status = 'found'";
            $totalReportsSql = "SELECT COUNT(*) as count FROM missing_persons";
            $countriesSql = "SELECT COUNT(DISTINCT SUBSTRING_INDEX(last_location, ',', -1)) as count FROM missing_persons";
            
            $missingCount = $conn->query($missingCountSql)->fetch_assoc()['count'] ?? 0;
            $foundCount = $conn->query($foundCountSql)->fetch_assoc()['count'] ?? 0;
            $totalReports = $conn->query($totalReportsSql)->fetch_assoc()['count'] ?? 0;
            $countriesCount = $conn->query($countriesSql)->fetch_assoc()['count'] ?? 0;
            
            // Get the most recent missing persons reports
            $recentMissingSql = "SELECT * FROM missing_persons ORDER BY created_at DESC LIMIT 10";
            $recentMissingResult = $conn->query($recentMissingSql);
            $missingPersons = [];
            
            if ($recentMissingResult && $recentMissingResult->num_rows > 0) {
                while ($row = $recentMissingResult->fetch_assoc()) {
                    $missingPersons[] = $row;
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching missing persons data: " . $e->getMessage());
            // Set defaults
            $missingCount = 0;
            $foundCount = 0;
            $totalReports = 0;
            $countriesCount = 0;
            $missingPersons = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BeaconHub - Safety Watch: Beacon Alerts</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Page-specific styles */
    .page-header {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .page-header h1 {
      font-size: 2.5rem;
      color: #1f2937;
      margin-bottom: 10px;
    }
    
    .page-header p {
      font-size: 1.1rem;
      color: #4b5563;
      max-width: 700px;
      margin: 0 auto;
    }
    
    .tabs-container {
      margin-bottom: 40px;
    }
    
    .tabs-nav {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-bottom: 30px;
    }
    
    .tab-btn {
      padding: 12px 25px;
      background-color: #f3f4f6;
      color: #4b5563;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .tab-btn:hover {
      background-color: #e5e7eb;
    }
    
    .tab-btn.active {
      background-color: #e63946;
      color: white;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
      animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    /* Report form styles */
    .report-form {
      background-color: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .form-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .form-group {
      flex: 1;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #374151;
    }
    
    .form-control {
      width: 100%;
      padding: 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    
    .form-control:focus {
      border-color: #e63946;
      box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.1);
      outline: none;
    }
    
    .form-control::placeholder {
      color: #9ca3af;
    }
    
    .form-note {
      font-size: 0.85rem;
      color: #6b7280;
      margin-top: 6px;
    }
    
    .submit-btn {
      display: inline-block;
      padding: 12px 24px;
      background-color: #e63946;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    .submit-btn:hover {
      background-color: #c1121f;
    }
    
    .photo-upload {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border: 2px dashed #d1d5db;
      border-radius: 8px;
      padding: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .photo-upload:hover {
      border-color: #e63946;
      background-color: rgba(230, 57, 70, 0.05);
    }
    
    .photo-upload i {
      font-size: 2.5rem;
      color: #6b7280;
      margin-bottom: 15px;
    }
    
    .photo-upload p {
      font-size: 0.9rem;
      color: #4b5563;
      text-align: center;
    }
    
    /* Missing persons grid */
    .persons-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 25px;
    }
    
    .person-card {
      background-color: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .person-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .person-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    
    .person-info {
      padding: 20px;
    }
    
    .person-name {
      font-size: 1.2rem;
      font-weight: 600;
      color: #1f2937;
      margin: 0 0 5px 0;
    }
    
    .person-details {
      margin: 15px 0;
      font-size: 0.95rem;
      color: #4b5563;
    }
    
    .person-detail {
      display: flex;
      align-items: baseline;
      gap: 8px;
      margin-bottom: 8px;
    }
    
    .person-detail i {
      color: #e63946;
      width: 16px;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 12px;
    }
    
    .status-missing {
      background-color: #fee2e2;
      color: #ef4444;
    }
    
    .status-found {
      background-color: #d1fae5;
      color: #10b981;
    }
    
    .card-actions {
      margin-top: 15px;
      display: flex;
      gap: 10px;
    }
    
    .action-btn {
      flex: 1;
      display: inline-block;
      padding: 8px;
      text-align: center;
      background-color: #f3f4f6;
      color: #4b5563;
      border: none;
      border-radius: 6px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .action-btn:hover {
      background-color: #e5e7eb;
    }
    
    .action-btn.primary {
      background-color: #e63946;
      color: white;
    }
    
    .action-btn.primary:hover {
      background-color: #c1121f;
    }
    
    /* Search and filter */
    .search-container {
      margin-bottom: 30px;
    }
    
    .search-box {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
    }
    
    .search-input {
      flex: 1;
      padding: 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .search-input:focus {
      border-color: #e63946;
      box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.1);
      outline: none;
    }
    
    .search-btn {
      padding: 0 20px;
      background-color: #e63946;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    .search-btn:hover {
      background-color: #c1121f;
    }
    
    .filter-options {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    
    .filter-select {
      padding: 8px 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 0.9rem;
      color: #4b5563;
      background-color: white;
    }
    
    /* Photo search tab */
    .photo-search-container {
      background-color: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      text-align: center;
    }
    
    .photo-search-container p {
      margin-bottom: 25px;
      font-size: 1.1rem;
      color: #4b5563;
    }
    
    .upload-container {
      max-width: 500px;
      margin: 0 auto;
    }
    
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin: 40px 0;
    }
    
    .stat-card {
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      text-align: center;
    }
    
    .stat-card i {
      font-size: 2rem;
      color: #e63946;
      margin-bottom: 15px;
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: #4b5563;
    }
    
    /* Notification banner */
    .notification-banner {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .notification-banner.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .notification-banner.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .notification-banner .close-btn {
      cursor: pointer;
      font-size: 20px;
    }
    
    /* Subscription success message */
    #subscription-success {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      border-radius: 5px;
      padding: 15px 25px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 15px;
      max-width: 400px;
      animation: slideIn 0.5s ease forwards;
    }
    
    #subscription-success i {
      font-size: 24px;
    }
    
    #subscription-success p {
      margin: 0;
      font-size: 0.95rem;
    }
    
    #subscription-success .close-btn {
      position: absolute;
      top: 5px;
      right: 10px;
      cursor: pointer;
    }
    
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  </style>
</head>
<body>
  <!-- Loading Screen with Animation -->
  <div id="loading-screen">
    <div class="loader-container">
      <img id="loading-image" src="../images/heart-icon.svg" alt="Loading...">
      <div id="progress-container">
        <div id="progress-bar"></div>
      </div>
      <p>Loading BeaconHub...</p>
    </div>
  </div>

  <!-- Enhanced Navbar with Smooth Transitions -->
  <nav class="navbar">
    <div class="logo-title">
      <img src="../images/alert-bell.svg" alt="Safety Watch Logo" class="logo-image">
      <div class="title">Safety Watch <span class="sub-title">Beacon Alerts</span></div>
    </div>
    <div class="navbar-links">
      <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
      <a href="donate.php" class="nav-link"><i class="fas fa-heart"></i> Donate</a>
      <a href="lost.php" class="nav-link active"><i class="fas fa-users"></i> BeaconHub</a>
      <a href="map.php" class="nav-link"><i class="fas fa-map-marked-alt"></i> Crisis Map</a>
    </div>
    <div class="hamburger-menu">
      <div class="bar"></div>
      <div class="bar"></div>
      <div class="bar"></div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">
    <!-- Display subscription success message if available -->
    <?php if (isset($_SESSION['subscription_success']) && $_SESSION['subscription_success']): ?>
      <div id="subscription-success">
        <i class="fas fa-check-circle"></i>
        <p><?php echo escapeOutput($_SESSION['subscription_message']); ?></p>
        <span class="close-btn">&times;</span>
      </div>
      <?php 
        // Clear the session variables
        unset($_SESSION['subscription_success']);
        unset($_SESSION['subscription_message']);
      ?>
    <?php endif; ?>

    <div class="page-header" data-aos="fade-up">
      <h1>BeaconHub: Missing Persons Platform</h1>
      <p>Report missing individuals or search through our database to help reconnect families and loved ones during crisis situations.</p>
    </div>

    <div class="stats-container" data-aos="fade-up" data-aos-delay="100">
      <div class="stat-card">
        <i class="fas fa-user-plus"></i>
        <div class="stat-number"><?php echo escapeOutput($totalReports); ?></div>
        <div class="stat-label">Missing Persons Reported</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-handshake"></i>
        <div class="stat-number"><?php echo escapeOutput($foundCount); ?></div>
        <div class="stat-label">People Reunited</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-users"></i>
        <div class="stat-number"><?php echo escapeOutput($missingCount); ?></div>
        <div class="stat-label">Active Cases</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-globe"></i>
        <div class="stat-number"><?php echo escapeOutput($countriesCount); ?></div>
        <div class="stat-label">Countries Covered</div>
      </div>
    </div>

    <div class="tabs-container" data-aos="fade-up" data-aos-delay="200">
      <div class="tabs-nav">
        <button class="tab-btn active" data-tab="report">Report Missing Person</button>
        <button class="tab-btn" data-tab="view">View Missing Persons</button>
        <button class="tab-btn" data-tab="photo">Search by Photo</button>
      </div>

      <!-- Report Missing Person Tab -->
      <div class="tab-content active" id="report-tab">
        <div class="report-form">
          <!-- Display success message if available -->
          <?php if (isset($_SESSION['missing_person_success']) && $_SESSION['missing_person_success']): ?>
            <div class="notification-banner success">
              <span><?php echo escapeOutput($_SESSION['missing_person_message']); ?></span>
              <span class="close-btn">&times;</span>
            </div>
            <?php 
              // Clear the session variables
              unset($_SESSION['missing_person_success']);
              unset($_SESSION['missing_person_message']);
              unset($_SESSION['missing_person_id']);
            ?>
          <?php endif; ?>
          
          <!-- Display error message if available -->
          <?php if (isset($_SESSION['missing_person_error'])): ?>
            <div class="notification-banner error">
              <span><?php echo escapeOutput($_SESSION['missing_person_error']); ?></span>
              <span class="close-btn">&times;</span>
            </div>
            <?php 
              // Clear the session variable
              unset($_SESSION['missing_person_error']);
            ?>
          <?php endif; ?>
          
          <form id="reportForm" action="../includes/process_missing_person.php" method="POST" enctype="multipart/form-data">
            <div class="form-row">
              <div class="form-group">
                <label for="fullName">Full Name *</label>
                <input type="text" id="fullName" name="fullName" class="form-control" placeholder="Enter full name of missing person" required>
              </div>
              <div class="form-group">
                <label for="age">Age *</label>
                <input type="number" id="age" name="age" class="form-control" placeholder="Age" required min="0" max="120">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" class="form-control">
                  <option value="" selected disabled>Select gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="form-group">
                <label for="lastSeen">Last Seen Date *</label>
                <input type="date" id="lastSeen" name="lastSeen" class="form-control" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="location">Last Known Location *</label>
                <input type="text" id="location" name="location" class="form-control" placeholder="City, region, or specific location" required>
              </div>
              <div class="form-group">
                <label for="contactInfo">Reporter's Contact Information *</label>
                <input type="text" id="contactInfo" name="contactInfo" class="form-control" placeholder="Phone number or email address" required>
                <p class="form-note">This information will be used to contact you if there are any updates.</p>
              </div>
            </div>

            <div class="form-group">
              <label for="description">Physical Description</label>
              <textarea id="description" name="description" class="form-control" rows="4" placeholder="Height, weight, distinctive features, clothing when last seen, etc."></textarea>
            </div>

            <div class="form-group">
              <label for="photoUpload">Upload Photo (if available)</label>
              <div class="photo-upload" id="photoDropArea">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Drag and drop an image here<br>or click to select a file</p>
                <input type="file" id="photoUpload" name="photoUpload" accept="image/*" style="display: none;">
              </div>
              <p class="form-note">A clear photo can greatly increase the chances of finding the person.</p>
            </div>

            <div class="form-group">
              <label for="circumstances">Circumstances</label>
              <textarea id="circumstances" name="circumstances" class="form-control" rows="3" placeholder="Any information about how the person went missing or relevant circumstances"></textarea>
            </div>

            <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Report</button>
          </form>
        </div>
      </div>

      <!-- View Missing Persons Tab -->
      <div class="tab-content" id="view-tab">
        <div class="search-container">
          <div class="search-box">
            <input type="text" class="search-input" placeholder="Search by name, location, or ID">
            <button class="search-btn">Search</button>
          </div>
          <div class="filter-options">
            <select class="filter-select">
              <option value="all">All Statuses</option>
              <option value="missing">Missing</option>
              <option value="found">Found</option>
            </select>
            <select class="filter-select">
              <option value="all">All Locations</option>
              <option value="ukraine">Ukraine</option>
              <option value="gaza">Gaza</option>
              <option value="syria">Syria</option>
              <option value="sudan">Sudan</option>
              <option value="yemen">Yemen</option>
            </select>
            <select class="filter-select">
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
            </select>
          </div>
        </div>

        <div class="persons-grid">
          <!-- Dynamically generated person cards -->
          <?php foreach ($missingPersons as $index => $person): ?>
            <?php 
              $delay = $index * 50;
              $defaultImage = 'https://randomuser.me/api/portraits/' . 
                (strtolower($person['gender']) === 'female' ? 'women/' : 'men/') . 
                rand(1, 99) . '.jpg';
              $imageUrl = !empty($person['photo_url']) ? '../' . $person['photo_url'] : $defaultImage;
              $formattedDate = date('F j, Y', strtotime($person['last_seen_date']));
            ?>
            <div class="person-card" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
              <img src="<?php echo escapeOutput($imageUrl); ?>" alt="<?php echo escapeOutput($person['full_name']); ?>" class="person-img">
              <div class="person-info">
                <span class="status-badge status-<?php echo strtolower($person['status']); ?>">
                  <?php echo ucfirst(escapeOutput($person['status'])); ?>
                </span>
                <h3 class="person-name"><?php echo escapeOutput($person['full_name']); ?></h3>
                <div class="person-details">
                  <div class="person-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo escapeOutput($person['last_location']); ?></span>
                  </div>
                  <div class="person-detail">
                    <i class="fas fa-calendar"></i>
                    <span>Last seen: <?php echo escapeOutput($formattedDate); ?></span>
                  </div>
                  <div class="person-detail">
                    <i class="fas fa-user"></i>
                    <span><?php echo escapeOutput($person['age']); ?> years old, <?php echo escapeOutput($person['gender']); ?></span>
                  </div>
                </div>
                <div class="card-actions">
                  <button class="action-btn">View Details</button>
                  <button class="action-btn primary">Share Info</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          
          <?php if (empty($missingPersons)): ?>
            <div class="notification-banner info" style="grid-column: 1 / -1; text-align: center;">
              <span>No missing person reports found. Be the first to submit a report.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Search by Photo Tab -->
      <div class="tab-content" id="photo-tab">
        <div class="photo-search-container">
          <p>Upload a photo to search for matching faces in our missing persons database.</p>
          
          <div class="upload-container">
            <div class="photo-upload" id="photoSearchArea">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Drag and drop a clear facial photo here<br>or click to select a file</p>
              <input type="file" id="photoSearch" accept="image/*" style="display: none;">
            </div>
            <p class="form-note">Our facial recognition technology compares your uploaded photo with thousands of reported cases in our database.</p>
            
            <button class="submit-btn" style="margin-top: 20px;"><i class="fas fa-search"></i> Start Facial Recognition Search</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Enhanced Footer with Animation -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section" data-aos="fade-right">
        <h3><i class="fas fa-bell"></i> Safety Watch: Beacon Alerts</h3>
        <p>Providing critical alerts and information during emergencies. Stay informed, stay safe.</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-telegram"></i></a>
        </div>
      </div>
  
      <div class="footer-section" data-aos="fade-up">
        <h3>Emergency Contacts</h3>
        <p><i class="fas fa-phone-alt"></i> Emergency Hotline: <strong>1-800-SAFE-NOW</strong></p>
        <p><i class="fas fa-envelope"></i> Report Critical Information: <br><strong>report@safetywatch.org</strong></p>
      </div>
  
      <div class="footer-section" data-aos="fade-left">
        <h3>Stay Updated</h3>
        <p>Sign up to receive real-time alerts directly to your device.</p>
        <form class="subscribe-form" action="../includes/process_subscription.php" method="POST">
          <input type="email" name="email" placeholder="Your email" required>
          <button type="submit">Subscribe</button>
        </form>
      </div>
    </div>
  
    <div class="footer-bottom">
      <p>© 2025 Safety Watch: Beacon Alerts. All rights reserved.</p>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script src="../js/script.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
      });
      
      // Tab functionality
      const tabButtons = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabButtons.forEach(button => {
        button.addEventListener('click', () => {
          const tabId = button.getAttribute('data-tab');
          
          // Deactivate all tabs
          tabButtons.forEach(btn => btn.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));
          
          // Activate selected tab
          button.classList.add('active');
          document.getElementById(`${tabId}-tab`).classList.add('active');
        });
      });
      
      // Photo upload functionality
      const photoDropArea = document.getElementById('photoDropArea');
      const photoUpload = document.getElementById('photoUpload');
      
      if (photoDropArea && photoUpload) {
        photoDropArea.addEventListener('click', () => {
          photoUpload.click();
        });
        
        photoUpload.addEventListener('change', function() {
          if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
              photoDropArea.innerHTML = `
                <img src="${e.target.result}" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                <p>Click to change image</p>
              `;
            };
            reader.readAsDataURL(this.files[0]);
          }
        });
      }
      
      // Photo search functionality
      const photoSearchArea = document.getElementById('photoSearchArea');
      const photoSearch = document.getElementById('photoSearch');
      
      if (photoSearchArea && photoSearch) {
        photoSearchArea.addEventListener('click', () => {
          photoSearch.click();
        });
        
        photoSearch.addEventListener('change', function() {
          if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
              photoSearchArea.innerHTML = `
                <img src="${e.target.result}" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                <p>Click to change image</p>
              `;
            };
            reader.readAsDataURL(this.files[0]);
          }
        });
      }
      
      // Close notification banner when X is clicked
      const closeButtons = document.querySelectorAll('.notification-banner .close-btn, #subscription-success .close-btn');
      closeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
          const banner = this.parentElement;
          banner.style.display = 'none';
        });
      });
      
      // Handle subscription form submission via AJAX
      const subscribeForm = document.querySelector('.footer-section .subscribe-form');
      
      if (subscribeForm) {
        subscribeForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const email = this.querySelector('input[name="email"]').value;
          const formData = new FormData();
          formData.append('email', email);
          
          fetch('../includes/process_subscription.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Create success notification
              const successMessage = document.createElement('div');
              successMessage.id = 'subscription-success';
              successMessage.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <p>${data.message}</p>
                <span class="close-btn">&times;</span>
              `;
              document.body.appendChild(successMessage);
              
              // Add event listener to close button
              successMessage.querySelector('.close-btn').addEventListener('click', function() {
                successMessage.style.display = 'none';
              });
              
              // Clear the form
              subscribeForm.reset();
              
              // Auto-hide after 5 seconds
              setTimeout(() => {
                if (successMessage && successMessage.parentNode) {
                  successMessage.style.opacity = '0';
                  setTimeout(() => {
                    if (successMessage && successMessage.parentNode) {
                      successMessage.parentNode.removeChild(successMessage);
                    }
                  }, 500);
                }
              }, 5000);
            } else {
              // Show error in an alert
              alert(data.message || 'An error occurred. Please try again.');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
          });
        });
      }
      
      // Auto-hide subscription success message after 5 seconds
      const subscriptionSuccess = document.getElementById('subscription-success');
      if (subscriptionSuccess) {
        setTimeout(() => {
          subscriptionSuccess.style.opacity = '0';
          setTimeout(() => {
            if (subscriptionSuccess && subscriptionSuccess.parentNode) {
              subscriptionSuccess.parentNode.removeChild(subscriptionSuccess);
            }
          }, 500);
        }, 5000);
      }
    });
  </script>
</body>
</html>