<?php
// Start session to access session variables
session_start();

// Include database connection
require_once 'includes/db_connect.php';

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
        // Fetch latest crisis events for alerts section
        $alerts = [];
        try {
            $alertSql = "SELECT * FROM crisis_events ORDER BY event_date DESC LIMIT 4";
            $alertResult = $conn->query($alertSql);
            if ($alertResult && $alertResult->num_rows > 0) {
                while ($row = $alertResult->fetch_assoc()) {
                    $alerts[] = $row;
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching alerts: " . $e->getMessage());
        }

        // Fetch safe zones for evacuation centers
        $safeZones = [];
        try {
            $safeZonesSql = "SELECT * FROM safe_zones WHERE status != 'closed' ORDER BY capacity_current/capacity_total DESC LIMIT 5";
            $safeZonesResult = $conn->query($safeZonesSql);
            if ($safeZonesResult && $safeZonesResult->num_rows > 0) {
                while ($row = $safeZonesResult->fetch_assoc()) {
                    $safeZones[] = $row;
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching safe zones: " . $e->getMessage());
        }

        // Fetch recent distress messages - MODIFIED to include ALL messages
        $distressMessages = [];
        try {
            // Changed to show all messages including 'new' status and order by most recent first
            $distressMessagesSql = "SELECT * FROM distress_messages ORDER BY created_at DESC LIMIT 10";
            $distressMessagesResult = $conn->query($distressMessagesSql);
            if ($distressMessagesResult && $distressMessagesResult->num_rows > 0) {
                while ($row = $distressMessagesResult->fetch_assoc()) {
                    $distressMessages[] = $row;
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching distress messages: " . $e->getMessage());
        }

        // Get emergency status metrics
        try {
            $criticalAlertsSql = "SELECT COUNT(*) as count FROM crisis_events WHERE severity = 'high'";
            $warningAlertsSql = "SELECT COUNT(*) as count FROM crisis_events WHERE severity = 'medium'";
            $advisoryAlertsSql = "SELECT COUNT(*) as count FROM crisis_events WHERE severity = 'low'";
            $safeZonesCountSql = "SELECT COUNT(*) as count FROM safe_zones WHERE status = 'open'";

            $criticalAlertsCount = $conn->query($criticalAlertsSql)->fetch_assoc()['count'] ?? 0;
            $warningAlertsCount = $conn->query($warningAlertsSql)->fetch_assoc()['count'] ?? 0;
            $advisoryAlertsCount = $conn->query($advisoryAlertsSql)->fetch_assoc()['count'] ?? 0;
            $safeZonesCount = $conn->query($safeZonesCountSql)->fetch_assoc()['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error fetching emergency metrics: " . $e->getMessage());
            $criticalAlertsCount = 0;
            $warningAlertsCount = 0;
            $advisoryAlertsCount = 0;
            $safeZonesCount = 0;
        }

        // Get current emergency status data (highest severity event)
        $emergencyStatus = null;
        try {
            $emergencyStatusSql = "SELECT * FROM crisis_events WHERE severity = 'high' ORDER BY created_at DESC LIMIT 1";
            $emergencyStatusResult = $conn->query($emergencyStatusSql);
            if ($emergencyStatusResult && $emergencyStatusResult->num_rows > 0) {
                $emergencyStatus = $emergencyStatusResult->fetch_assoc();
            } else {
                // If no high severity event, get the most recent event
                $emergencyStatusSql = "SELECT * FROM crisis_events ORDER BY created_at DESC LIMIT 1";
                $emergencyStatusResult = $conn->query($emergencyStatusSql);
                if ($emergencyStatusResult && $emergencyStatusResult->num_rows > 0) {
                    $emergencyStatus = $emergencyStatusResult->fetch_assoc();
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching emergency status: " . $e->getMessage());
        }

        // Count of affected regions
        $affectedRegionsCount = 0;
        try {
            $affectedRegionsSql = "SELECT COUNT(DISTINCT location) as count FROM crisis_events WHERE severity != 'low'";
            $affectedRegionsResult = $conn->query($affectedRegionsSql);
            if ($affectedRegionsResult) {
                $affectedRegionsCount = $affectedRegionsResult->fetch_assoc()['count'] ?? 0;
            }
        } catch (Exception $e) {
            error_log("Error fetching affected regions: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Safety Watch: Beacon Alerts</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Style for the notification banner */
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
  </style>
</head>
<body>
  <!-- Loading Screen with Animation -->
  <div id="loading-screen">
    <div class="loader-container">
      <img id="loading-image" src="images/heart-icon.svg" alt="Loading...">
      <div id="progress-container">
        <div id="progress-bar"></div>
      </div>
      <p>Loading Safety Information...</p>
    </div>
  </div>

  <!-- Enhanced Navbar with Smooth Transitions -->
  <nav class="navbar">
    <div class="logo-title">
      <img src="images/alert-bell.svg" alt="Safety Watch Logo" class="logo-image">
      <div class="title">Safety Watch <span class="sub-title">Beacon Alerts</span></div>
    </div>
    <div class="navbar-links">
      <a href="#" class="nav-link active"><i class="fas fa-home"></i> Home</a>
      <a href="pages/donate.php" class="nav-link"><i class="fas fa-heart"></i> Donate</a>
      <a href="pages/lost.php" class="nav-link"><i class="fas fa-users"></i> BeaconHub</a>
      <a href="pages/map.php" class="nav-link"><i class="fas fa-map-marked-alt"></i> Crisis Map</a>
    </div>
    <div class="hamburger-menu">
      <div class="bar"></div>
      <div class="bar"></div>
      <div class="bar"></div>
    </div>
  </nav>

  <!-- Hero Section with Parallax Effect -->
  <section class="hero-section">
    <div class="hero-content" data-aos="fade-up">
      <h1>Stay Safe, Stay Informed</h1>
      <p>Real-time emergency alerts and critical information for conflict zones</p>
      <div class="hero-buttons">
        <a href="#emergency-status" class="btn primary-btn">Check Emergency Status</a>
        <a href="#distress-section" class="btn secondary-btn">Report Distress</a>
      </div>
    </div>
    <div class="hero-overlay"></div>
  </section>

  <!-- Emergency Status Section with Fade-in Animation -->
  <section id="emergency-status" class="emergency-status-section" data-aos="fade-up">
    <div class="container">
      <div class="section-header">
        <h2>Current Emergency Status</h2>
        <div class="alert-level">Level: <span class="high-alert"><?php echo escapeOutput($emergencyStatus['severity'] ?? 'N/A'); ?></span> - <?php echo escapeOutput($emergencyStatus['location'] ?? 'No active conflict zone'); ?></div>
      </div>
      <div class="description">
        <p><?php echo escapeOutput($emergencyStatus['description'] ?? 'No current emergency status available.'); ?></p>
      </div>
      <div class="info-box">
        <div class="info-item" data-aos="zoom-in" data-aos-delay="100">
          <div class="info-icon"><i class="fas fa-clock"></i></div>
          <h3>Curfew Hours</h3>
          <p><?php echo escapeOutput($emergencyStatus['curfew_hours'] ?? 'N/A'); ?></p>
        </div>
        <div class="info-item" data-aos="zoom-in" data-aos-delay="200">
          <div class="info-icon"><i class="fas fa-exclamation-triangle"></i></div>
          <h3>Alert Level</h3>
          <p class="high-alert"><?php echo escapeOutput($emergencyStatus['severity'] ?? 'N/A'); ?></p>
        </div>
        <div class="info-item" data-aos="zoom-in" data-aos-delay="300">
          <div class="info-icon"><i class="fas fa-map-marked-alt"></i></div>
          <h3>Affected Regions</h3>
          <p><?php echo escapeOutput($affectedRegionsCount); ?></p>
        </div>
      </div>
      <div class="safety-recommendations">
        <h3><i class="fas fa-shield-alt"></i> Safety Recommendations</h3>
        <ul>
          <li>Monitor local media for updates</li>
          <li>Identify shelter locations in advance of any air alert</li>
          <li>Immediately take shelter if an air alert is announced</li>
          <li>Follow the directions of officials and first responders</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Conflict Statistics with Counter Animation -->
  <section class="statistics-section">
    <div class="container">
      <h2 data-aos="fade-up">Live Conflict Death Count (Estimated)</h2>
      <div class="counter-grid" id="counterGrid">
        <div class="counter-card" data-aos="fade-up" data-aos-delay="100">
          <div class="location">Gaza</div>
          <div class="count" data-target="34000">0</div>
          <div class="label">Deaths (Estimated)</div>
        </div>
        <div class="counter-card" data-aos="fade-up" data-aos-delay="200">
          <div class="location">Ukraine</div>
          <div class="count" data-target="300000">0</div>
          <div class="label">Deaths (Estimated)</div>
        </div>
        <div class="counter-card" data-aos="fade-up" data-aos-delay="300">
          <div class="location">Syria</div>
          <div class="count" data-target="500000">0</div>
          <div class="label">Deaths (Estimated)</div>
        </div>
        <div class="counter-card" data-aos="fade-up" data-aos-delay="400">
          <div class="location">Sudan</div>
          <div class="count" data-target="15000">0</div>
          <div class="label">Deaths (Estimated)</div>
        </div>
        <div class="counter-card" data-aos="fade-up" data-aos-delay="500">
          <div class="location">Yemen</div>
          <div class="count" data-target="377000">0</div>
          <div class="label">Deaths (Estimated)</div>
        </div>
        <div class="counter-card" data-aos="fade-up" data-aos-delay="600">
          <div class="location">Myanmar</div>
          <div class="count" data-target="31000">0</div>
          <div class="label">Deaths (Estimated)</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Interactive Map Section -->
  <section class="map-section">
    <div class="container">
      <h2 data-aos="fade-up">Conflict Zone Map</h2>
      <div class="map-container" data-aos="zoom-in">
        <iframe src="pages/map.php" width="100%" height="600px" style="border: none;"></iframe>
      </div>
    </div>
  </section>

  <!-- Distress Reporting Section with Animation -->
  <section id="distress-section" class="distress-section">
    <div class="container">
      <div class="form-container">
        <section class="form-section" data-aos="fade-right">
          <h2><i class="fas fa-exclamation-triangle"></i> Report a Distress Situation</h2>
          
          <!-- Display success/error messages from session -->
          <?php if (isset($_SESSION['distress_submitted']) && $_SESSION['distress_submitted']): ?>
            <div class="notification-banner success">
              <span><?php echo escapeOutput($_SESSION['distress_message']); ?></span>
              <span class="close-btn">&times;</span>
            </div>
            <?php 
              // Clear the session variables
              unset($_SESSION['distress_submitted']);
              unset($_SESSION['distress_message']);
            ?>
          <?php endif; ?>
          
          <?php if (isset($_SESSION['distress_error'])): ?>
            <div class="notification-banner error">
              <span><?php echo escapeOutput($_SESSION['distress_error']); ?></span>
              <span class="close-btn">&times;</span>
            </div>
            <?php 
              // Clear the session variable
              unset($_SESSION['distress_error']);
            ?>
          <?php endif; ?>
          
          <!-- Added action and method attributes -->
          <form id="distressForm" action="includes/process_distress.php" method="POST">
            <div class="form-group">
              <input type="text" placeholder="Enter your name" required id="name" name="name" class="form-control" />
            </div>
            <div class="form-group">
              <input type="text" placeholder="Phone or email for follow-up" id="contact" name="contact" class="form-control" />
            </div>
            <div class="form-group">
              <input type="text" placeholder="Specify your location" required id="location" name="location" class="form-control" />
            </div>
            <div class="form-group">
              <select id="type" name="type" class="form-control">
                <option value="" disabled selected>Select Emergency Type</option>
                <option value="Medical">Medical</option>
                <option value="Food">Food</option>
                <option value="Security">Security Threat</option>
              </select>
            </div>
            <div class="form-group">
              <select id="severity" name="severity" required class="form-control">
                <option value="" disabled selected>Select Severity</option>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
              </select>
            </div>
            <div class="form-group">
              <textarea placeholder="Describe your situation in detail" required id="message" name="message" class="form-control"></textarea>
            </div>
            <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Send Distress Message</button>
          </form>
        </section>
  
        <section class="messages-section" data-aos="fade-left">
          <h2><i class="fas fa-comments"></i> Recent Distress Messages</h2>
          <div id="messagesContainer" class="messages">
            <?php if (empty($distressMessages)): ?>
              <div class="message-box">
                <h3>No messages yet</h3>
                <p>Be the first to report a distress situation.</p>
              </div>
            <?php else: ?>
              <?php foreach ($distressMessages as $message): ?>
                <div class="message-box">
                  <h3><?php echo escapeOutput($message['name']); ?> 
                    <span class="message-type <?php echo strtolower(escapeOutput($message['message_type'])); ?>">
                      <?php echo escapeOutput($message['message_type']); ?>
                    </span>
                  </h3>
                  <p><?php echo escapeOutput($message['message']); ?></p>
                  <span class="meta">
                    <?php echo escapeOutput($message['location']); ?> • 
                    <span class="severity <?php echo strtolower(escapeOutput($message['severity'])); ?>">
                      <?php echo escapeOutput($message['severity']); ?>
                    </span> • 
                    <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                  </span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </div>
  </section>

  <!-- Latest Alerts Section with Slide-in Animation -->
  <section id="latest-alerts" class="latest-alerts-section">
    <div class="container">
      <h2 data-aos="fade-up"><i class="fas fa-bell"></i> Latest Alerts</h2>

      <div class="alerts-container">
        <?php foreach ($alerts as $alert): ?>
          <div class="alert-box <?php echo escapeOutput($alert['severity']); ?>" data-aos="fade-up" data-aos-delay="100">
            <div class="alert-header">
              <h3><?php echo escapeOutput($alert['title']); ?></h3>
              <span class="tag red"><?php echo escapeOutput($alert['severity']); ?></span>
            </div>
            <div class="meta">
              <span><i class="fas fa-map-marker-alt"></i> <?php echo escapeOutput($alert['location']); ?></span> •
              <span><i class="far fa-clock"></i> <?php echo escapeOutput($alert['event_date']); ?></span>
            </div>
            <p><?php echo escapeOutput($alert['description']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Help Section with Fade Animation -->
  <section class="help-section">
    <div class="container">
      <div class="help-content" data-aos="fade-up">
        <h2>Help Those Affected</h2>
        <p>Your donation helps provide:</p>
        <div class="help-items">
          <div class="help-item" data-aos="fade-up" data-aos-delay="100">
            <i class="fas fa-home"></i>
            <p>Emergency shelter and safe housing for displaced families</p>
          </div>
          <div class="help-item" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-utensils"></i>
            <p>Food, clean water, and essential supplies</p>
          </div>
          <div class="help-item" data-aos="fade-up" data-aos-delay="300">
            <i class="fas fa-hospital"></i>
            <p>Medical care and emergency health services</p>
          </div>
          <div class="help-item" data-aos="fade-up" data-aos-delay="400">
            <i class="fas fa-child"></i>
            <p>Support for children who have lost parents or caregivers</p>
          </div>
        </div>
        <a href="pages/donate.php" class="btn primary-btn donate-btn" data-aos="zoom-in">Donate Now</a>
      </div>
    </div>
  </section>

  <!-- Emergency Resources Section (Improved) -->
  <section class="emergency-resources-section">
    <div class="container">
      <h2 data-aos="fade-up"><i class="fas fa-first-aid"></i> Emergency Resources</h2>
      
      <!-- Emergency Status Grid (Improved UI) -->
      <div class="alerts-status-grid" data-aos="fade-up" data-aos-delay="100">
        <div class="alert-status-box">
          <div>
            <p>Critical Alerts</p>
            <h2><?php echo escapeOutput($criticalAlertsCount); ?></h2>
          </div>
          <div class="status-icon critical-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
        </div>
        <div class="alert-status-box">
          <div>
            <p>Warning Alerts</p>
            <h2><?php echo escapeOutput($warningAlertsCount); ?></h2>
          </div>
          <div class="status-icon warning-icon">
            <i class="fas fa-bell"></i>
          </div>
        </div>
        <div class="alert-status-box">
          <div>
            <p>Advisory Alerts</p>
            <h2><?php echo escapeOutput($advisoryAlertsCount); ?></h2>
          </div>
          <div class="status-icon advisory-icon">
            <i class="fas fa-info-circle"></i>
          </div>
        </div>
        <div class="alert-status-box">
          <div>
            <p>Safe Zones</p>
            <h2><?php echo escapeOutput($safeZonesCount); ?></h2>
          </div>
          <div class="status-icon safe-icon">
            <i class="fas fa-check-circle"></i>
          </div>
        </div>
      </div>
      
      <!-- Accordion Resources (Improved) -->
      <div class="accordion-container">
        <!-- Emergency Contact Numbers -->
        <div class="accordion-item" data-aos="fade-up" data-aos-delay="100">
          <div class="accordion-header">
            <div class="header-content">
              <i class="fas fa-phone-alt"></i>
              <h3>Emergency Contact Numbers</h3>
            </div>
            <div class="accordion-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="accordion-content">
            <div class="accordion-body">
              <ul class="resource-list">
                <li><strong>Emergency Coordination Center:</strong> 1-800-CRISIS-24 <span class="availability">Available 24/7</span></li>
                <li><strong>Medical Emergency:</strong> 112 or 911</li>
                <li><strong>Evacuation Assistance:</strong> 1-800-EVAC-NOW</li>
                <li><strong>Missing Persons Hotline:</strong> 1-800-FIND-HELP</li>
                <li><strong>Humanitarian Aid Coordination:</strong> +1-212-555-7890</li>
                <li><strong>Mental Health Crisis Line:</strong> 1-800-TALK-NOW <span class="availability">Available 24/7</span></li>
              </ul>
              <div class="resource-note">
                <i class="fas fa-info-circle"></i>
                <p>Save these numbers on your phone and keep a printed copy in your emergency kit.</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Evacuation Centers -->
        <div class="accordion-item" data-aos="fade-up" data-aos-delay="200">
          <div class="accordion-header">
            <div class="header-content">
              <i class="fas fa-building"></i>
              <h3>Evacuation Centers</h3>
            </div>
            <div class="accordion-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="accordion-content">
            <div class="accordion-body">
              <div class="evacuation-centers">
                <?php foreach ($safeZones as $zone): ?>
                  <div class="center-item">
                    <div class="center-header">
                      <h4><?php echo escapeOutput($zone['name']); ?></h4>
                      <span class="status <?php echo escapeOutput($zone['status']); ?>"><?php echo escapeOutput($zone['status']); ?></span>
                    </div>
                    <p class="center-address"><?php echo escapeOutput($zone['address']); ?></p>
                    <div class="center-capacity">
                      <span class="capacity-label">Capacity:</span>
                      <div class="capacity-bar-container">
                        <div class="capacity-bar" style="width: <?php echo escapeOutput(($zone['capacity_current'] / $zone['capacity_total']) * 100); ?>%"></div>
                      </div>
                      <span class="capacity-text"><?php echo escapeOutput($zone['capacity_current']); ?>/<?php echo escapeOutput($zone['capacity_total']); ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              
              <div class="resource-note">
                <i class="fas fa-info-circle"></i>
                <p>All evacuation centers provide food, water, medical care, and temporary shelter. For special needs assistance, please call 1-800-EVAC-NOW before arrival.</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Emergency Supply Checklist -->
        <div class="accordion-item" data-aos="fade-up" data-aos-delay="300">
          <div class="accordion-header">
            <div class="header-content">
              <i class="fas fa-list-ul"></i>
              <h3>Emergency Supply Checklist</h3>
            </div>
            <div class="accordion-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="accordion-content">
            <div class="accordion-body">
              <div class="supply-categories">
                <div class="supply-category">
                  <h4><i class="fas fa-tint"></i> Water & Food</h4>
                  <ul class="check-list">
                    <li>Water: one gallon per person per day for at least three days</li>
                    <li>Non-perishable food: at least a three-day supply</li>
                    <li>Manual can opener</li>
                    <li>Mess kits, paper plates, cups, utensils</li>
                  </ul>
                </div>
                
                <div class="supply-category">
                  <h4><i class="fas fa-first-aid"></i> Health & Safety</h4>
                  <ul class="check-list">
                    <li>First aid kit</li>
                    <li>Prescription medications</li>
                    <li>Dust mask to filter contaminated air</li>
                    <li>Hand sanitizer and disinfectant wipes</li>
                    <li>Plastic sheeting and duct tape (to shelter in place)</li>
                  </ul>
                </div>
                
                <div class="supply-category">
                  <h4><i class="fas fa-tools"></i> Tools & Equipment</h4>
                  <ul class="check-list">
                    <li>Battery-powered or hand-crank radio</li>
                    <li>Flashlight and extra batteries</li>
                    <li>Whistle (to signal for help)</li>
                    <li>Wrench or pliers (to turn off utilities)</li>
                    <li>Local maps</li>
                    <li>Cell phone with chargers and backup battery</li>
                  </ul>
                </div>
                
                <div class="supply-category">
                  <h4><i class="fas fa-box-open"></i> Personal Items</h4>
                  <ul class="check-list">
                    <li>Change of clothing and sturdy shoes</li>
                    <li>Sleeping bags or warm blankets</li>
                    <li>Personal hygiene items</li>
                    <li>Important documents (ID, insurance, bank records) in waterproof container</li>
                    <li>Cash in small denominations</li>
                  </ul>
                </div>
              </div>
              
              <div class="resource-note">
                <i class="fas fa-exclamation-circle"></i>
                <p>Prepare these items in advance and store them in an easily accessible location. Check and refresh your supplies every six months.</p>
              </div>
              
              <a href="#" class="resources-btn">Download Printable Checklist <i class="fas fa-download"></i></a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- FAQ Section (Improved) -->
      <div class="faq-container">
        <h2 data-aos="fade-up">Frequently Asked Questions</h2>
        
        <!-- Question 1 -->
        <div class="faq-item" data-aos="fade-up" data-aos-delay="100">
          <div class="faq-question">
            <div class="question-content">
              <span class="question-number">01</span>
              <h3>How do I report a missing person?</h3>
            </div>
            <div class="question-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="faq-answer">
            <div class="faq-answer-content">
              <p>To report a missing person during an emergency situation, follow these steps:</p>
              <ol class="procedure-list">
                <li>Call the Missing Persons Hotline at <strong>1-800-FIND-HELP</strong> immediately.</li>
                <li>Provide the following information:
                  <ul>
                    <li>Full name of the missing person</li>
                    <li>Physical description (height, weight, age, hair color, etc.)</li>
                    <li>Recent photo (if available)</li>
                    <li>Last known location and time seen</li>
                    <li>Clothing worn when last seen</li>
                    <li>Any medical conditions or special needs</li>
                  </ul>
                </li>
                <li>File a report at your nearest emergency coordination center</li>
                <li>Register the missing person in the BeaconHub section of this website</li>
              </ol>
              
              <div class="action-links">
                <a href="pages/lost.php" class="faq-link"><i class="fas fa-users"></i> Go to BeaconHub</a>
                <a href="#" class="faq-link"><i class="fas fa-print"></i> Print Missing Person Form</a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Question 2 -->
        <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
          <div class="faq-question">
            <div class="question-content">
              <span class="question-number">02</span>
              <h3>How can I volunteer or donate supplies?</h3>
            </div>
            <div class="question-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="faq-answer">
            <div class="faq-answer-content">
              <p>There are several ways you can help those affected by the crisis:</p>
              
              <div class="support-options">
                <div class="support-option">
                  <i class="fas fa-hand-holding-usd"></i>
                  <h4>Donate Funds</h4>
                  <p>Financial contributions allow relief organizations to purchase exactly what is needed and support the local economy.</p>
                  <a href="pages/donate.php" class="option-link">Make a Donation</a>
                </div>
                
                <div class="support-option">
                  <i class="fas fa-hands-helping"></i>
                  <h4>Volunteer Time</h4>
                  <p>Register as a volunteer through our online portal or call the Humanitarian Aid Coordination number at +1-212-555-7890.</p>
                  <a href="#" class="option-link">Register as Volunteer</a>
                </div>
                
                <div class="support-option">
                  <i class="fas fa-box-open"></i>
                  <h4>Donate Supplies</h4>
                  <p>Contact the nearest evacuation center to inquire about specific supply needs before bringing items.</p>
                  <a href="#" class="option-link">View Current Needs</a>
                </div>
              </div>
              
              <div class="faq-note">
                <i class="fas fa-info-circle"></i>
                <p>All volunteers must register and receive proper training before being deployed to affected areas.</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Question 3 -->
        <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
          <div class="faq-question">
            <div class="question-content">
              <span class="question-number">03</span>
              <h3>What should I do during an air raid alert?</h3>
            </div>
            <div class="question-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="faq-answer">
            <div class="faq-answer-content">
              <div class="alert-procedure">
                <div class="procedure-timeline">
                  <div class="timeline-item">
                    <div class="timeline-icon">
                      <i class="fas fa-running"></i>
                    </div>
                    <div class="timeline-content">
                      <h4>Immediate Action</h4>
                      <p>Seek shelter in the nearest designated bomb shelter or interior room without windows.</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item">
                    <div class="timeline-icon">
                      <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="timeline-content">
                      <h4>Protective Measures</h4>
                      <p>Stay away from windows and exterior walls. Position yourself near load-bearing walls if possible.</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item">
                    <div class="timeline-icon">
                      <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="timeline-content">
                      <h4>Essential Items</h4>
                      <p>If time permits, grab your emergency kit including water, medicine, documents, and charged phone.</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item">
                    <div class="timeline-icon">
                      <i class="fas fa-headset"></i>
                    </div>
                    <div class="timeline-content">
                      <h4>Follow Instructions</h4>
                      <p>Listen to official communications and follow directions from emergency services personnel.</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item">
                    <div class="timeline-icon">
                      <i class="fas fa-hourglass-end"></i>
                    </div>
                    <div class="timeline-content">
                      <h4>Wait for All-Clear</h4>
                      <p>Remain in shelter until authorities announce the all-clear signal.</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="faq-callout">
                <i class="fas fa-mobile-alt"></i>
                <div>
                  <h4>Stay Informed</h4>
                  <p>Download the Safety Watch mobile app to receive real-time alerts directly on your device.</p>
                  <div class="app-buttons">
                    <a href="#" class="app-btn"><i class="fab fa-apple"></i> App Store</a>
                    <a href="#" class="app-btn"><i class="fab fa-google-play"></i> Google Play</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Question 4 -->
        <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
          <div class="faq-question">
            <div class="question-content">
              <span class="question-number">04</span>
              <h3>How can I get emergency financial assistance?</h3>
            </div>
            <div class="question-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="faq-answer">
            <div class="faq-answer-content">
              <p>Several options are available for emergency financial assistance:</p>
              
              <div class="assistance-options">
                <div class="assistance-option">
                  <div class="assistance-icon">
                    <i class="fas fa-landmark"></i>
                  </div>
                  <div class="assistance-details">
                    <h4>Government Aid</h4>
                    <p>Register at your local evacuation center or online through the government emergency portal.</p>
                    <ul class="document-list">
                      <li>Valid identification</li>
                      <li>Proof of residence in affected area</li>
                      <li>Documentation of loss/damage (if applicable)</li>
                    </ul>
                    <a href="#" class="assistance-link">Government Aid Portal <i class="fas fa-external-link-alt"></i></a>
                  </div>
                </div>
                
                <div class="assistance-option">
                  <div class="assistance-icon">
                    <i class="fas fa-heart"></i>
                  </div>
                  <div class="assistance-details">
                    <h4>NGO Support</h4>
                    <p>Contact organizations like Red Cross, Mercy Corps, or local aid organizations for direct assistance.</p>
                    <div class="ngo-contacts">
                      <a href="#" class="ngo-link">Red Cross <i class="fas fa-phone"></i></a>
                      <a href="#" class="ngo-link">Mercy Corps <i class="fas fa-phone"></i></a>
                      <a href="#" class="ngo-link">Local Aid Directory <i class="fas fa-list"></i></a>
                    </div>
                  </div>
                </div>
                
                <div class="assistance-option">
                  <div class="assistance-icon">
                    <i class="fas fa-money-bill-wave"></i>
                  </div>
                  <div class="assistance-details">
                    <h4>Emergency Loans</h4>
                    <p>Some financial institutions offer special emergency loans with reduced interest rates for those affected by the crisis.</p>
                    <a href="#" class="assistance-link">Emergency Financial Programs <i class="fas fa-external-link-alt"></i></a>
                  </div>
                </div>
              </div>
              
              <div class="assistance-hotline">
                <i class="fas fa-phone-volume"></i>
                <div>
                  <h4>Financial Assistance Hotline</h4>
                  <p>Call 1-800-CRISIS-AID for guidance on available financial help programs</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Question 5 (New) -->
        <div class="faq-item" data-aos="fade-up" data-aos-delay="500">
          <div class="faq-question">
            <div class="question-content">
              <span class="question-number">05</span>
              <h3>How do I locate family members displaced by conflict?</h3>
            </div>
            <div class="question-icon">
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
          <div class="faq-answer">
            <div class="faq-answer-content">
              <p>Finding family members during a crisis can be challenging but several resources are available:</p>
              
              <div class="resource-columns">
                <div class="resource-column">
                  <h4><i class="fas fa-database"></i> Official Registries</h4>
                  <ul class="resource-checklist">
                    <li>Check the BeaconHub missing persons database on this website</li>
                    <li>Contact the Red Cross Family Tracing service</li>
                    <li>Register at evacuation centers and shelters</li>
                    <li>Check with hospitals in the area</li>
                  </ul>
                </div>
                
                <div class="resource-column">
                  <h4><i class="fas fa-laptop"></i> Online Resources</h4>
                  <ul class="resource-links">
                    <li><a href="pages/lost.php">BeaconHub Missing Persons Platform</a></li>
                    <li><a href="#">International Committee of the Red Cross</a></li>
                    <li><a href="#">UNHCR Refugee Tracing Service</a></li>
                    <li><a href="#">Social Media Crisis Response Centers</a></li>
                  </ul>
                </div>
              </div>
              
              <div class="reunion-tips">
                <h4>Tips for Successful Family Reunification</h4>
                <ul>
                  <li>Provide as much detailed information as possible about the missing person</li>
                  <li>Upload a recent photo if available</li>
                  <li>List all possible locations where the person might seek shelter</li>
                  <li>Include contact information where you can be reached</li>
                  <li>Check registries regularly as new information is added frequently</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

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
        
        <!-- Subscription success message -->
        <?php if (isset($_SESSION['subscription_success']) && $_SESSION['subscription_success']): ?>
          <div class="notification-banner success subscription-success">
            <span><?php echo escapeOutput($_SESSION['subscription_message']); ?></span>
            <span class="close-btn">&times;</span>
          </div>
          <?php 
            // Clear the session variables
            unset($_SESSION['subscription_success']);
            unset($_SESSION['subscription_message']);
          ?>
        <?php endif; ?>
        
        <form class="subscribe-form" action="includes/process_subscription.php" method="POST">
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
  <script src="js/script.js"></script>
  <script src="js/counter.js"></script>
  <script src="js/accordion.js"></script>
  <script>
    // Close notification banner when X is clicked
    document.addEventListener('DOMContentLoaded', function() {
      const closeButtons = document.querySelectorAll('.notification-banner .close-btn');
      closeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
          const banner = this.parentElement;
          banner.style.display = 'none';
        });
      });
    });
  </script>
</body>
</html>