/**
 * Enhanced Accordion functionality for both FAQ and Emergency Resources sections
 * This script handles the interactive behavior of accordion components
 * including smooth animations, accessibility improvements, and responsive design
 */

document.addEventListener('DOMContentLoaded', function() {
  console.log("DOM loaded - initializing enhanced accordion functionality");
  
  // Initialize FAQ section
  initFaqAccordion();
  
  // Initialize Emergency Resources accordion section
  initEmergencyAccordion();
  
  // Add keyboard accessibility for accordion elements
  addKeyboardAccessibility();
});

/**
 * Initialize FAQ accordion functionality with improved animations and interactions
 */
function initFaqAccordion() {
  const faqItems = document.querySelectorAll('.faq-item');
  const faqQuestions = document.querySelectorAll('.faq-question');
  
  console.log("FAQ items found:", faqItems.length);
  
  // Close all FAQ items initially
  faqItems.forEach(item => {
    const answer = item.querySelector('.faq-answer');
    if (answer) {
      answer.style.maxHeight = '0';
      answer.style.visibility = 'hidden';
      answer.setAttribute('aria-hidden', 'true');
    }
  });
  
  // Add click event listeners to each FAQ question
  faqQuestions.forEach(question => {
    question.addEventListener('click', function() {
      const parent = this.parentElement;
      const answer = parent.querySelector('.faq-answer');
      const isExpanded = parent.classList.contains('active');
      
      // Toggle the expanded state for accessibility
      this.setAttribute('aria-expanded', !isExpanded);
      
      // Option: Close other items (accordion behavior)
      if (!isExpanded) {
        closeAllFaqItems(parent.id);
      }
      
      // Toggle active class
      parent.classList.toggle('active');
      
      // Animate the content height
      if (parent.classList.contains('active')) {
        answer.style.visibility = 'visible';
        answer.style.maxHeight = answer.scrollHeight + 'px';
        answer.setAttribute('aria-hidden', 'false');
      } else {
        answer.style.maxHeight = '0';
        // Hide the content after animation completes
        setTimeout(() => {
          if (!parent.classList.contains('active')) {
            answer.style.visibility = 'hidden';
            answer.setAttribute('aria-hidden', 'true');
          }
        }, 500); // Match this to the CSS transition duration
      }
    });
  });
  
  // Close all FAQ items except the one with the given ID
  function closeAllFaqItems(exceptId) {
    faqItems.forEach(item => {
      if (item.id !== exceptId && item.classList.contains('active')) {
        const answer = item.querySelector('.faq-answer');
        const question = item.querySelector('.faq-question');
        
        item.classList.remove('active');
        question.setAttribute('aria-expanded', 'false');
        
        if (answer) {
          answer.style.maxHeight = '0';
          setTimeout(() => {
            answer.style.visibility = 'hidden';
            answer.setAttribute('aria-hidden', 'true');
          }, 500);
        }
      }
    });
  }
}

/**
 * Initialize Emergency Resources accordion functionality 
 * with enhanced animation effects and better UX
 */
function initEmergencyAccordion() {
  const accordionItems = document.querySelectorAll('.accordion-item');
  const accordionHeaders = document.querySelectorAll('.accordion-header');
  
  console.log("Accordion items found:", accordionItems.length);
  
  // Close all accordion items initially
  accordionItems.forEach(item => {
    const content = item.querySelector('.accordion-content');
    if (content) {
      content.style.maxHeight = '0';
      content.style.visibility = 'hidden';
      content.setAttribute('aria-hidden', 'true');
    }
    
    // Add unique IDs for ARIA if not present
    if (!item.id) {
      const randomId = 'accordion-' + Math.floor(Math.random() * 10000);
      item.id = randomId;
    }
  });
  
  // Add click event listeners to accordion headers
  accordionHeaders.forEach(header => {
    const parent = header.parentElement;
    const content = parent.querySelector('.accordion-content');
    const headerId = header.id || `header-${parent.id}`;
    const contentId = content.id || `content-${parent.id}`;
    
    // Set up ARIA attributes
    header.setAttribute('role', 'button');
    header.setAttribute('aria-expanded', 'false');
    header.setAttribute('aria-controls', contentId);
    header.id = headerId;
    
    content.setAttribute('role', 'region');
    content.setAttribute('aria-labelledby', headerId);
    content.id = contentId;
    
    // Add click event handler
    header.addEventListener('click', function() {
      const isExpanded = parent.classList.contains('active');
      
      // Update ARIA attributes for accessibility
      this.setAttribute('aria-expanded', !isExpanded);
      
      // Option: Close other items (accordion behavior)
      closeOtherAccordionItems(parent.id);
      
      // Toggle this item
      parent.classList.toggle('active');
      
      // Animate content
      if (parent.classList.contains('active')) {
        content.style.visibility = 'visible';
        content.style.maxHeight = content.scrollHeight + 'px';
        content.setAttribute('aria-hidden', 'false');
        
        // Handle dynamic content that might change height
        setTimeout(() => {
          if (parent.classList.contains('active')) {
            content.style.maxHeight = content.scrollHeight + 'px';
          }
        }, 50);
      } else {
        content.style.maxHeight = '0';
        setTimeout(() => {
          if (!parent.classList.contains('active')) {
            content.style.visibility = 'hidden';
            content.setAttribute('aria-hidden', 'true');
          }
        }, 500);
      }
    });
  });
  
  function closeOtherAccordionItems(currentId) {
    accordionItems.forEach(item => {
      if (item.id !== currentId && item.classList.contains('active')) {
        const header = item.querySelector('.accordion-header');
        const content = item.querySelector('.accordion-content');
        
        item.classList.remove('active');
        header.setAttribute('aria-expanded', 'false');
        
        if (content) {
          content.style.maxHeight = '0';
          setTimeout(() => {
            content.style.visibility = 'hidden';
            content.setAttribute('aria-hidden', 'true');
          }, 500);
        }
      }
    });
  }
}

/**
 * Add keyboard accessibility to accordion elements
 */
function addKeyboardAccessibility() {
  // Make accordion headers keyboard navigable
  const allHeaders = document.querySelectorAll('.accordion-header, .faq-question');
  
  allHeaders.forEach(header => {
    // Ensure the header is focusable
    if (!header.hasAttribute('tabindex')) {
      header.setAttribute('tabindex', '0');
    }
    
    // Handle keyboard interactions
    header.addEventListener('keydown', function(e) {
      // Activate on Enter or Space
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.click(); // Trigger the click event
      }
    });
  });
}

/**
 * Update accordion content heights when window is resized
 * (Useful for responsive designs where content might reflow)
 */
function updateAccordionHeights() {
  const activeAccordions = document.querySelectorAll('.accordion-item.active, .faq-item.active');
  
  activeAccordions.forEach(item => {
    const content = item.querySelector('.accordion-content') || item.querySelector('.faq-answer');
    if (content) {
      // Temporarily set to auto to get the true height
      const originalMaxHeight = content.style.maxHeight;
      content.style.maxHeight = 'none';
      const scrollHeight = content.scrollHeight;
      content.style.maxHeight = originalMaxHeight;
      
      // Force a reflow
      void content.offsetWidth;
      
      // Set the new height
      content.style.maxHeight = scrollHeight + 'px';
    }
  });
}

// Handle window resize to adjust accordion heights
window.addEventListener('resize', debounce(updateAccordionHeights, 250));

/**
 * Debounce function to limit how often a function runs
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Helper function to open specific accordion item by ID or index
 * Can be used for linking to specific FAQs
 */
function openAccordionItem(idOrIndex, type = 'faq') {
  let item;
  
  if (typeof idOrIndex === 'string') {
    // Find by ID
    item = document.getElementById(idOrIndex);
  } else if (typeof idOrIndex === 'number') {
    // Find by index
    const selector = type === 'faq' ? '.faq-item' : '.accordion-item';
    const items = document.querySelectorAll(selector);
    item = items[idOrIndex];
  }
  
  if (item && !item.classList.contains('active')) {
    const trigger = item.querySelector(type === 'faq' ? '.faq-question' : '.accordion-header');
    if (trigger) {
      trigger.click();
      
      // Scroll into view with smooth animation
      setTimeout(() => {
        item.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }
  }
}

// Ensure initialization also happens if page is already loaded
if (document.readyState === 'complete' || document.readyState === 'interactive') {
  console.log("Page already loaded, initializing accordion");
  setTimeout(function() {
    initFaqAccordion();
    initEmergencyAccordion();
    addKeyboardAccessibility();
  }, 1);
}