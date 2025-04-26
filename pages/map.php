<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emergency Map - Safety Watch: Beacon Alerts</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <style>
    /* Page-specific styles */
    .map-container {
      position: relative;
      width: 100%;
      height: calc(100vh - 80px);
      background-color: #f5f5f5;
      overflow: hidden;
    }
    
    #map {
      width: 100%;
      height: 100%;
      z-index: 1;
    }
    
    .map-sidebar {
      position: absolute;
      top: 20px;
      left: 20px;
      width: 320px;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      z-index: 2;
      transition: transform 0.3s ease;
      max-height: calc(100% - 40px);
      display: flex;
      flex-direction: column;
    }
    
    .map-sidebar.collapsed {
      transform: translateX(-290px);
    }
    
    .sidebar-toggle {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      width: 24px;
      height: 50px;
      background-color: white;
      border: 1px solid #e5e7eb;
      border-radius: 0 5px 5px 0;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      z-index: 3;
    }
    
    .sidebar-toggle i {
      font-size: 0.9rem;
      color: #6b7280;
      transition: transform 0.3s ease;
    }
    
    .map-sidebar.collapsed .sidebar-toggle i {
      transform: rotate(180deg);
    }
    
    .sidebar-header {
      padding: 15px 20px;
      background-color: #1f2937;
      color: white;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .sidebar-header h3 {
      margin: 0;
      font-size: 1.1rem;
      font-weight: 600;
    }
    
    .sidebar-tabs {
      display: flex;
      background-color: #f3f4f6;
    }
    
    .sidebar-tab {
      flex: 1;
      padding: 10px;
      text-align: center;
      font-size: 0.9rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      color: #6b7280;
      border-bottom: 2px solid transparent;
    }
    
    .sidebar-tab.active {
      background-color: white;
      color: #e63946;
      border-bottom-color: #e63946;
    }
    
    .sidebar-content {
      flex: 1;
      overflow-y: auto;
      padding: 15px;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
      animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(5px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .filter-controls {
      background-color: #f9fafb;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    
    .filter-row {
      margin-bottom: 12px;
    }
    
    .filter-row:last-child {
      margin-bottom: 0;
    }
    
    .filter-row label {
      display: block;
      font-size: 0.85rem;
      font-weight: 500;
      margin-bottom: 5px;
      color: #4b5563;
    }
    
    .filter-select {
      width: 100%;
      padding: 8px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 0.9rem;
      color: #4b5563;
    }
    
    .event-list {
      margin-top: 15px;
    }
    
    .event-item {
      background-color: white;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .event-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .event-title {
      font-size: 1rem;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .event-title i {
      color: #e63946;
    }
    
    .event-severity {
      font-size: 0.8rem;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 12px;
      display: inline-block;
      margin-bottom: 8px;
    }
    
    .severity-high { 
      background-color: #fee2e2; 
      color: #ef4444;
    }
    
    .severity-medium { 
      background-color: #ffedd5; 
      color: #f59e0b;
    }
    
    .severity-low { 
      background-color: #d1fae5; 
      color: #10b981;
    }
    
    .event-details {
      font-size: 0.9rem;
      color: #4b5563;
      margin-bottom: 8px;
      line-height: 1.5;
    }
    
    .event-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.8rem;
      color: #6b7280;
    }
    
    .map-legend {
      position: absolute;
      bottom: 20px;
      left: 20px;
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 10px 15px;
      z-index: 2;
      max-width: 320px;
    }
    
    .legend-title {
      font-size: 0.9rem;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .legend-items {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 8px;
    }
    
    .legend-item {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.8rem;
      color: #4b5563;
    }
    
    .legend-color {
      width: 12px;
      height: 12px;
      border-radius: 3px;
    }
    
    .legend-color.high { background-color: #ef4444; }
    .legend-color.medium { background-color: #f59e0b; }
    .legend-color.low { background-color: #10b981; }
    .legend-color.shelter { background-color: #3b82f6; }
    .legend-color.medical { background-color: #8b5cf6; }
    .legend-color.evacuation { background-color: #ec4899; }
    
    .map-controls {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 10px;
      z-index: 2;
      display: flex;
      gap: 8px;
    }
    
    .map-button {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: white;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
      color: #4b5563;
    }
    
    .map-button:hover {
      background-color: #f9fafb;
      color: #e63946;
    }
    
    .map-button.active {
      background-color: #e63946;
      color: white;
      border-color: #e63946;
    }
    
    .map-button i {
      font-size: 1.2rem;
    }
    
    .popup-custom {
      max-width: 300px;
    }
    
    .popup-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 5px;
    }
    
    .popup-header i {
      color: #e63946;
      font-size: 1.1rem;
    }
    
    .popup-title {
      font-weight: 600;
      color: #1f2937;
      font-size: 1rem;
      margin: 0;
    }
    
    .popup-description {
      font-size: 0.9rem;
      color: #4b5563;
      margin-bottom: 10px;
      line-height: 1.5;
    }
    
    .popup-meta {
      font-size: 0.8rem;
      color: #6b7280;
      display: flex;
      flex-direction: column;
      gap: 3px;
    }
    
    .popup-action {
      display: block;
      text-align: center;
      background-color: #e63946;
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.9rem;
      font-weight: 500;
      margin-top: 10px;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    
    .popup-action:hover {
      background-color: #c1121f;
    }
    
    /* Safe zones tab */
    .safe-zone {
      padding: 15px;
      border-radius: 8px;
      background-color: #f0f9ff;
      border: 1px solid #e0f2fe;
      margin-bottom: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .safe-zone:hover {
      transform: translateY(-2px);
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .safe-zone-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }
    
    .safe-zone-title {
      font-weight: 600;
      color: #1f2937;
      font-size: 1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .safe-zone-title i {
      color: #3b82f6;
    }
    
    .safe-zone-status {
      font-size: 0.8rem;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 12px;
      background-color: #d1fae5;
      color: #10b981;
    }
    
    .safe-zone-info {
      font-size: 0.9rem;
      color: #4b5563;
      margin-bottom: 8px;
    }
    
    .safe-zone-capacity {
      font-size: 0.85rem;
      color: #6b7280;
      margin-bottom: 8px;
    }
    
    .capacity-bar {
      height: 6px;
      background-color: #e5e7eb;
      border-radius: 3px;
      overflow: hidden;
      margin-top: 5px;
    }
    
    .capacity-fill {
      height: 100%;
      background-color: #3b82f6;
      border-radius: 3px;
      transition: width 0.3s ease;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
      .map-sidebar {
        width: 280px;
      }
      
      .map-sidebar.collapsed {
        transform: translateX(-250px);
      }
      
      .map-legend {
        bottom: 60px;
        left: 10px;
        max-width: 260px;
      }
      
      .legend-items {
        grid-template-columns: 1fr;
      }
      
      .map-controls {
        top: 10px;
        right: 10px;
        padding: 5px;
      }
      
      .map-button {
        width: 35px;
        height: 35px;
      }
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
      <p>Loading Map Data...</p>
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
      <a href="lost.php" class="nav-link"><i class="fas fa-users"></i> BeaconHub</a>
      <a href="map.php" class="nav-link active"><i class="fas fa-map-marked-alt"></i> Crisis Map</a>
    </div>
    <div class="hamburger-menu">
      <div class="bar"></div>
      <div class="bar"></div>
      <div class="bar"></div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="map-container">
    <!-- Map Sidebar -->
    <div class="map-sidebar">
      <div class="sidebar-toggle">
        <i class="fas fa-chevron-right"></i>
      </div>
      
      <div class="sidebar-header">
        <i class="fas fa-layer-group"></i>
        <h3>Emergency Map Layers</h3>
      </div>
      
      <div class="sidebar-tabs">
        <div class="sidebar-tab active" data-tab="events">Events</div>
        <div class="sidebar-tab" data-tab="safe-zones">Safe Zones</div>
        <div class="sidebar-tab" data-tab="routes">Evacuation</div>
      </div>
      
      <div class="sidebar-content">
        <!-- Events Tab -->
        <div class="tab-content active" id="events-tab">
          <div class="filter-controls">
            <div class="filter-row">
              <label for="eventType">Event Type</label>
              <select id="eventType" class="filter-select">
                <option value="all">All Types</option>
                <option value="conflict">Armed Conflict</option>
                <option value="natural">Natural Disaster</option>
                <option value="humanitarian">Humanitarian Crisis</option>
                <option value="health">Health Emergency</option>
              </select>
            </div>
            <div class="filter-row">
              <label for="eventSeverity">Severity</label>
              <select id="eventSeverity" class="filter-select">
                <option value="all">All Severities</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
            </div>
            <div class="filter-row">
              <label for="eventDate">Date Range</label>
              <select id="eventDate" class="filter-select">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="week">Past Week</option>
                <option value="month">Past Month</option>
              </select>
            </div>
          </div>
          
          <div class="event-list">
            <div class="event-item" data-id="1">
              <div class="event-title">
                <i class="fas fa-exclamation-triangle"></i>
                Active Conflict Zone
              </div>
              <div class="event-severity severity-high">High Severity</div>
              <div class="event-details">Heavy artillery reported in northern districts. Civilians advised to avoid the area.</div>
              <div class="event-meta">
                <div>Kyiv, Ukraine</div>
                <div>April 23, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="2">
              <div class="event-title">
                <i class="fas fa-house-damage"></i>
                Infrastructure Damage
              </div>
              <div class="event-severity severity-medium">Medium Severity</div>
              <div class="event-details">Multiple buildings damaged in recent shelling. Limited access to water and electricity.</div>
              <div class="event-meta">
                <div>Gaza City</div>
                <div>April 22, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="3">
              <div class="event-title">
                <i class="fas fa-medkit"></i>
                Medical Emergency
              </div>
              <div class="event-severity severity-high">High Severity</div>
              <div class="event-details">Shortage of medical supplies in central hospital. Urgent need for blood donors.</div>
              <div class="event-meta">
                <div>Damascus, Syria</div>
                <div>April 20, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="4">
              <div class="event-title">
                <i class="fas fa-water"></i>
                Drinking Water Shortage
              </div>
              <div class="event-severity severity-medium">Medium Severity</div>
              <div class="event-details">Limited access to clean drinking water in eastern districts. Distribution points set up.</div>
              <div class="event-meta">
                <div>Khartoum, Sudan</div>
                <div>April 19, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="5">
              <div class="event-title">
                <i class="fas fa-bread-slice"></i>
                Food Distribution
              </div>
              <div class="event-severity severity-low">Low Severity</div>
              <div class="event-details">Aid organizations distributing food supplies at designated points. See map for locations.</div>
              <div class="event-meta">
                <div>Sana'a, Yemen</div>
                <div>April 18, 2025</div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Safe Zones Tab -->
        <div class="tab-content" id="safe-zones-tab">
          <div class="filter-controls">
            <div class="filter-row">
              <select id="zoneCapacity" class="filter-select">
                <option value="all">All Capacities</option>
                <option value="available">Space Available</option>
                <option value="limited">Limited Space</option>
                <option value="full">Full</option>
              </select>
            </div>
          </div>
          
          <div class="safe-zones-list">
            <div class="safe-zone" data-id="sz1">
              <div class="safe-zone-header">
                <div class="safe-zone-title">
                  <i class="fas fa-house-user"></i>
                  Central Community Shelter
                </div>
                <div class="safe-zone-status">Open</div>
              </div>
              <div class="safe-zone-info">Large facility with sleeping arrangements, food, and medical services.</div>
              <div class="safe-zone-capacity">
                <span>Capacity: 65% (325/500)</span>
                <div class="capacity-bar">
                  <div class="capacity-fill" style="width: 65%"></div>
                </div>
              </div>
            </div>
            
            <div class="safe-zone" data-id="sz2">
              <div class="safe-zone-header">
                <div class="safe-zone-title">
                  <i class="fas fa-hospital"></i>
                  Eastern District Hospital
                </div>
                <div class="safe-zone-status">Open</div>
              </div>
              <div class="safe-zone-info">Emergency medical care available. Limited non-emergency services.</div>
              <div class="safe-zone-capacity">
                <span>Capacity: 85% (170/200)</span>
                <div class="capacity-bar">
                  <div class="capacity-fill" style="width: 85%"></div>
                </div>
              </div>
            </div>
            
            <div class="safe-zone" data-id="sz3">
              <div class="safe-zone-header">
                <div class="safe-zone-title">
                  <i class="fas fa-school"></i>
                  North School Complex
                </div>
                <div class="safe-zone-status">Open</div>
              </div>
              <div class="safe-zone-info">Temporary shelter with basic amenities and food distribution.</div>
              <div class="safe-zone-capacity">
                <span>Capacity: 40% (160/400)</span>
                <div class="capacity-bar">
                  <div class="capacity-fill" style="width: 40%"></div>
                </div>
              </div>
            </div>
            
            <div class="safe-zone" data-id="sz4">
              <div class="safe-zone-header">
                <div class="safe-zone-title">
                  <i class="fas fa-place-of-worship"></i>
                  Southern Community Center
                </div>
                <div class="safe-zone-status">Open</div>
              </div>
              <div class="safe-zone-info">Food, water, and essential supplies distribution center.</div>
              <div class="safe-zone-capacity">
                <span>Capacity: 25% (75/300)</span>
                <div class="capacity-bar">
                  <div class="capacity-fill" style="width: 25%"></div>
                </div>
              </div>
            </div>
            
            <div class="safe-zone" data-id="sz5">
              <div class="safe-zone-header">
                <div class="safe-zone-title">
                  <i class="fas fa-campground"></i>
                  Western Relief Camp
                </div>
                <div class="safe-zone-status">Open</div>
              </div>
              <div class="safe-zone-info">UNHCR operated facility with tents, food, water, and medical aid.</div>
              <div class="safe-zone-capacity">
                <span>Capacity: 70% (350/500)</span>
                <div class="capacity-bar">
                  <div class="capacity-fill" style="width: 70%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Evacuation Routes Tab -->
        <div class="tab-content" id="routes-tab">
          <div class="filter-controls">
            <div class="filter-row">
              <label for="routeStatus">Route Status</label>
              <select id="routeStatus" class="filter-select">
                <option value="all">All Statuses</option>
                <option value="open">Open</option>
                <option value="limited">Limited Access</option>
                <option value="closed">Closed</option>
              </select>
            </div>
            <div class="filter-row">
              <label for="routeType">Route Type</label>
              <select id="routeType" class="filter-select">
                <option value="all">All Types</option>
                <option value="vehicle">Vehicle</option>
                <option value="foot">On Foot</option>
                <option value="boat">Boat/Ferry</option>
              </select>
            </div>
          </div>
          
          <div class="event-list">
            <div class="event-item" data-id="r1">
              <div class="event-title">
                <i class="fas fa-road"></i>
                Northern Highway Corridor
              </div>
              <div class="event-severity severity-low">Open</div>
              <div class="event-details">Main evacuation route to the north. Military escort available at checkpoints.</div>
              <div class="event-meta">
                <div>Vehicle Access</div>
                <div>Updated: April 24, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="r2">
              <div class="event-title">
                <i class="fas fa-route"></i>
                Eastern Border Crossing
              </div>
              <div class="event-severity severity-medium">Limited Access</div>
              <div class="event-details">Periodic closures due to security concerns. Check status before traveling.</div>
              <div class="event-meta">
                <div>Vehicle & Foot</div>
                <div>Updated: April 23, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="r3">
              <div class="event-title">
                <i class="fas fa-shipping-fast"></i>
                Southern Relief Corridor
              </div>
              <div class="event-severity severity-low">Open</div>
              <div class="event-details">Organized transport to southern safe zones available daily. Departure at 08:00 and 14:00.</div>
              <div class="event-meta">
                <div>Organized Transport</div>
                <div>Updated: April 24, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="r4">
              <div class="event-title">
                <i class="fas fa-ferry"></i>
                Western Port Evacuation
              </div>
              <div class="event-severity severity-medium">Limited Access</div>
              <div class="event-details">Ferry services operating with limited capacity. Priority for medical emergencies.</div>
              <div class="event-meta">
                <div>Boat/Ferry</div>
                <div>Updated: April 22, 2025</div>
              </div>
            </div>
            
            <div class="event-item" data-id="r5">
              <div class="event-title">
                <i class="fas fa-walking"></i>
                Central City Safe Path
              </div>
              <div class="event-severity severity-high">Closed</div>
              <div class="event-details">Route currently blocked due to active conflict. Seek alternative paths.</div>
              <div class="event-meta">
                <div>Foot Only</div>
                <div>Updated: April 24, 2025</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Map Display Area -->
    <div id="map"></div>
    
    <!-- Map Legend -->
    <div class="map-legend">
      <div class="legend-title">
        <i class="fas fa-info-circle"></i>
        Map Legend
      </div>
      <div class="legend-items">
        <div class="legend-item">
          <div class="legend-color high"></div>
          <span>High Severity</span>
        </div>
        <div class="legend-item">
          <div class="legend-color medium"></div>
          <span>Medium Severity</span>
        </div>
        <div class="legend-item">
          <div class="legend-color low"></div>
          <span>Low Severity</span>
        </div>
        <div class="legend-item">
          <div class="legend-color shelter"></div>
          <span>Shelter</span>
        </div>
        <div class="legend-item">
          <div class="legend-color medical"></div>
          <span>Medical Aid</span>
        </div>
        <div class="legend-item">
          <div class="legend-color evacuation"></div>
          <span>Evacuation Route</span>
        </div>
      </div>
    </div>
    
    <!-- Map Controls -->
    <div class="map-controls">
      <button class="map-button" id="locateBtn" title="Find my location">
        <i class="fas fa-location-arrow"></i>
      </button>
      <button class="map-button" id="layersBtn" title="Toggle layers">
        <i class="fas fa-layer-group"></i>
      </button>
      <button class="map-button" id="fullscreenBtn" title="Toggle fullscreen">
        <i class="fas fa-expand"></i>
      </button>
      <button class="map-button" id="shareBtn" title="Share this map view">
        <i class="fas fa-share-alt"></i>
      </button>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  <script src="../js/script.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
      });
      
      // Sidebar toggle functionality
      const sidebarToggle = document.querySelector('.sidebar-toggle');
      const sidebar = document.querySelector('.map-sidebar');
      
      sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
      });
      
      // Tab functionality
      const tabButtons = document.querySelectorAll('.sidebar-tab');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabButtons.forEach(tab => {
        tab.addEventListener('click', () => {
          const tabName = tab.dataset.tab;
          
          // Deactivate all tabs
          tabButtons.forEach(btn => btn.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));
          
          // Activate selected tab
          tab.classList.add('active');
          document.getElementById(`${tabName}-tab`).classList.add('active');
        });
      });
      
      // Initialize map centered on Ukraine
      const map = L.map('map').setView([49.4871, 31.2718], 6);
      
      // Add OpenStreetMap tile layer
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
      }).addTo(map);
      
      // Custom icon for events
      const createIcon = (severity) => {
        let color = '';
        switch (severity) {
          case 'high':
            color = '#ef4444';
            break;
          case 'medium':
            color = '#f59e0b';
            break;
          case 'low':
            color = '#10b981';
            break;
          default:
            color = '#3b82f6';
        }
        
        return L.divIcon({
          className: 'custom-div-icon',
          html: `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.3);"></div>`,
          iconSize: [20, 20],
          iconAnchor: [10, 10]
        });
      };
      
      // Sample data for events (in a real application, this would come from an API)
      const events = [
        {
          id: 1,
          title: 'Active Conflict Zone',
          type: 'conflict',
          severity: 'high',
          details: 'Heavy artillery reported in northern districts. Civilians advised to avoid the area.',
          location: 'Kyiv, Ukraine',
          date: 'April 23, 2025',
          coordinates: [50.4501, 30.5234]
        },
        {
          id: 2,
          title: 'Infrastructure Damage',
          type: 'conflict',
          severity: 'medium',
          details: 'Multiple buildings damaged in recent shelling. Limited access to water and electricity.',
          location: 'Gaza City',
          date: 'April 22, 2025',
          coordinates: [31.5017, 34.4668]
        },
        {
          id: 3,
          title: 'Medical Emergency',
          type: 'humanitarian',
          severity: 'high',
          details: 'Shortage of medical supplies in central hospital. Urgent need for blood donors.',
          location: 'Damascus, Syria',
          date: 'April 20, 2025',
          coordinates: [33.5138, 36.2765]
        },
        {
          id: 4,
          title: 'Drinking Water Shortage',
          type: 'humanitarian',
          severity: 'medium',
          details: 'Limited access to clean drinking water in eastern districts. Distribution points set up.',
          location: 'Khartoum, Sudan',
          date: 'April 19, 2025',
          coordinates: [15.5007, 32.5599]
        },
        {
          id: 5,
          title: 'Food Distribution',
          type: 'humanitarian',
          severity: 'low',
          details: 'Aid organizations distributing food supplies at designated points. See map for locations.',
          location: 'Sana\'a, Yemen',
          date: 'April 18, 2025',
          coordinates: [15.3694, 44.1910]
        }
      ];
      
      // Safe zones data
      const safeZones = [
        {
          id: 'sz1',
          title: 'Central Community Shelter',
          type: 'shelter',
          status: 'open',
          details: 'Large facility with sleeping arrangements, food, and medical services.',
          capacity: 65,
          coordinates: [50.4401, 30.5134]
        },
        {
          id: 'sz2',
          title: 'Eastern District Hospital',
          type: 'medical',
          status: 'open',
          details: 'Emergency medical care available. Limited non-emergency services.',
          capacity: 85,
          coordinates: [50.4601, 30.5634]
        },
        {
          id: 'sz3',
          title: 'North School Complex',
          type: 'shelter',
          status: 'open',
          details: 'Temporary shelter with basic amenities and food distribution.',
          capacity: 40,
          coordinates: [50.4701, 30.5034]
        },
        {
          id: 'sz4',
          title: 'Southern Community Center',
          type: 'food',
          status: 'open',
          details: 'Food, water, and essential supplies distribution center.',
          capacity: 25,
          coordinates: [50.4301, 30.5334]
        },
        {
          id: 'sz5',
          title: 'Western Relief Camp',
          type: 'shelter',
          status: 'open',
          details: 'UNHCR operated facility with tents, food, water, and medical aid.',
          capacity: 70,
          coordinates: [50.4501, 30.4934]
        }
      ];
      
      // Evacuation routes data (simplified for demonstration)
      const evacuationRoutes = [
        {
          id: 'r1',
          name: 'Northern Highway Corridor',
          status: 'open',
          type: 'vehicle',
          coordinates: [
            [50.4601, 30.5234],
            [50.5201, 30.5534],
            [50.5801, 30.5834]
          ]
        },
        {
          id: 'r2',
          name: 'Eastern Border Crossing',
          status: 'limited',
          type: 'vehicle',
          coordinates: [
            [50.4501, 30.5334],
            [50.4601, 30.6134],
            [50.4701, 30.6834]
          ]
        },
        {
          id: 'r3',
          name: 'Southern Relief Corridor',
          status: 'open',
          type: 'vehicle',
          coordinates: [
            [50.4401, 30.5134],
            [50.4001, 30.5034],
            [50.3601, 30.4934]
          ]
        },
        {
          id: 'r4',
          name: 'Western Port Evacuation',
          status: 'limited',
          type: 'boat',
          coordinates: [
            [50.4501, 30.5134],
            [50.4401, 30.4634],
            [50.4301, 30.4134]
          ]
        },
        {
          id: 'r5',
          name: 'Central City Safe Path',
          status: 'closed',
          type: 'foot',
          coordinates: [
            [50.4501, 30.5234],
            [50.4451, 30.5334],
            [50.4401, 30.5434]
          ]
        }
      ];
      
      // Layer groups
      const eventsLayer = L.layerGroup().addTo(map);
      const safeZonesLayer = L.layerGroup().addTo(map);
      const evacuationRoutesLayer = L.layerGroup().addTo(map);
      
      // Add event markers to the map
      events.forEach(event => {
        L.marker(event.coordinates, { icon: createIcon(event.severity) })
          .addTo(eventsLayer)
          .bindPopup(`
            <div class="popup-custom">
              <div class="popup-header">
                <i class="fas fa-exclamation-circle"></i>
                <div class="popup-title">${event.title}</div>
              </div>
              <div class="popup-description">${event.details}</div>
              <div class="popup-meta">
                <div><strong>Location:</strong> ${event.location}</div>
                <div><strong>Date:</strong> ${event.date}</div>
              </div>
              <a href="#" class="popup-action">More Info</a>
            </div>
          `);
      });
      
      // Add safe zones to the map
      safeZones.forEach(zone => {
        L.marker(zone.coordinates, { icon: createIcon('shelter') })
          .addTo(safeZonesLayer)
          .bindPopup(`
            <div class="popup-custom">
              <div class="popup-header">
                <i class="fas fa-shield-alt"></i>
                <div class="popup-title">${zone.title}</div>
              </div>
              <div class="popup-description">${zone.details}</div>
              <div class="popup-meta">
                <div><strong>Status:</strong> ${zone.status}</div>
                <div><strong>Capacity:</strong> ${zone.capacity}%</div>
              </div>
              <a href="#" class="popup-action">More Info</a>
            </div>
          `);
      });
      
      // Add evacuation routes to the map
      evacuationRoutes.forEach(route => {
        L.polyline(route.coordinates, { color: 'blue', weight: 4 }).addTo(evacuationsLayer);
      });
    });
  </script>
</body>
</html>