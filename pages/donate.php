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
        // Get donation totals and statistics
        $donationStats = array(
            'ukraine' => ['raised' => 0, 'goal' => 500000],
            'gaza' => ['raised' => 0, 'goal' => 500000],
            'syria' => ['raised' => 0, 'goal' => 500000],
            'sudan' => ['raised' => 0, 'goal' => 500000],
            'yemen' => ['raised' => 0, 'goal' => 500000],
            'myanmar' => ['raised' => 0, 'goal' => 500000]
        );
        
        try {
            // Get total donations by crisis type
            $donationSql = "SELECT crisis_type, SUM(amount) as total FROM donations WHERE status != 'failed' GROUP BY crisis_type";
            $donationResult = $conn->query($donationSql);
            
            if ($donationResult && $donationResult->num_rows > 0) {
                while ($row = $donationResult->fetch_assoc()) {
                    $crisisType = strtolower($row['crisis_type']);
                    
                    // Extract just the country name from the crisis type (e.g., "Ukraine Crisis" -> "ukraine")
                    foreach ($donationStats as $key => $value) {
                        if (strpos(strtolower($row['crisis_type']), $key) !== false) {
                            $crisisType = $key;
                            break;
                        }
                    }
                    
                    if (isset($donationStats[$crisisType])) {
                        $donationStats[$crisisType]['raised'] = $row['total'];
                    }
                }
            }
            
            // Get total donation count
            $totalDonationSql = "SELECT COUNT(*) as count FROM donations WHERE status != 'failed'";
            $totalDonationResult = $conn->query($totalDonationSql);
            $totalDonations = $totalDonationResult->fetch_assoc()['count'] ?? 0;
            
            // Get recent donations for display
            $recentDonationsSql = "SELECT * FROM donations WHERE status != 'failed' ORDER BY created_at DESC LIMIT 5";
            $recentDonationsResult = $conn->query($recentDonationsSql);
            $recentDonations = [];
            
            if ($recentDonationsResult && $recentDonationsResult->num_rows > 0) {
                while ($row = $recentDonationsResult->fetch_assoc()) {
                    $recentDonations[] = $row;
                }
            }
            
        } catch (Exception $e) {
            error_log("Error fetching donation data: " . $e->getMessage());
            // Create empty arrays if there was an error
            $recentDonations = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Donate - Safety Watch: Beacon Alerts</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Page-specific styles */
    .quote-banner {
      background-color: #f0f4f8;
      padding: 40px 20px;
      text-align: center;
      margin-bottom: 40px;
      border-radius: 8px;
    }
    
    .quote-text {
      font-size: 1.8rem;
      font-style: italic;
      color: #333;
      max-width: 800px;
      margin: 0 auto;
      line-height: 1.6;
    }
    
    .donation-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
      gap: 30px;
      margin: 40px 0;
    }
    
    .donation-card {
      background-color: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .donation-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
      padding: 20px;
      border-bottom: 1px solid #eee;
    }
    
    .card-header h3 {
      margin: 0;
      color: #1f2937;
    }
    
    .card-body {
      padding: 20px;
    }
    
    .card-body p {
      color: #4b5563;
      margin-bottom: 20px;
      line-height: 1.6;
    }
    
    .progress-container {
      width: 100%;
      height: 10px;
      background-color: #e5e7eb;
      border-radius: 5px;
      overflow: hidden;
      margin-bottom: 10px;
    }
    
    .progress-bar {
      height: 100%;
      border-radius: 5px;
      transition: width 1.5s ease;
    }
    
    .progress-ukraine { background-color: #3b82f6; }
    .progress-gaza { background-color: #ef4444; }
    .progress-syria { background-color: #10b981; }
    .progress-sudan { background-color: #f59e0b; }
    .progress-yemen { background-color: #8b5cf6; }
    .progress-myanmar { background-color: #ec4899; }
    
    .progress-info {
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      color: #6b7280;
      margin-bottom: 20px;
    }
    
    .donate-btn {
      display: block;
      width: 100%;
      padding: 12px;
      text-align: center;
      background-color: #e63946;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    .donate-btn:hover {
      background-color: #c1121f;
    }
    
    .heading-section {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .heading-section h2 {
      font-size: 2.4rem;
      color: #1f2937;
      margin-bottom: 15px;
    }
    
    .heading-section p {
      color: #4b5563;
      max-width: 700px;
      margin: 0 auto;
    }
    
    /* Success message styles */
    .donation-success {
      background-color: #d4edda;
      color: #155724;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
      text-align: center;
      border: 1px solid #c3e6cb;
    }
    
    .donation-success h3 {
      margin-top: 0;
      color: #155724;
    }
    
    .donation-success p {
      margin-bottom: 15px;
    }
    
    .transaction-id {
      font-family: monospace;
      background-color: #f8f9fa;
      padding: 8px 15px;
      border-radius: 4px;
      font-weight: bold;
    }
    
    /* Recent donations section */
    .recent-donations {
      margin-top: 40px;
      padding-top: 40px;
      border-top: 1px solid #eaeaea;
    }
    
    .recent-donations h3 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 1.8rem;
      color: #1f2937;
    }
    
    .donation-list {
      max-width: 800px;
      margin: 0 auto;
    }
    
    .donation-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px;
      background-color: white;
      border-radius: 8px;
      margin-bottom: 15px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
    }
    
    .donation-item:hover {
      transform: translateX(5px);
    }
    
    .donor-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .donor-avatar {
      width: 40px;
      height: 40px;
      background-color: #e63946;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    .donor-details h4 {
      margin: 0 0 5px 0;
      font-size: 1rem;
    }
    
    .donor-details p {
      margin: 0;
      font-size: 0.85rem;
      color: #6b7280;
    }
    
    .donation-amount {
      font-weight: bold;
      color: #e63946;
    }
    
    @media (max-width: 768px) {
      .donation-cards {
        grid-template-columns: 1fr;
      }
      
      .quote-text {
        font-size: 1.4rem;
      }
      
      .donation-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .donation-amount {
        align-self: flex-end;
      }
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
      <p>Loading Donation Information...</p>
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
      <a href="donate.php" class="nav-link active"><i class="fas fa-heart"></i> Donate</a>
      <a href="lost.php" class="nav-link"><i class="fas fa-users"></i> BeaconHub</a>
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
    <!-- Quote Banner -->
    <div class="quote-banner" data-aos="fade-up">
      <p class="quote-text">" Where bombs have stolen lullabies and homes lie in ash, let your gift be the light that leads hearts back to hope "</p>
    </div>
    
    <!-- Display donation success message if available -->
    <?php if (isset($_SESSION['donation_success']) && $_SESSION['donation_success']): ?>
      <div id="donation-success" class="donation-success" data-aos="fade-up">
        <h3><i class="fas fa-check-circle"></i> Donation Successful!</h3>
        <p><?php echo escapeOutput($_SESSION['donation_message']); ?></p>
        <p>Transaction ID: <span class="transaction-id"><?php echo escapeOutput($_SESSION['transaction_id']); ?></span></p>
        <p>Please save this transaction ID for your records. A confirmation email has also been sent to your email address.</p>
      </div>
      <?php 
        // Clear the session variables
        unset($_SESSION['donation_success']);
        unset($_SESSION['donation_message']);
        unset($_SESSION['transaction_id']);
      ?>
    <?php endif; ?>
    
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
    
    <div class="heading-section" data-aos="fade-up" data-aos-delay="100">
      <h2>Current Crisis Relief Efforts</h2>
      <p>Your donation can make a real difference in the lives of people affected by conflicts around the world.</p>
    </div>

    <!-- Donation Cards -->
    <div class="donation-cards">
      <!-- Ukraine -->
      <div class="donation-card" data-aos="fade-up" data-aos-delay="150">
        <div class="card-header">
          <h3>Ukraine Crisis Relief</h3>
        </div>
        <div class="card-body">
          <p>Support humanitarian aid for families affected by the ongoing conflict in Ukraine.</p>
          <div class="progress-container">
            <div class="progress-bar progress-ukraine" id="ukraine-progress"></div>
          </div>
          <div class="progress-info">
            <span>Raised: $<?php echo number_format($donationStats['ukraine']['raised']); ?></span>
            <span>Goal: $<?php echo number_format($donationStats['ukraine']['goal']); ?></span>
          </div>
          <a href="#donation-form" class="donate-btn">Donate Now</a>
        </div>
      </div>

      <!-- Gaza -->
      <div class="donation-card" data-aos="fade-up" data-aos-delay="200">
        <div class="card-header">
          <h3>Gaza Crisis Relief</h3>
        </div>
        <div class="card-body">
          <p>Provide essential medical supplies and food to civilians caught in the Gaza conflict.</p>
          <div class="progress-container">
            <div class="progress-bar progress-gaza" id="gaza-progress"></div>
          </div>
          <div class="progress-info">
            <span>Raised: $<?php echo number_format($donationStats['gaza']['raised']); ?></span>
            <span>Goal: $<?php echo number_format($donationStats['gaza']['goal']); ?></span>
          </div>
          <a href="#donation-form" class="donate-btn">Donate Now</a>
        </div>
      </div>

      <!-- Syria -->
      <div class="donation-card" data-aos="fade-up" data-aos-delay="250">
        <div class="card-header">
          <h3>Syria Crisis Relief</h3>
        </div>
        <div class="card-body">
          <p>Help provide shelter and food for families displaced by the Syrian civil war.</p>
          <div class="progress-container">
            <div class="progress-bar progress-syria" id="syria-progress"></div>
          </div>
          <div class="progress-info">
            <span>Raised: $<?php echo number_format($donationStats['syria']['raised']); ?></span>
            <span>Goal: $<?php echo number_format($donationStats['syria']['goal']); ?></span>
          </div>
          <a href="#donation-form" class="donate-btn">Donate Now</a>
        </div>
      </div>

      <!-- Sudan -->
      <div class="donation-card" data-aos="fade-up" data-aos-delay="300">
        <div class="card-header">
          <h3>Sudan Crisis Relief</h3>
        </div>
        <div class="card-body">
          <p>Support humanitarian efforts for families affected by the ongoing conflict in Sudan.</p>
          <div class="progress-container">
            <div class="progress-bar progress-sudan" id="sudan-progress"></div>
          </div>
          <div class="progress-info">
            <span>Raised: $<?php echo number_format($donationStats['sudan']['raised']); ?></span>
            <span>Goal: $<?php echo number_format($donationStats['sudan']['goal']); ?></span>
          </div>
          <a href="#donation-form" class="donate-btn">Donate Now</a>
        </div>
      </div>

      <!-- Yemen -->
      <div class="donation-card" data-aos="fade-up" data-aos-delay="350">
        <div class="card-header">
          <h3>Yemen Crisis Relief</h3>
        </div>
        <div class="card-body">
          <p>Provide food and medical supplies to families affected by the ongoing conflict in Yemen.</p>
          <div class="progress-container">
            <div class="progress-bar progress-yemen" id="yemen-progress"></div>
          </div>
          <div class="progress-info">
            <span>Raised: $<?php echo number_format($donationStats['yemen']['raised']); ?></span>
            <span>Goal: $<?php echo number_format($donationStats['yemen']['goal']); ?></span>
          </div>
          <a href="#donation-form" class="donate-btn">Donate Now</a>
        </div>
      </div>

      <!-- Myanmar -->
      <div class="donation-card" data-aos="fade-up" data-aos-delay="400">
        <div class="card-header">
          <h3>Myanmar Crisis Relief</h3>
        </div>
        <div class="card-body">
          <p>Support humanitarian efforts for families affected by the ongoing conflict in Myanmar.</p>
          <div class="progress-container">
            <div class="progress-bar progress-myanmar" id="myanmar-progress"></div>
          </div>
          <div class="progress-info">
            <span>Raised: $<?php echo number_format($donationStats['myanmar']['raised']); ?></span>
            <span>Goal: $<?php echo number_format($donationStats['myanmar']['goal']); ?></span>
          </div>
          <a href="#donation-form" class="donate-btn">Donate Now</a>
        </div>
      </div>
    </div>

    <!-- Recent Donations Section -->
    <?php if (!empty($recentDonations)): ?>
    <div class="recent-donations" data-aos="fade-up">
      <h3>Recent Donations</h3>
      <div class="donation-list">
        <?php foreach ($recentDonations as $donation): ?>
          <?php 
            $initial = substr($donation['full_name'], 0, 1); 
            $date = date('M d, Y', strtotime($donation['created_at']));
          ?>
          <div class="donation-item">
            <div class="donor-info">
              <div class="donor-avatar"><?php echo escapeOutput($initial); ?></div>
              <div class="donor-details">
                <h4><?php echo escapeOutput($donation['full_name']); ?></h4>
                <p><?php echo escapeOutput($donation['crisis_type']); ?> • <?php echo escapeOutput($date); ?></p>
              </div>
            </div>
            <div class="donation-amount">$<?php echo number_format($donation['amount'], 2); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Donation Form Section -->
    <section id="donation-form" class="form-section" data-aos="fade-up">
      <h2><i class="fas fa-hand-holding-heart"></i> Make a Donation</h2>
      
      <!-- Display error message if available -->
      <?php if (isset($_SESSION['donation_error'])): ?>
        <div class="notification-banner error">
          <span><?php echo escapeOutput($_SESSION['donation_error']); ?></span>
          <span class="close-btn">&times;</span>
        </div>
        <?php 
          // Clear the session variable
          unset($_SESSION['donation_error']);
        ?>
      <?php endif; ?>
      
      <form id="donateForm" class="donation-form" action="../includes/process_donation.php" method="POST">
        <div class="form-group">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" name="fullName" placeholder="Enter your full name" required class="form-control" />
        </div>
        
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="Enter your email address" required class="form-control" />
        </div>
        
        <div class="form-group">
          <label for="amount">Donation Amount ($)</label>
          <input type="number" id="amount" name="amount" placeholder="Enter amount" min="5" required class="form-control" />
        </div>
        
        <div class="form-group">
          <label for="crisisType">Select Crisis</label>
          <select id="crisisType" name="crisisType" required class="form-control">
            <option value="" disabled selected>Choose a crisis to support</option>
            <option value="Ukraine Crisis">Ukraine Crisis</option>
            <option value="Gaza Crisis">Gaza Crisis</option>
            <option value="Syria Crisis">Syria Crisis</option>
            <option value="Sudan Crisis">Sudan Crisis</option>
            <option value="Yemen Crisis">Yemen Crisis</option>
            <option value="Myanmar Crisis">Myanmar Crisis</option>
            <option value="General Support">Where Most Needed</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="paymentMethod">Payment Method</label>
          <select id="paymentMethod" name="paymentMethod" required class="form-control">
            <option value="" disabled selected>Select payment method</option>
            <option value="Credit Card">Credit/Debit Card</option>
            <option value="PayPal">PayPal</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="UPI">UPI</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="message">Message (Optional)</label>
          <textarea id="message" name="message" placeholder="Leave a message of support" class="form-control"></textarea>
        </div>
        
        <button type="submit" class="submit-btn"><i class="fas fa-heart"></i> Complete Donation</button>
      </form>
    </section>
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
        <form id="subscribe-form" class="subscribe-form" action="../includes/process_subscription.php" method="POST">
          <input type="email" name="email" placeholder="Your email" required>
          <button type="submit">Subscribe</button>
        </form>
        <div id="subscribe-message"></div>
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
    // Initialize progress bars with animation
    document.addEventListener('DOMContentLoaded', function() {
      // Set progress bar percentages based on actual data
      const progressBars = {
          'ukraine': <?php echo min(100, ($donationStats['ukraine']['raised'] / $donationStats['ukraine']['goal']) * 100); ?>,
          'gaza': <?php echo min(100, ($donationStats['gaza']['raised'] / $donationStats['gaza']['goal']) * 100); ?>,
          'syria': <?php echo min(100, ($donationStats['syria']['raised'] / $donationStats['syria']['goal']) * 100); ?>,
          'sudan': <?php echo min(100, ($donationStats['sudan']['raised'] / $donationStats['sudan']['goal']) * 100); ?>,
          'yemen': <?php echo min(100, ($donationStats['yemen']['raised'] / $donationStats['yemen']['goal']) * 100); ?>,
          'myanmar': <?php echo min(100, ($donationStats['myanmar']['raised'] / $donationStats['myanmar']['goal']) * 100); ?>
      };
      
      // Set progress bar widths
      Object.keys(progressBars).forEach(key => {
          const element = document.getElementById(`${key}-progress`);
          if (element) {
              element.style.width = progressBars[key] + '%';
          }
      });
      
      // Initialize AOS
      AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
      });
      
      // Close notification banner when X is clicked
      const closeButtons = document.querySelectorAll('.notification-banner .close-btn, #subscription-success .close-btn');
      closeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
          const banner = this.parentElement;
          banner.style.display = 'none';
        });
      });
      
      // Handle subscription form submission via AJAX
      const subscribeForm = document.getElementById('subscribe-form');
      const subscribeMessage = document.getElementById('subscribe-message');
      
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
              subscribeMessage.innerHTML = `<p class="error-message">${data.message}</p>`;
              setTimeout(() => {
                subscribeMessage.innerHTML = '';
              }, 3000);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            subscribeMessage.innerHTML = '<p class="error-message">An error occurred. Please try again.</p>';
            setTimeout(() => {
              subscribeMessage.innerHTML = '';
            }, 3000);
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