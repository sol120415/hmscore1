/**
 * Advanced Theme System
 * Handles light/dark mode switching with localStorage persistence and system preference detection
 */

class ThemeManager {
    constructor() {
        this.currentTheme = null;
        this.systemTheme = null;
        this.storageKey = 'theme-preference';
        this.themeToggle = null;
        this.init();
    }

    /**
     * Initialize the theme system
     */
    init() {
        this.detectSystemTheme();
        this.loadStoredTheme();
        this.createThemeToggle();
        this.applyTheme(this.currentTheme);
        this.bindEvents();
        this.watchSystemTheme();
        
        console.log('Theme system initialized:', {
            current: this.currentTheme,
            system: this.systemTheme,
            stored: this.getStoredTheme()
        });
    }

    /**
     * Detect system theme preference
     */
    detectSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            this.systemTheme = 'dark';
        } else {
            this.systemTheme = 'light';
        }
    }

    /**
     * Get stored theme preference
     */
    getStoredTheme() {
        try {
            return localStorage.getItem(this.storageKey);
        } catch (error) {
            console.warn('Could not access localStorage:', error);
            return null;
        }
    }

    /**
     * Store theme preference
     */
    storeTheme(theme) {
        try {
            localStorage.setItem(this.storageKey, theme);
        } catch (error) {
            console.warn('Could not save to localStorage:', error);
        }
    }

    /**
     * Load theme from storage or use system preference
     */
    loadStoredTheme() {
        const stored = this.getStoredTheme();
        if (stored && (stored === 'light' || stored === 'dark')) {
            this.currentTheme = stored;
        } else {
            this.currentTheme = this.systemTheme;
        }
    }

    /**
     * Create theme toggle button
     */
    createThemeToggle() {
        // Remove existing toggle if any
        const existingToggle = document.querySelector('.theme-toggle');
        if (existingToggle) {
            existingToggle.remove();
        }

        // Create toggle button
        this.themeToggle = document.createElement('button');
        this.themeToggle.className = 'theme-toggle';
        this.themeToggle.setAttribute('aria-label', 'Toggle theme');
        this.themeToggle.setAttribute('title', 'Toggle light/dark mode');
        
        // Create icon
        const icon = document.createElement('i');
        icon.className = 'theme-toggle-icon';
        this.themeToggle.appendChild(icon);
        
        // Add to page
        document.body.appendChild(this.themeToggle);
        
        // Update icon
        this.updateToggleIcon();
    }

    /**
     * Update toggle button icon
     */
    updateToggleIcon() {
        if (!this.themeToggle) return;
        
        const icon = this.themeToggle.querySelector('.theme-toggle-icon');
        if (!icon) return;
        
        if (this.currentTheme === 'light') {
            icon.className = 'theme-toggle-icon cil-moon';
        } else {
            icon.className = 'theme-toggle-icon cil-sun';
        }
    }

    /**
     * Apply theme to document
     */
    applyTheme(theme) {
        if (!theme || (theme !== 'light' && theme !== 'dark')) {
            console.warn('Invalid theme:', theme);
            return;
        }

        // Update document attributes
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-coreui-theme', theme);
        
        // Update body class
        document.body.className = document.body.className.replace(/theme-\w+/g, '');
        document.body.classList.add(`theme-${theme}`);
        
        // Update toggle icon
        this.updateToggleIcon();
        
        // Force theme application to override any conflicting styles
        this.forceThemeApplication(theme);
        
        // Dispatch theme change event
        this.dispatchThemeChangeEvent(theme);
        
        console.log('Theme applied:', theme);
    }

    /**
     * Force theme application with inline styles
     */
    forceThemeApplication(theme) {
        // Force body background
        document.body.style.setProperty('background-color', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
        document.body.style.setProperty('color', theme === 'light' ? '#212529' : '#ffffff', 'important');
        
        // Target the actual background source: wave elements from loginbg.css
        document.documentElement.style.setProperty('background-color', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
        document.documentElement.style.setProperty('background', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
        
        // Override wave backgrounds for theme consistency
        const waves = document.querySelectorAll('section .wave');
        const waveSpans = document.querySelectorAll('section .wave span');
        const waveSpan1 = document.querySelectorAll('section .wave span:nth-child(1)');
        const waveSpan2 = document.querySelectorAll('section .wave span:nth-child(2)');
        const waveSpan3 = document.querySelectorAll('section .wave span:nth-child(3)');
        
        if (theme === 'light') {
            // Light mode: Change wave backgrounds to white while keeping blue wave
            waves.forEach(wave => {
                wave.style.setProperty('background', '#4973ff', 'important'); // Keep blue wave
            });
            
            waveSpans.forEach(span => {
                span.style.setProperty('background', '#ffffff', 'important'); // White spans
            });
            
            waveSpan1.forEach(span => {
                span.style.setProperty('background', 'rgba(255, 255, 255, 1)', 'important'); // White with full opacity
            });
            
            waveSpan2.forEach(span => {
                span.style.setProperty('background', 'rgba(255, 255, 255, 0.5)', 'important'); // White with 50% opacity
            });
            
            waveSpan3.forEach(span => {
                span.style.setProperty('background', 'rgba(255, 255, 255, 0.5)', 'important'); // White with 50% opacity
            });
        } else {
            // Dark mode: Keep original dark wave backgrounds
            waves.forEach(wave => {
                wave.style.setProperty('background', '#4973ff', 'important'); // Keep blue wave
            });
            
            waveSpans.forEach(span => {
                span.style.setProperty('background', '#000', 'important'); // Keep black
            });
            
            waveSpan1.forEach(span => {
                span.style.setProperty('background', 'rgba(20, 20, 20, 1)', 'important'); // Keep original dark
            });
            
            waveSpan2.forEach(span => {
                span.style.setProperty('background', 'rgba(20, 20, 20, 0.5)', 'important'); // Keep original dark
            });
            
            waveSpan3.forEach(span => {
                span.style.setProperty('background', 'rgba(20, 20, 20, 0.5)', 'important'); // Keep original dark
            });
        }
        
        // Apply again after delay to ensure override
        setTimeout(() => {
            document.documentElement.style.setProperty('background-color', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
            document.documentElement.style.setProperty('background', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
            
            // Reapply wave overrides
            if (theme === 'light') {
                waveSpans.forEach(span => {
                    span.style.setProperty('background', '#ffffff', 'important');
                });
                waveSpan1.forEach(span => {
                    span.style.setProperty('background', 'rgba(255, 255, 255, 1)', 'important');
                });
                waveSpan2.forEach(span => {
                    span.style.setProperty('background', 'rgba(255, 255, 255, 0.5)', 'important');
                });
                waveSpan3.forEach(span => {
                    span.style.setProperty('background', 'rgba(255, 255, 255, 0.5)', 'important');
                });
            } else {
                waveSpans.forEach(span => {
                    span.style.setProperty('background', '#000', 'important');
                });
                waveSpan1.forEach(span => {
                    span.style.setProperty('background', 'rgba(20, 20, 20, 1)', 'important');
                });
                waveSpan2.forEach(span => {
                    span.style.setProperty('background', 'rgba(20, 20, 20, 0.5)', 'important');
                });
                waveSpan3.forEach(span => {
                    span.style.setProperty('background', 'rgba(20, 20, 20, 0.5)', 'important');
                });
            }
        }, 100);
        
        // Force container backgrounds
        const containers = document.querySelectorAll('.login-container, .register-container, .forgot-container, .verify-container');
        containers.forEach(container => {
            container.style.setProperty('background-color', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
        });
        
        // Force card backgrounds - Only apply to authentication pages
        const authCards = document.querySelectorAll('.login-card, .register-card, .forgot-card, .verify-card');
        authCards.forEach(card => {
            card.style.setProperty('background-color', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
            card.style.setProperty('color', theme === 'light' ? '#212529' : '#ffffff', 'important');
            card.style.setProperty('border-color', theme === 'light' ? '#dee2e6' : '#495057', 'important');
        });
        
        // Explicitly exclude dashboard cards from theme changes
        const dashboardCards = document.querySelectorAll('.card:not(.login-card):not(.register-card):not(.forgot-card):not(.verify-card)');
        dashboardCards.forEach(card => {
            // Remove any theme-related inline styles
            card.style.removeProperty('background-color');
            card.style.removeProperty('color');
            card.style.removeProperty('border-color');
        });
        
        // Force form controls - Only apply to authentication pages
        const authFormControls = document.querySelectorAll('.login-card .form-control, .register-card .form-control, .forgot-card .form-control, .verify-card .form-control');
        authFormControls.forEach(control => {
            control.style.setProperty('background-color', theme === 'light' ? '#ffffff' : '#1a1a1a', 'important');
            control.style.setProperty('color', theme === 'light' ? '#212529' : '#ffffff', 'important');
            control.style.setProperty('border-color', theme === 'light' ? '#dee2e6' : '#495057', 'important');
        });
        
        // Remove theme styles from dashboard form controls
        const dashboardFormControls = document.querySelectorAll('.form-control:not(.login-card .form-control):not(.register-card .form-control):not(.forgot-card .form-control):not(.verify-card .form-control)');
        dashboardFormControls.forEach(control => {
            control.style.removeProperty('background-color');
            control.style.removeProperty('color');
            control.style.removeProperty('border-color');
        });
        
        // Force text colors - Only apply to authentication pages (excluding validation error text)
        const authTextElements = document.querySelectorAll('.login-card .text-muted, .login-card .text-white, .login-card .text-primary, .login-card .text-decoration-none, .register-card .text-muted, .register-card .text-white, .register-card .text-primary, .register-card .text-decoration-none, .forgot-card .text-muted, .forgot-card .text-white, .forgot-card .text-primary, .forgot-card .text-decoration-none, .verify-card .text-muted, .verify-card .text-white, .verify-card .text-primary, .verify-card .text-decoration-none');
        authTextElements.forEach(element => {
            // Skip validation error elements
            if (element.classList.contains('invalid-feedback') || 
                element.classList.contains('text-danger') || 
                element.classList.contains('alert-danger') ||
                element.classList.contains('validation-error') ||
                element.classList.contains('error-message') ||
                element.classList.contains('error-text')) {
                return; // Skip these elements to preserve red color
            }
            
            if (element.classList.contains('text-muted')) {
                element.style.setProperty('color', theme === 'light' ? '#6c757d' : '#adb5bd', 'important');
            } else if (element.classList.contains('text-white')) {
                element.style.setProperty('color', theme === 'light' ? '#212529' : '#ffffff', 'important');
            } else if (element.classList.contains('text-primary')) {
                element.style.setProperty('color', '#0d6efd', 'important');
            }
        });
        
        // Remove theme styles from dashboard text elements
        const dashboardTextElements = document.querySelectorAll('.text-muted:not(.login-card .text-muted):not(.register-card .text-muted):not(.forgot-card .text-muted):not(.verify-card .text-muted), .text-white:not(.login-card .text-white):not(.register-card .text-white):not(.forgot-card .text-white):not(.verify-card .text-white), .text-primary:not(.login-card .text-primary):not(.register-card .text-primary):not(.forgot-card .text-primary):not(.verify-card .text-primary)');
        dashboardTextElements.forEach(element => {
            element.style.removeProperty('color');
        });
        
        // Explicitly preserve validation error text colors
        const validationElements = document.querySelectorAll('.invalid-feedback, .text-danger, .alert-danger, .validation-error, .error-message, .error-text');
        validationElements.forEach(element => {
            element.style.setProperty('color', '#dc3545', 'important'); // Bootstrap red color
        });
        
        // Force section background with maximum override
        this.forceSectionBackground(theme);
        
        // Force input group text
        const inputGroupTexts = document.querySelectorAll('.input-group-text');
        inputGroupTexts.forEach(text => {
            text.style.setProperty('background-color', theme === 'light' ? '#f8f9fa' : '#2d2d2d', 'important');
            text.style.setProperty('color', theme === 'light' ? '#212529' : '#ffffff', 'important');
            text.style.setProperty('border-color', theme === 'light' ? '#dee2e6' : '#495057', 'important');
        });
        
        // Force alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.setProperty('background-color', theme === 'light' ? '#f8f9fa' : '#2d2d2d', 'important');
            alert.style.setProperty('color', theme === 'light' ? '#212529' : '#ffffff', 'important');
            alert.style.setProperty('border-color', theme === 'light' ? '#dee2e6' : '#495057', 'important');
        });
    }

    /**
     * Toggle between light and dark themes
     */
    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }

    /**
     * Set specific theme
     */
    setTheme(theme) {
        if (theme !== 'light' && theme !== 'dark') {
            console.warn('Invalid theme:', theme);
            return;
        }
        
        this.currentTheme = theme;
        this.storeTheme(theme);
        this.applyTheme(theme);
    }

    /**
     * Reset to system theme
     */
    resetToSystemTheme() {
        this.setTheme(this.systemTheme);
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Toggle button click
        if (this.themeToggle) {
            this.themeToggle.addEventListener('click', () => {
                this.toggleTheme();
            });
        }
        
        // Keyboard shortcut (Ctrl/Cmd + Shift + T)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }

    /**
     * Watch for system theme changes
     */
    watchSystemTheme() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', (e) => {
                this.systemTheme = e.matches ? 'dark' : 'light';
                
                // Only auto-switch if user hasn't set a preference
                const stored = this.getStoredTheme();
                if (!stored) {
                    this.setTheme(this.systemTheme);
                }
            });
        }
    }

    /**
     * Dispatch theme change event
     */
    dispatchThemeChangeEvent(theme) {
        const event = new CustomEvent('themechange', {
            detail: { theme: theme }
        });
        document.dispatchEvent(event);
    }

    /**
     * Force section background with maximum override
     */
    forceSectionBackground(theme) {
        // Remove existing dynamic style if any
        const existingStyle = document.getElementById('section-background-override');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        // Create new dynamic style for section background
        const style = document.createElement('style');
        style.id = 'section-background-override';
        
        if (theme === 'light') {
            style.textContent = `
                html {
                    background: #ffffff !important;
                    background-color: #ffffff !important;
                }
                body {
                    background: #ffffff !important;
                    background-color: #ffffff !important;
                }
                [data-theme="light"] section .wave {
                    background: #4973ff !important;
                }
                [data-theme="light"] section .wave span {
                    background: #ffffff !important;
                }
                [data-theme="light"] section .wave span:nth-child(1) {
                    background: rgba(255, 255, 255, 1) !important;
                }
                [data-theme="light"] section .wave span:nth-child(2) {
                    background: rgba(255, 255, 255, 0.5) !important;
                }
                [data-theme="light"] section .wave span:nth-child(3) {
                    background: rgba(255, 255, 255, 0.5) !important;
                }
            `;
        } else {
            style.textContent = `
                html {
                    background: #1a1a1a !important;
                    background-color: #1a1a1a !important;
                }
                body {
                    background: #1a1a1a !important;
                    background-color: #1a1a1a !important;
                }
                [data-theme="dark"] section .wave {
                    background: #4973ff !important;
                }
                [data-theme="dark"] section .wave span {
                    background: #000 !important;
                }
                [data-theme="dark"] section .wave span:nth-child(1) {
                    background: rgba(20, 20, 20, 1) !important;
                }
                [data-theme="dark"] section .wave span:nth-child(2) {
                    background: rgba(20, 20, 20, 0.5) !important;
                }
                [data-theme="dark"] section .wave span:nth-child(3) {
                    background: rgba(20, 20, 20, 0.5) !important;
                }
            `;
        }
        
        document.head.appendChild(style);
    }

    /**
     * Get current theme
     */
    getCurrentTheme() {
        return this.currentTheme;
    }

    /**
     * Check if theme is light
     */
    isLightTheme() {
        return this.currentTheme === 'light';
    }

    /**
     * Check if theme is dark
     */
    isDarkTheme() {
        return this.currentTheme === 'dark';
    }
}

// Initialize theme system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
