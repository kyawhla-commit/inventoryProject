@props([
    'type' => 'toggle',
    'size' => 'md',
    'showText' => false,
    'showIcon' => true,
])

@php
$sizes = [
    'sm' => 'btn-sm',
    'md' => '',
    'lg' => 'btn-lg'
];
$currentTheme = auth()->check() ? (auth()->user()->theme_preference ?? 'auto') : 'auto';
@endphp

@if($type === 'toggle')
    <button 
        data-theme-toggle="toggle"
        class="theme-toggle {{ $sizes[$size] }}"
        aria-label="Toggle theme"
        type="button"
        {{ $attributes }}
    >
        @if($showIcon)
            <i class="theme-icon fas fa-adjust"></i>
        @endif
        @if($showText)
            <span class="theme-text ms-1">Theme</span>
        @endif
    </button>
@elseif($type === 'select')
    <div class="dropdown">
        <button 
            class="theme-toggle dropdown-toggle {{ $sizes[$size] }}" 
            type="button" 
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Select theme"
            title="{{ __('Theme') }}"
        >
            @if($showIcon)
                <i class="theme-icon fas fa-adjust"></i>
            @endif
            @if($showText)
                <span class="theme-text ms-1">{{ __('Theme') }}</span>
            @endif
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <button class="dropdown-item d-flex align-items-center" data-theme-option="light" onclick="setTheme('light')">
                    <i class="fas fa-sun me-2 text-warning"></i>
                    <span>{{ __('Light') }}</span>
                    <i class="fas fa-check ms-auto theme-check text-success" data-theme-check="light" style="visibility: hidden;"></i>
                </button>
            </li>
            <li>
                <button class="dropdown-item d-flex align-items-center" data-theme-option="dark" onclick="setTheme('dark')">
                    <i class="fas fa-moon me-2 text-primary"></i>
                    <span>{{ __('Dark') }}</span>
                    <i class="fas fa-check ms-auto theme-check text-success" data-theme-check="dark" style="visibility: hidden;"></i>
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <button class="dropdown-item d-flex align-items-center" data-theme-option="auto" onclick="setTheme('auto')">
                    <i class="fas fa-desktop me-2 text-secondary"></i>
                    <span>{{ __('System Default') }}</span>
                    <i class="fas fa-check ms-auto theme-check text-success" data-theme-check="auto" style="visibility: hidden;"></i>
                </button>
            </li>
        </ul>
    </div>
    
    <script>
    function setTheme(theme) {
        // Save to localStorage
        localStorage.setItem('app_theme', theme);
        
        // Get current and new effective theme
        var currentTheme = document.documentElement.getAttribute('data-bs-theme');
        var effectiveTheme = theme;
        if (theme === 'auto') {
            effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        
        // Skip animation if theme is the same
        if (currentTheme === effectiveTheme) {
            updateThemeUI(theme, effectiveTheme);
            saveThemeToServer(theme);
            return;
        }
        
        // Add transition class for smooth animation
        document.documentElement.classList.add('theme-transitioning');
        
        // Create transition overlay
        var overlay = document.createElement('div');
        overlay.className = 'theme-transition-overlay ' + (effectiveTheme === 'dark' ? 'to-dark' : 'to-light');
        document.body.appendChild(overlay);
        
        // Trigger reflow and activate overlay
        overlay.offsetHeight;
        requestAnimationFrame(function() {
            overlay.classList.add('active');
        });
        
        // Apply theme after overlay appears
        setTimeout(function() {
            document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
            
            if (effectiveTheme === 'dark') {
                document.documentElement.classList.add('dark-mode');
                document.documentElement.classList.remove('light-mode');
                document.body.classList.add('dark-mode');
                document.body.classList.remove('light-mode');
            } else {
                document.documentElement.classList.add('light-mode');
                document.documentElement.classList.remove('dark-mode');
                document.body.classList.add('light-mode');
                document.body.classList.remove('dark-mode');
            }
            
            updateThemeUI(theme, effectiveTheme);
        }, 200);
        
        // Remove overlay after animation
        setTimeout(function() {
            overlay.classList.remove('active');
            setTimeout(function() {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
                document.documentElement.classList.remove('theme-transitioning');
            }, 500);
        }, 500);
        
        saveThemeToServer(theme);
    }
    
    function updateThemeUI(theme, effectiveTheme) {
        // Update checkmarks with smooth transition
        document.querySelectorAll('.theme-check').forEach(function(el) {
            el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            el.style.visibility = 'hidden';
            el.style.opacity = '0';
            el.style.transform = 'scale(0.5)';
        });
        
        var activeCheck = document.querySelector('[data-theme-check="' + theme + '"]');
        if (activeCheck) {
            setTimeout(function() {
                activeCheck.style.visibility = 'visible';
                activeCheck.style.opacity = '1';
                activeCheck.style.transform = 'scale(1)';
            }, 150);
        }
        
        // Update icon with smooth rotation
        var icon = document.querySelector('.theme-toggle .theme-icon');
        if (icon) {
            icon.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            icon.style.transform = 'rotate(180deg) scale(0.8)';
            
            setTimeout(function() {
                icon.className = 'theme-icon fas ' + (theme === 'dark' ? 'fa-moon' : (theme === 'light' ? 'fa-sun' : 'fa-desktop'));
                icon.style.transform = 'rotate(360deg) scale(1)';
            }, 200);
        }
    }
    
    function saveThemeToServer(theme) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return;
        
        fetch('/api/user/theme', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content
            },
            body: JSON.stringify({ theme: theme })
        }).catch(function() {
            // Silently fail - localStorage is the primary storage
        });
    }
    
    // Initialize checkmark on page load
    document.addEventListener('DOMContentLoaded', function() {
        var currentTheme = localStorage.getItem('app_theme') || '{{ $currentTheme }}';
        var activeCheck = document.querySelector('[data-theme-check="' + currentTheme + '"]');
        if (activeCheck) {
            activeCheck.style.visibility = 'visible';
        }
        
        // Update icon based on current theme
        var icon = document.querySelector('.theme-toggle .theme-icon');
        if (icon) {
            icon.className = 'theme-icon fas ' + (currentTheme === 'dark' ? 'fa-moon' : (currentTheme === 'light' ? 'fa-sun' : 'fa-adjust'));
        }
    });
    </script>
@endif
