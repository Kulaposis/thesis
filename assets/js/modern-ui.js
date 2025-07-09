/**
 * Modern UI JavaScript Framework
 * Enhanced interactions, animations, and user experience
 */

class ModernUI {
  constructor() {
    this.init();
  }

  init() {
    this.initTheme();
    this.initAnimations();
    this.initSidebar();
    this.initTabs();
    this.initComponents();
    this.initFormEnhancements();
    this.initLoadingStates();
    this.initNotifications();
  }

  // Theme Management
  initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    this.setTheme(savedTheme);
    
    // Create theme toggle if it doesn't exist
    this.createThemeToggle();
  }

  setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
  }

  toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    this.setTheme(newTheme);
  }

  createThemeToggle() {
    const header = document.querySelector('.flex.justify-between.items-center');
    if (header && !document.querySelector('.theme-toggle')) {
      const toggleContainer = document.createElement('div');
      toggleContainer.className = 'theme-toggle-container';
      toggleContainer.innerHTML = `
        <div class="theme-toggle" id="themeToggle" title="Toggle dark mode">
          <i data-lucide="sun" class="theme-icon sun-icon"></i>
          <i data-lucide="moon" class="theme-icon moon-icon"></i>
        </div>
      `;
      
      header.appendChild(toggleContainer);
      
      const toggle = document.getElementById('themeToggle');
      toggle.addEventListener('click', () => this.toggleTheme());
      
      // Re-initialize Lucide icons
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    }
  }

  // Smooth Animations
  initAnimations() {
    // Intersection Observer for fade-in animations
    const observeOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in');
        }
      });
    }, observeOptions);

    // Observe elements for animation
    document.querySelectorAll('.card, .table-container, .sidebar').forEach(el => {
      observer.observe(el);
    });

    // Add staggered animations
    this.addStaggeredAnimations();
  }

  addStaggeredAnimations() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
      card.style.animationDelay = `${index * 100}ms`;
      card.classList.add('fade-in');
    });
  }

  // Enhanced Sidebar
  initSidebar() {
    const sidebar = document.querySelector('aside');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('main');

    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-open');
        document.body.classList.toggle('sidebar-mobile-open');
      });

      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
          if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
            sidebar.classList.remove('sidebar-open');
            document.body.classList.remove('sidebar-mobile-open');
          }
        }
      });
    }

    // Add smooth hover effects to sidebar items
    this.enhanceSidebarItems();
  }

  enhanceSidebarItems() {
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
      item.addEventListener('mouseenter', () => {
        item.style.transform = 'translateX(4px)';
      });
      
      item.addEventListener('mouseleave', () => {
        if (!item.classList.contains('active-tab')) {
          item.style.transform = 'translateX(0)';
        }
      });
    });
  }

  // Enhanced Tab System
  initTabs() {
    const tabButtons = document.querySelectorAll('[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const targetTab = button.getAttribute('data-tab');
        
        // Remove active class from all tabs and contents
        tabButtons.forEach(btn => btn.classList.remove('active-tab'));
        tabContents.forEach(content => {
          content.classList.add('hidden');
          content.classList.remove('fade-in');
        });

        // Add active class to clicked tab
        button.classList.add('active-tab');
        
        // Show target content with animation
        const targetContent = document.getElementById(`${targetTab}-content`);
        if (targetContent) {
          setTimeout(() => {
            targetContent.classList.remove('hidden');
            targetContent.classList.add('fade-in');
          }, 150);
        }

        // Update page title
        this.updatePageTitle(button.textContent.trim());
      });
    });
  }

  updatePageTitle(tabName) {
    const titleElement = document.querySelector('h2');
    if (titleElement) {
      titleElement.textContent = tabName;
      titleElement.classList.add('scale-in');
      setTimeout(() => {
        titleElement.classList.remove('scale-in');
      }, 300);
    }
  }

  // Enhanced Components
  initComponents() {
    this.enhanceCards();
    this.enhanceButtons();
    this.enhanceTables();
    this.enhanceProgressBars();
    this.enhanceModals();
  }

  enhanceCards() {
    const cards = document.querySelectorAll('.card, .card-hover');
    cards.forEach(card => {
      // Add ripple effect
      card.addEventListener('click', (e) => {
        this.createRipple(e, card);
      });

      // Add subtle parallax effect
      card.addEventListener('mousemove', (e) => {
        if (card.classList.contains('card-hover')) {
          this.addParallaxEffect(e, card);
        }
      });

      card.addEventListener('mouseleave', () => {
        card.style.transform = '';
      });
    });
  }

  createRipple(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    ripple.style.cssText = `
      position: absolute;
      border-radius: 50%;
      background: rgba(59, 130, 246, 0.3);
      transform: scale(0);
      animation: ripple-animation 0.6s ease-out;
      left: ${x}px;
      top: ${y}px;
      width: ${size}px;
      height: ${size}px;
      pointer-events: none;
      z-index: 1;
    `;

    // Add ripple animation keyframes if not exists
    if (!document.querySelector('#ripple-styles')) {
      const style = document.createElement('style');
      style.id = 'ripple-styles';
      style.textContent = `
        @keyframes ripple-animation {
          to {
            transform: scale(2);
            opacity: 0;
          }
        }
      `;
      document.head.appendChild(style);
    }

    const originalPosition = element.style.position;
    element.style.position = 'relative';
    element.appendChild(ripple);

    setTimeout(() => {
      if (ripple.parentNode) {
        ripple.parentNode.removeChild(ripple);
      }
      if (!originalPosition) {
        element.style.position = '';
      }
    }, 600);
  }

  addParallaxEffect(event, element) {
    const rect = element.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;
    const rotateX = (y - centerY) / 10;
    const rotateY = (centerX - x) / 10;

    element.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
  }

  enhanceButtons() {
    const buttons = document.querySelectorAll('.btn, button');
    buttons.forEach(button => {
      // Add loading state capability
      const originalText = button.innerHTML;
      
      button.addEventListener('click', () => {
        if (button.classList.contains('btn-loading')) return;
        
        // Add subtle click animation
        button.style.transform = 'scale(0.98)';
        setTimeout(() => {
          button.style.transform = '';
        }, 150);
      });

      // Add loading method
      button.setLoading = (loading) => {
        if (loading) {
          button.classList.add('btn-loading');
          button.disabled = true;
          button.innerHTML = `
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
          `;
        } else {
          button.classList.remove('btn-loading');
          button.disabled = false;
          button.innerHTML = originalText;
        }
      };
    });
  }

  enhanceTables() {
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
      // Add row hover effects
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 50}ms`;
        row.classList.add('fade-in');
        
        // Add click feedback
        row.addEventListener('click', () => {
          row.style.background = 'var(--primary-50)';
          setTimeout(() => {
            row.style.background = '';
          }, 300);
        });
      });
    });
  }

  enhanceProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      
      // Animate to target width
      setTimeout(() => {
        bar.style.width = width;
      }, 500);
    });
  }

  enhanceModals() {
    const modals = document.querySelectorAll('[id*="Modal"]');
    modals.forEach(modal => {
      // Add backdrop blur effect
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          this.closeModal(modal);
        }
      });

      // Add escape key handling
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
          this.closeModal(modal);
        }
      });
    });
  }

  closeModal(modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
  }

  // Form Enhancements
  initFormEnhancements() {
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
      // Add floating label effect
      this.addFloatingLabel(input);
      
      // Add validation styling
      this.addValidationStyling(input);
      
      // Add focus effects
      this.addFocusEffects(input);
    });
  }

  addFloatingLabel(input) {
    const formGroup = input.closest('.form-group, .mb-4');
    if (!formGroup) return;

    const label = formGroup.querySelector('label');
    if (!label) return;

    input.addEventListener('focus', () => {
      label.style.transform = 'translateY(-20px) scale(0.85)';
      label.style.color = 'var(--primary-600)';
    });

    input.addEventListener('blur', () => {
      if (!input.value) {
        label.style.transform = '';
        label.style.color = '';
      }
    });

    // Initial state
    if (input.value) {
      label.style.transform = 'translateY(-20px) scale(0.85)';
    }
  }

  addValidationStyling(input) {
    input.addEventListener('blur', () => {
      if (input.required && !input.value) {
        input.classList.add('error');
        this.showFieldError(input, 'This field is required');
      } else if (input.type === 'email' && input.value && !this.isValidEmail(input.value)) {
        input.classList.add('error');
        this.showFieldError(input, 'Please enter a valid email address');
      } else {
        input.classList.remove('error');
        this.hideFieldError(input);
        if (input.value) {
          input.classList.add('success');
        }
      }
    });

    input.addEventListener('input', () => {
      if (input.classList.contains('error')) {
        input.classList.remove('error');
        this.hideFieldError(input);
      }
    });
  }

  addFocusEffects(input) {
    input.addEventListener('focus', () => {
      input.parentElement.classList.add('input-focused');
    });

    input.addEventListener('blur', () => {
      input.parentElement.classList.remove('input-focused');
    });
  }

  showFieldError(input, message) {
    this.hideFieldError(input); // Remove existing error
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error text-xs text-red-600 mt-1';
    errorElement.textContent = message;
    
    input.parentElement.appendChild(errorElement);
  }

  hideFieldError(input) {
    const errorElement = input.parentElement.querySelector('.field-error');
    if (errorElement) {
      errorElement.remove();
    }
  }

  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Loading States
  initLoadingStates() {
    // Add loading skeleton for dynamic content
    this.addLoadingSkeletons();
  }

  addLoadingSkeletons() {
    const contentAreas = document.querySelectorAll('.tab-content');
    contentAreas.forEach(area => {
      if (area.children.length === 0) {
        area.innerHTML = this.createSkeletonHTML();
        
        // Remove skeleton after content loads
        setTimeout(() => {
          area.classList.remove('loading');
        }, 1000);
      }
    });
  }

  createSkeletonHTML() {
    return `
      <div class="loading">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div class="card">
            <div class="h-20 bg-gray-200 animate-pulse rounded"></div>
          </div>
          <div class="card">
            <div class="h-20 bg-gray-200 animate-pulse rounded"></div>
          </div>
          <div class="card">
            <div class="h-20 bg-gray-200 animate-pulse rounded"></div>
          </div>
        </div>
        <div class="card">
          <div class="h-64 bg-gray-200 animate-pulse rounded"></div>
        </div>
      </div>
    `;
  }

  // Notification System
  initNotifications() {
    this.createNotificationContainer();
  }

  createNotificationContainer() {
    if (!document.querySelector('.notification-container')) {
      const container = document.createElement('div');
      container.className = 'notification-container fixed top-4 right-4 z-50 space-y-2';
      document.body.appendChild(container);
    }
  }

  showNotification(message, type = 'info', duration = 5000) {
    const container = document.querySelector('.notification-container');
    const notification = document.createElement('div');
    
    const typeColors = {
      success: 'bg-green-500 border-green-600',
      error: 'bg-red-500 border-red-600',
      warning: 'bg-yellow-500 border-yellow-600',
      info: 'bg-blue-500 border-blue-600'
    };

    notification.className = `
      notification p-4 rounded-lg shadow-lg text-white border-l-4 
      ${typeColors[type]} transform translate-x-full opacity-0 
      transition-all duration-300 ease-out max-w-sm
    `;

    notification.innerHTML = `
      <div class="flex items-center justify-between">
        <span>${message}</span>
        <button class="ml-4 text-white opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
    `;

    container.appendChild(notification);

    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }

    // Trigger animation
    setTimeout(() => {
      notification.classList.remove('translate-x-full', 'opacity-0');
    }, 10);

    // Auto remove
    if (duration > 0) {
      setTimeout(() => {
        notification.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
          if (notification.parentElement) {
            notification.remove();
          }
        }, 300);
      }, duration);
    }
  }

  // Utility Methods
  debounce(func, wait) {
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

  throttle(func, limit) {
    let inThrottle;
    return function() {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  // Public API for external usage
  static getInstance() {
    if (!window.modernUIInstance) {
      window.modernUIInstance = new ModernUI();
    }
    return window.modernUIInstance;
  }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const ui = ModernUI.getInstance();
  
  // Expose to global scope for manual usage
  window.modernUI = ui;
  
  // Show welcome notification
  setTimeout(() => {
    ui.showNotification('Welcome to the enhanced UI! 🎉', 'success', 3000);
  }, 1000);
});

// Additional CSS for animations and effects
const additionalStyles = `
  .animate-in {
    animation: slideIn 0.6s ease-out forwards;
  }

  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .sidebar-mobile-open {
    overflow: hidden;
  }

  .sidebar-mobile-open aside {
    transform: translateX(0);
    position: fixed;
    z-index: 40;
    height: 100vh;
  }

  @media (max-width: 768px) {
    aside {
      transform: translateX(-100%);
      transition: transform 0.3s ease;
    }
  }

  .input-focused {
    transform: scale(1.02);
    transition: transform 0.2s ease;
  }

  .theme-toggle-container {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .theme-toggle {
    position: relative;
    width: 60px;
    height: 30px;
    background: var(--gray-300);
    border-radius: 15px;
    cursor: pointer;
    transition: background 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px;
  }

  [data-theme="dark"] .theme-toggle {
    background: var(--primary-600);
  }

  .theme-icon {
    width: 16px;
    height: 16px;
    color: var(--gray-600);
    transition: opacity 0.3s ease;
  }

  [data-theme="dark"] .sun-icon {
    opacity: 0.3;
  }

  [data-theme="light"] .moon-icon {
    opacity: 0.3;
  }

  .theme-toggle::before {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 24px;
    height: 24px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }

  [data-theme="dark"] .theme-toggle::before {
    transform: translateX(30px);
  }

  .btn-loading {
    pointer-events: none;
  }

  .notification-container {
    pointer-events: none;
  }

  .notification {
    pointer-events: auto;
  }
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);