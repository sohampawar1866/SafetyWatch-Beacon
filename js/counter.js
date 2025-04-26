// Enhanced Counter Animation for Statistics Section
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit to ensure elements are fully rendered
    setTimeout(initCounters, 100);
    
    function initCounters() {
        const counterElements = document.querySelectorAll('.count');
        let animated = false;
        
        if (counterElements.length === 0) {
            console.log("No counter elements found, retrying in 300ms");
            setTimeout(initCounters, 300);
            return;
        }

        // Enhanced function to animate counting up to target number with easing
        function animateCounter(element, target) {
            const duration = 3000; // Extended animation duration for more impact
            const startTime = performance.now();
            const formatValue = new Intl.NumberFormat().format;
            
            // Use requestAnimationFrame for smoother animation
            function updateCounter(currentTime) {
                const elapsedTime = currentTime - startTime;
                const progress = Math.min(elapsedTime / duration, 1);
                
                // Apply easing function for more natural counting effect
                // Slow start, fast middle, slow end
                const easedProgress = easeOutExpo(progress);
                
                const currentValue = Math.floor(target * easedProgress);
                element.textContent = formatValue(currentValue);
                
                // Continue animation until complete
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    // Add pulsing effect when counter finishes
                    element.classList.add('counter-complete');
                    
                    // For critical numbers, add warning effect
                    if (target > 100000) {
                        element.style.color = '#e63946';
                        pulseEffect(element);
                    }
                }
            }
            
            requestAnimationFrame(updateCounter);
        }
        
        // Easing function for more natural animation
        function easeOutExpo(x) {
            return x === 1 ? 1 : 1 - Math.pow(2, -10 * x);
        }
        
        // Create pulsing effect for high numbers
        function pulseEffect(element) {
            let scale = 1;
            const pulseInterval = setInterval(() => {
                scale = scale === 1 ? 1.05 : 1;
                element.style.transform = `scale(${scale})`;
                element.style.transition = 'transform 0.5s ease';
            }, 1000);
            
            // Clear interval after some time
            setTimeout(() => {
                clearInterval(pulseInterval);
                element.style.transform = 'scale(1)';
            }, 10000);
        }
        
        // Fall back to direct animation if observer fails
        function directAnimate() {
            if (animated) return;
            
            animated = true;
            counterElements.forEach((counter, index) => {
                setTimeout(() => {
                    const target = parseInt(counter.getAttribute('data-target'));
                    animateCounter(counter, target);
                    
                    // Add "rising" animation
                    counter.style.opacity = 0;
                    counter.style.transform = 'translateY(20px)';
                    counter.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        counter.style.opacity = 1;
                        counter.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 200); // Stagger start times
            });
        }
        
        // Use Intersection Observer API for better performance
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1 // Trigger when just 10% of element is visible for earlier animation
        };
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !animated) {
                    // Add staggered delay to each counter for visual effect
                    counterElements.forEach((counter, index) => {
                        setTimeout(() => {
                            const target = parseInt(counter.getAttribute('data-target'));
                            animateCounter(counter, target);
                            
                            // Add "rising" animation
                            counter.style.opacity = 0;
                            counter.style.transform = 'translateY(20px)';
                            counter.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                            
                            setTimeout(() => {
                                counter.style.opacity = 1;
                                counter.style.transform = 'translateY(0)';
                            }, 50);
                        }, index * 200); // Stagger start times
                    });
                    
                    animated = true;
                    counterObserver.disconnect(); // Stop observing once animation starts
                }
            });
        }, observerOptions);
        
        // Start observing the counter grid
        const counterGrid = document.querySelector('.counter-grid');
        if (counterGrid) {
            counterObserver.observe(counterGrid);
            
            // Fallback animation if observer doesn't trigger within 3 seconds
            setTimeout(() => {
                if (!animated) {
                    console.log("Fallback animation triggered");
                    directAnimate();
                }
            }, 3000);
            
            // Add real-time incrementing effect to simulate live data
            setInterval(() => {
                if (animated) {
                    // Randomly select a counter to increment occasionally
                    const randomIndex = Math.floor(Math.random() * counterElements.length);
                    const randomCounter = counterElements[randomIndex];
                    
                    // Get current value and increment it slightly
                    const currentValue = parseInt(randomCounter.textContent.replace(/,/g, ''));
                    const newValue = currentValue + Math.floor(Math.random() * 3) + 1;
                    
                    // Update the display with a flash effect
                    randomCounter.textContent = new Intl.NumberFormat().format(newValue);
                    randomCounter.classList.add('flash');
                    
                    // Update data-target attribute to keep track of the new value
                    randomCounter.setAttribute('data-target', newValue);
                    
                    // Remove flash class after animation completes
                    setTimeout(() => {
                        randomCounter.classList.remove('flash');
                    }, 500);
                }
            }, 5000); // Check every 5 seconds
        } else {
            // If counter grid not found, try to find individual counters
            if (counterElements.length > 0) {
                counterElements.forEach(counter => {
                    counterObserver.observe(counter);
                });
                
                // Fallback animation if observer doesn't trigger
                setTimeout(() => {
                    if (!animated) {
                        console.log("Fallback animation triggered for individual counters");
                        directAnimate();
                    }
                }, 3000);
            }
        }
        
        // Add CSS for flash animation if it doesn't exist
        if (!document.getElementById('counter-animation-styles')) {
            const styleSheet = document.createElement('style');
            styleSheet.id = 'counter-animation-styles';
            styleSheet.textContent = `
                @keyframes counterFlash {
                    0% { color: inherit; }
                    30% { color: #e63946; }
                    100% { color: inherit; }
                }
                
                .flash {
                    animation: counterFlash 0.5s ease;
                }
                
                .counter-complete {
                    transition: all 0.3s ease;
                }
                
                .glow-effect {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    box-shadow: 0 0 10px 2px rgba(230, 57, 70, 0.7);
                    border-radius: inherit;
                    opacity: 0;
                    transition: opacity 0.5s ease;
                    pointer-events: none;
                }
            `;
            document.head.appendChild(styleSheet);
        }
    }
});