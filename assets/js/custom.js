/**
 * Sliding Menu Functionality & Dynamic Header Padding
 * WCEME 2025 Website
 */

document.addEventListener("DOMContentLoaded", function () {
  // Dynamic Header Padding Function
  function adjustHeaderPadding() {
    const pageHeader = document.getElementById("page-header-sec");
    if (pageHeader) {
      const headerHeight = pageHeader.offsetHeight;
      const nextSection = pageHeader.nextElementSibling;

      if (nextSection) {
        // Calculate dynamic padding based on header height
        const dynamicPadding = Math.max(20, Math.floor(headerHeight * 0.1));
        nextSection.style.paddingTop = dynamicPadding + "px";
      }

      // Add resize event listener for responsive behavior
      window.addEventListener("resize", function () {
        setTimeout(adjustHeaderPadding, 100);
      });
    }
  }

  // Initialize header padding adjustment
  adjustHeaderPadding();

  // Get menu elements
  const menuToggle = document.getElementById("menu-toggle");
  const menuClose = document.getElementById("menu-close");
  const slidingMenu = document.getElementById("sliding-menu");
  const menuOverlay = document.querySelector(".menu-overlay");
  const menuItems = document.querySelectorAll(".menu-item");
  const menuLinks = document.querySelectorAll(".menu-link");

  // Open menu function
  function openMenu() {
    slidingMenu.classList.add("active");
    document.body.classList.add("menu-open");

    // Animate menu items with staggered delay
    menuItems.forEach((item, index) => {
      const delay = item.getAttribute("data-delay") || (index + 1) * 100;
      setTimeout(() => {
        item.style.transitionDelay = delay + "ms";
      }, 50);
    });
  }

  // Close menu function
  function closeMenu() {
    slidingMenu.classList.remove("active");
    document.body.classList.remove("menu-open");

    // Reset menu item transitions
    menuItems.forEach((item) => {
      item.style.transitionDelay = "0ms";
    });
  }

  // Event listeners
  if (menuToggle) {
    menuToggle.addEventListener("click", function (e) {
      e.preventDefault();
      openMenu();
    });
  }

  if (menuClose) {
    menuClose.addEventListener("click", function (e) {
      e.preventDefault();
      closeMenu();
    });
  }

  if (menuOverlay) {
    menuOverlay.addEventListener("click", function () {
      closeMenu();
    });
  }

  // Close menu when clicking on menu links (for smooth navigation)
  menuLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      // Check if it's an internal link (starts with #)
      if (this.getAttribute("href").startsWith("#")) {
        closeMenu();
      } else {
        // For external links, add a small delay before navigation
        e.preventDefault();
        const href = this.getAttribute("href");

        setTimeout(() => {
          window.location.href = href;
        }, 300);

        closeMenu();
      }
    });
  });

  // Close menu on escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && slidingMenu.classList.contains("active")) {
      closeMenu();
    }
  });

  // Handle window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768 && slidingMenu.classList.contains("active")) {
      closeMenu();
    }
  });

  // Smooth scrolling for internal links
  function smoothScroll(target) {
    const element = document.querySelector(target);
    if (element) {
      element.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  }

  // Add hover effects for better interactivity
  menuLinks.forEach((link) => {
    const icon = link.querySelector("i");

    link.addEventListener("mouseenter", function () {
      if (icon) {
        icon.style.transform = "scale(1.2) translateX(5px)";
      }
    });

    link.addEventListener("mouseleave", function () {
      if (icon) {
        icon.style.transform = "scale(1) translateX(0)";
      }
    });
  });

  // Add ripple effect to menu items
  menuLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const ripple = document.createElement("span");
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.height, rect.width);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;

      ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(246, 115, 48, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
                z-index: 1;
            `;

      this.style.position = "relative";
      this.appendChild(ripple);

      setTimeout(() => {
        ripple.remove();
      }, 600);
    });
  });

  // Add CSS animation for ripple effect
  const style = document.createElement("style");
  style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        /* Floating Help Icon Styles */
        .floating-help-icon {
            position: fixed;
            left: 20px;
            bottom: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f67330, #e55d1f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(246, 115, 48, 0.4);
            cursor: pointer;
            z-index: 9999;
            transition: all 0.3s ease;
            animation: helpPulse 2s infinite;
        }
        
        .floating-help-icon:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(246, 115, 48, 0.6);
            animation: none;
        }
        
        .floating-help-icon i {
            color: white;
            font-size: 24px;
        }
        
        .help-tooltip {
            position: absolute;
            left: 70px;
            top: 50%;
            transform: translateY(-50%);
            background: #2c3e50;
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 14px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .help-tooltip::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 8px solid transparent;
            border-left-color: #2c3e50;
        }
        
        .floating-help-icon:hover .help-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        @keyframes helpPulse {
            0%, 100% {
                box-shadow: 0 4px 20px rgba(246, 115, 48, 0.4);
            }
            50% {
                box-shadow: 0 4px 30px rgba(246, 115, 48, 0.7);
            }
        }
        
        /* Floating Register Button Styles */
        .floating-register-btn {
            position: fixed;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #f67330, #e55d1f);
            color: white;
            padding: 20px 15px;
            border-radius: 10px 0 0 10px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            box-shadow: -4px 0 20px rgba(246, 115, 48, 0.4);
            cursor: pointer;
            z-index: 9999;
            transition: all 0.3s ease;
            animation: registerPulse 2s infinite;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            min-height: 120px;
            justify-content: center;
        }
        
        .floating-register-btn:hover {
            box-shadow: -8px 0 30px rgba(246, 115, 48, 0.6);
            animation: none;
            color: white;
            text-decoration: none;
        }
        
        .floating-register-btn i {
            font-size: 24px;
            writing-mode: initial;
            text-orientation: initial;
        }
        
        .floating-register-btn span {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            letter-spacing: 2px;
        }
        
        .register-tooltip {
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #2c3e50;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin-right: 10px;
        }
        
        .register-tooltip::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 10px solid transparent;
            border-left-color: #2c3e50;
        }
        
        .floating-register-btn:hover .register-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        @keyframes registerPulse {
            0%, 100% {
                box-shadow: -4px 0 20px rgba(246, 115, 48, 0.4);
                transform: translateY(-50%) scale(1);
            }
            50% {
                box-shadow: -6px 0 25px rgba(246, 115, 48, 0.7);
                transform: translateY(-50%) scale(1.02);
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .floating-help-icon {
                width: 50px;
                height: 50px;
                left: 15px;
                bottom: 90px;
            }
            
            .floating-help-icon i {
                font-size: 20px;
            }
            
            .help-tooltip {
                left: 60px;
                font-size: 12px;
                padding: 8px 12px;
            }
            
            .floating-register-btn {
                right: 0;
                top: 50%;
                transform: translateY(-50%);
                padding: 15px 10px;
                font-size: 12px;
                min-height: 100px;
            }
            
            .floating-register-btn i {
                font-size: 20px;
            }
            
            .register-tooltip {
                right: 100%;
                margin-right: 8px;
                font-size: 12px;
            }
        }
    `;
  document.head.appendChild(style);

  // Create and inject floating help icon
  function createFloatingHelpIcon() {
    const helpIcon = document.createElement("div");
    helpIcon.className = "floating-help-icon";
    helpIcon.innerHTML = `
      <i class="ri-customer-service-2-line"></i>
      <div class="help-tooltip">
        Need Some Help?<br>
        Click to email us
      </div>
    `;

    // Add click event to open email
    helpIcon.addEventListener("click", function () {
      const email = "wceme2025@avmc.edu.in";
      const subject = "WCEME 2025 - Help Request";
      const body =
        "Hello WCEME 2025 Team,\n\nI need help with:\n\n[Please describe your question or issue here]\n\nThank you!";

      const mailtoLink = `mailto:${email}?subject=${encodeURIComponent(
        subject
      )}&body=${encodeURIComponent(body)}`;
      window.location.href = mailtoLink;
    });

    // Append to body
    document.body.appendChild(helpIcon);
  }

  // Initialize floating help icon
  createFloatingHelpIcon();

  // Create and inject floating register button
  function createFloatingRegisterButton() {
    const registerBtn = document.createElement("a");
    registerBtn.className = "floating-register-btn";
    registerBtn.href = "registration-form.html";
    registerBtn.innerHTML = `
      <span>REGISTER NOW</span>
      <div class="register-tooltip">
        Join WCEME 2025 Conference!
      </div>
    `;

    // Add click effect
    registerBtn.addEventListener("click", function (e) {
      e.preventDefault();
      this.style.transform = "translateY(-50%) scale(0.95)";
      setTimeout(() => {
        window.location.href = this.href;
      }, 150);
    });

    // Append to body
    document.body.appendChild(registerBtn);
  }

  // Initialize floating register button
  createFloatingRegisterButton();
});
