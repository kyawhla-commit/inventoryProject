/**
 * Theme Service - Handles theme persistence and system preferences
 */
export class ThemeService {
    static STORAGE_KEY = 'app_theme';
    static THEMES = {
        LIGHT: 'light',
        DARK: 'dark',
        AUTO: 'auto'
    };

    /**
     * Get current theme with fallbacks
     */
    static getCurrentTheme() {
        const stored = localStorage.getItem(this.STORAGE_KEY);
        if (stored && Object.values(this.THEMES).includes(stored)) {
            return stored;
        }
        return this.THEMES.AUTO;
    }

    /**
     * Get effective theme (resolves 'auto' to actual theme)
     */
    static getEffectiveTheme() {
        const theme = this.getCurrentTheme();
        if (theme === this.THEMES.AUTO) {
            return this.getSystemTheme();
        }
        return theme;
    }

    /**
     * Detect system theme preference
     */
    static getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches 
            ? this.THEMES.DARK 
            : this.THEMES.LIGHT;
    }

    /**
     * Save theme preference
     */
    static saveTheme(theme) {
        if (!Object.values(this.THEMES).includes(theme)) {
            throw new Error(`Invalid theme: ${theme}`);
        }
        localStorage.setItem(this.STORAGE_KEY, theme);
        
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme, effectiveTheme: this.getEffectiveTheme() }
        }));
        
        return theme;
    }

    /**
     * Listen for system theme changes
     */
    static watchSystemTheme(callback) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = (e) => {
            if (this.getCurrentTheme() === this.THEMES.AUTO) {
                callback(this.getSystemTheme());
            }
        };
        
        mediaQuery.addEventListener('change', handler);
        return () => mediaQuery.removeEventListener('change', handler);
    }
}
