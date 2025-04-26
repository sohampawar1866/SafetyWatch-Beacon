// Common JavaScript functionality for all pages
document.addEventListener('DOMContentLoaded', function() {
  // Initialize AOS (Animate On Scroll)
  AOS.init({
    duration: 800,
    easing: 'ease',
    once: true,
    offset: 100
  });
  
  // Handle loading screen
  setTimeout(function() {
    let loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
      loadingScreen.style.opacity = '0';
      setTimeout(function() {
        loadingScreen.style.display = 'none';
      }, 500);
    }
  }, 1500);
  
  // Mobile navigation toggle
  const hamburger = document.querySelector('.hamburger-menu');
  const navLinks = document.querySelector('.navbar-links');
  
  if (hamburger) {
    hamburger.addEventListener('click', function() {
      navLinks.classList.toggle('active');
      hamburger.classList.toggle('active');
    });
  }
  
  // Handle distress form submission with direct form submission
  const distressForm = document.getElementById('distressForm');
  if (distressForm) {
    console.log("Distress form found, attaching submit handler");
    distressForm.addEventListener('submit', function(e) {
      // Use standard form submission instead of AJAX
      // The form already has action="includes/process_distress.php" method="POST"
      console.log("Form submitted");
      return true; // Allow the form to submit normally
    });
  }
  
  // Handle subscription form
  const subscribeForm = document.querySelector('.subscribe-form');
  if (subscribeForm) {
    subscribeForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const email = this.querySelector('input[type="email"]').value;
      
      // Create FormData object
      const formData = new FormData();
      formData.append('email', email);
      
      // Send AJAX request
      fetch('includes/process_subscription.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        // Create notification
        showNotification(data.message, data.success ? 'success' : 'error');
        
        // Clear form if successful
        if (data.success) {
          subscribeForm.reset();
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('There was a problem connecting to the server.', 'error');
      });
    });
  }
  
  // Notification system
  function showNotification(message, type = 'info') {
    const notificationElement = document.createElement('div');
    notificationElement.className = `notification ${type}`;
    notificationElement.innerHTML = `
      <div class="notification-icon">
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-circle' : 
                     'fa-info-circle'}"></i>
      </div>
      <div class="notification-content">
        <p>${message}</p>
      </div>
      <div class="notification-close">
        <i class="fas fa-times"></i>
      </div>
    `;
    
    // Add notification to body
    document.body.appendChild(notificationElement);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
      notificationElement.style.opacity = '0';
      setTimeout(() => {
        document.body.removeChild(notificationElement);
      }, 500);
    }, 5000);
    
    // Add close button functionality
    const closeBtn = notificationElement.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
      notificationElement.style.opacity = '0';
      setTimeout(() => {
        document.body.removeChild(notificationElement);
      }, 500);
    });
  }

  // Other existing functions and code...
});