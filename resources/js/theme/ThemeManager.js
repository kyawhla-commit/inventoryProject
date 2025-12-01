import { ThemeService } from './theme-service.js';

/**
 * Theme Manager - Handles UI updates and theme application
 */
export class ThemeManager {
    constructor(options = {}) {
        this.options = {
            themeAttribute: 'data-bs-theme',
            persistToServer: false,
            serverEndpoint: '/api/theme/preference',
            ...options
        };
        
        this.toggleButtons = [];
        this.isInitialized = false;
    }

    /**
     * Initialize theme manager
     */
    init() {
        if (this.isInitialized) return;

        // Apply initial theme
        this.applyTheme(ThemeService.getEffectiveTheme());

        // Find all theme toggle buttons
        this.toggleButtons = document.querySelectorAll('[data-theme-toggle]');
        this.setupEventListeners();

        // Watch for system theme changes
        this.cleanupSystemWatch = ThemeService.watchSystemTheme((theme) => {
            this.applyTheme(theme);
        });

        // Listen for theme changes from other components
        window.addEventListener('themeChanged', (e) => {
            this.applyTheme(e.detail.effectiveTheme);
        });

        this.isInitialized = true;
    }

    /**
     * Apply theme to document
     */
    applyTheme(theme) {
        const html = document.documentElement;
        const effectiveTheme = ThemeService.getEffectiveTheme();

        // Set Bootstrap theme attribute
        html.setAttribute(this.options.themeAttribute, effectiveTheme);

        // Update toggle buttons
        this.updateToggleButtons(effectiveTheme);

        // Add/remove dark mode class for custom styling
        if (effectiveTheme === ThemeService.THEMES.DARK) {
            html.classList.add('dark-mode');
            html.classList.remove('light-mode');
            document.body.classList.add('dark-mode');
            document.body.classList.remove('light-mode');
        } else {
            html.classList.add('light-mode');
            html.classList.remove('dark-mode');
            document.body.classList.add('light-mode');
            document.body.classList.remove('dark-mode');
        }

        // Dispatch event
        window.dispatchEvent(new CustomEvent('themeApplied', {
            detail: { theme, effectiveTheme }
        }));
    }

    /**
     * Update all theme toggle buttons
     */
    updateToggleButtons(theme) {
        this.toggleButtons.forEach(button => {
            const type = button.getAttribute('data-theme-toggle');
            if (type === 'toggle') {
                this.updateToggleButton(button, theme);
            } else if (type === 'select') {
                this.updateThemeSelect(button, theme);
            }
        });

        // Update dropdown checkmarks
        document.querySelectorAll('[data-theme-check]').forEach(check => {
            const checkTheme = check.getAttribute('data-theme-check');
            check.style.visibility = checkTheme === ThemeService.getCurrentTheme() ? 'visible' : 'hidden';
        });
    }

    /**
     * Update a toggle button
     */
    updateToggleButton(button, theme) {
        const icon = button.querySelector('.theme-icon');
        const text = button.querySelector('.theme-text');

        if (icon) {
            icon.className = 'theme-icon ' + this.getIconClass(theme);
        }

        if (text) {
            text.textContent = this.getButtonText(theme);
        }

        button.setAttribute('aria-label', `${theme} mode`);
        button.setAttribute('data-current-theme', theme);
    }

    /**
     * Get icon class for theme
     */
    getIconClass(theme) {
        const icons = {
            [ThemeService.THEMES.LIGHT]: 'fas fa-sun',
            [ThemeService.THEMES.DARK]: 'fas fa-moon',
            [ThemeService.THEMES.AUTO]: 'fas fa-adjust'
        };
        return icons[theme] || icons[ThemeService.THEMES.AUTO];
    }

    /**
     * Get button text for theme
     */
    getButtonText(theme) {
        const texts = {
            [ThemeService.THEMES.LIGHT]: 'Light',
            [ThemeService.THEMES.DARK]: 'Dark',
            [ThemeService.THEMES.AUTO]: 'Auto'
        };
        return texts[theme] || 'Theme';
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Toggle buttons
        this.toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleToggleClick(button);
            });
        });

        // Theme option buttons in dropdown
        document.querySelectorAll('[data-theme-option]').forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                const theme = option.getAttribute('data-theme-option');
                ThemeService.saveTheme(theme);
                this.applyTheme(theme);
            });
        });
    }

    /**
     * Handle toggle button click - cycles through themes
     */
    handleToggleClick(button) {
        const current = ThemeService.getCurrentTheme();
        let nextTheme;

        if (current === ThemeService.THEMES.LIGHT) {
            nextTheme = ThemeService.THEMES.DARK;
        } else if (current === ThemeService.THEMES.DARK) {
            nextTheme = ThemeService.THEMES.AUTO;
        } else {
            nextTheme = ThemeService.THEMES.LIGHT;
        }

        ThemeService.saveTheme(nextTheme);
        this.applyTheme(nextTheme);
    }

    /**
     * Cleanup
     */
    destroy() {
        if (this.cleanupSystemWatch) {
            this.cleanupSystemWatch();
        }
        this.isInitialized = false;
    }
}

// Export singleton instance
export const themeManager = new ThemeManager();

// Make available globally
window.themeManager = themeManager;
