<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <title>Inventory Management System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Apply theme immediately to prevent flash (FOUC) --}}
    <script>
        (function() {
            var STORAGE_KEY = 'app_theme';
            // Check for server-side user preference first
            var serverTheme = '{{ auth()->check() ? auth()->user()->theme_preference ?? "auto" : "auto" }}';
            var stored = localStorage.getItem(STORAGE_KEY);
            
            // Use server preference if user is logged in and localStorage doesn't have a value
            var theme = stored || serverTheme || 'auto';
            
            // Sync localStorage with server preference for logged-in users
            if (serverTheme && serverTheme !== 'auto' && !stored) {
                localStorage.setItem(STORAGE_KEY, serverTheme);
            }
            
            var effectiveTheme = theme;
            
            if (theme === 'auto') {
                effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            
            document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
            if (effectiveTheme === 'dark') {
                document.documentElement.classList.add('dark-mode');
            } else {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
    
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --navbar-width-lg: 16.666667%; /* 2/12 columns */
            --main-width-lg: 83.333333%; /* 10/12 columns */
        }

        body {
            margin: 0;
            padding: 0;
            transition: all 0.3s ease;
        }

        /* Layout Container */
        .layout-container {
            display: flex;
            min-height: 100vh;
            padding-top: var(--header-height);
        }

        /* Top Header */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid #e5e5e5;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-toggle {
            background: none;
            border: 1px solid #dee2e6;
            font-size: 18px;
            cursor: pointer;
            padding: 10px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            min-height: 44px;
        }

        .menu-toggle:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
            color: #495057;
            transform: translateY(-1px);
        }

        .menu-toggle:active {
            transform: translateY(0);
        }

        .brand-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .brand-title:hover {
            color: #007bff;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .brand-title i {
            font-size: 28px;
            color: #007bff;
        }

        /* Left Sidebar - 2 columns on lg screens */
        .left-sidebar {
            width: var(--navbar-width-lg);
            height: calc(100vh - var(--header-height));
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        /* Hide/Show functionality for md screens */
        .left-sidebar.hidden {
            margin-left: -100%;
        }

        /* Mobile sidebar (overlay) */
        @media (max-width: 991px) {
            .left-sidebar {
                position: fixed;
                top: var(--header-height);
                left: -100%;
                width: var(--sidebar-width);
                height: calc(100vh - var(--header-height));
                z-index: 1100;
                box-shadow: 5px 0 25px rgba(0,0,0,0.2);
            }
            
            .left-sidebar.active {
                left: 0;
            }
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(5px);
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: #ecf0f1;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-title::before {
            content: "📋";
            font-size: 24px;
        }

        .sidebar-close {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            min-height: 36px;
        }

        .sidebar-close:hover {
            background: rgba(231, 76, 60, 0.8);
            border-color: #e74c3c;
            transform: rotate(90deg);
        }

        .sidebar-nav {
            padding: 25px 0;
        }

        .nav-section {
            margin-bottom: 35px;
        }

        .nav-section:last-child {
            margin-bottom: 20px;
        }

        .nav-section-title {
            color: #bdc3c7;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 20px;
            margin-bottom: 15px;
            position: relative;
        }

        .nav-section-title::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 20px;
            width: 30px;
            height: 2px;
            background: #3498db;
            border-radius: 1px;
        }

        .nav-item-custom {
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
            padding: 14px 20px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
            font-weight: 500;
        }

        .nav-item-custom::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: rgba(52, 152, 219, 0.1);
            transition: width 0.3s ease;
        }

        .nav-item-custom:hover::before {
            width: 100%;
        }

        .nav-item-custom:hover,
        .nav-item-custom.active {
            background: rgba(52, 152, 219, 0.15);
            color: #3498db;
            border-left-color: #3498db;
            text-decoration: none;
            transform: translateX(5px);
        }

        .nav-item-custom.active {
            background: rgba(52, 152, 219, 0.2);
            font-weight: 600;
        }

        .nav-item-custom i {
            width: 20px;
            margin-right: 15px;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .nav-item-custom:hover i,
        .nav-item-custom.active i {
            transform: scale(1.1);
            color: #3498db;
        }

        /* Custom Scrollbar Styling for Left Sidebar */
        .left-sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .left-sidebar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.2);
            border-radius: 4px;
        }

        .left-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .left-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        .left-sidebar::-webkit-scrollbar-thumb:active {
            background: rgba(255,255,255,0.7);
        }

        /* Custom Scrollbar Styling for Main Content */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .main-content::-webkit-scrollbar-thumb:active {
            background: #888;
        }

        /* Dark mode scrollbar for main content */
        .dark-mode .main-content::-webkit-scrollbar-track {
            background: #2a2d35;
        }

        .dark-mode .main-content::-webkit-scrollbar-thumb {
            background: #4a5568;
        }

        .dark-mode .main-content::-webkit-scrollbar-thumb:hover {
            background: #5a6578;
        }

        .dark-mode .main-content::-webkit-scrollbar-thumb:active {
            background: #6a7588;
        }

        /* Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Main Content - 10 columns on lg screens */
        .main-content {
            flex: 1;
            width: var(--main-width-lg);
            height: calc(100vh - var(--header-height));
            padding: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }

        /* When sidebar is hidden on md screens, main content takes full width */
        .main-content.expanded {
            width: 100%;
        }

        /* Mobile main content */
        @media (max-width: 991px) {
            .main-content {
                width: 100%;
            }
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            font-weight: 500;
        }

        .user-menu:hover {
            background-color: #f8f9fa;
            color: #007bff;
            text-decoration: none;
            border-color: #dee2e6;
            transform: translateY(-1px);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
            transition: all 0.2s ease;
        }

        .user-menu:hover .user-avatar {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
        }

        /* Theme Toggle */
        .theme-toggle {
            background: none;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 12px;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            min-height: 44px;
            position: relative;
        }

        .theme-toggle:hover {
            background: #f8f9fa;
            color: #333;
            border-color: #adb5bd;
            transform: translateY(-1px);
        }

        .theme-toggle i {
            transition: all 0.3s ease;
        }

        .theme-toggle:hover i {
            transform: rotate(15deg) scale(1.1);
        }

        /* Language Dropdown */
        .lang-dropdown {
            position: relative;
        }

        .lang-toggle {
            background: none;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 12px;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 12px;
            min-width: 50px;
            text-align: center;
        }

        .lang-toggle:hover {
            background: #f8f9fa;
            color: #333;
            border-color: #adb5bd;
            transform: translateY(-1px);
        }

        /* Dropdown Menu Improvements */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-radius: 12px;
            padding: 8px;
            margin-top: 8px;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 10px 16px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #007bff;
            transform: translateX(4px);
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .right-sidebar {
                width: 100%;
                right: -100%;
            }
        }

        @media (max-width: 767px) {
            .header-left .brand-title {
                font-size: 18px;
            }
            
            .main-content {
                padding: 15px;
            }
        }

        /* Dark Mode Support */
        .dark-mode {
            background-color: #1a1d23;
            color: #e4e6ea;
        }

        .dark-mode .top-header {
            background: #2a2d35;
            border-bottom-color: #374151;
            color: #e4e6ea;
        }

        .dark-mode .brand-title {
            color: #e4e6ea;
        }

        .dark-mode .brand-title:hover {
            color: #3498db;
        }

        .dark-mode .menu-toggle:hover {
            background-color: #374151;
        }

        .dark-mode .theme-toggle:hover,
        .dark-mode .lang-toggle:hover {
            background: #374151;
        }

        .dark-mode .user-menu:hover {
            background-color: #374151;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header class="top-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="brand-title">
                <i class="fas fa-cube me-2"></i>{{ __('Inventory') }}
            </a>
        </div>
        <div class="header-right">
            {{-- Theme Toggle with Dropdown --}}
            @include('components.theme-toggle', ['type' => 'select', 'showIcon' => true, 'showText' => false])
            
            <div class="lang-dropdown">
                <button class="lang-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ strtoupper(app()->getLocale()) }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">English</a></li>
                    <li><a class="dropdown-item" href="{{ route('lang.switch', 'mm') }}">မြန်မာ</a></li>
                </ul>
            </div>

            @auth
                <div class="dropdown">
                    <a href="#" class="user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span>{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i>{{ __('Logout') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            @else
                <a href="{{ route('login') }}" class="user-menu">
                    <i class="fas fa-sign-in-alt me-2"></i>{{ __('Login') }}
                </a>
            @endauth

            <!-- Show menu toggle only on md and below screens -->
            <button class="menu-toggle d-lg-none" id="menuToggle" type="button" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Show sidebar toggle only on md screens (768px - 991px) -->
            <button class="menu-toggle d-none d-md-block d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
                <i class="fas fa-chevron-left" id="sidebarToggleIcon"></i>
            </button>
        </div>
    </header>

    <!-- Layout Container -->
    <div class="layout-container">
        <!-- Left Sidebar - 2 columns on lg screens -->
        <nav class="left-sidebar d-lg-block" id="leftSidebar">
            <!-- Sidebar header only shown on mobile/tablet -->
            <div class="sidebar-header d-lg-none">
                <h3 class="sidebar-title">{{ __('Navigation') }}</h3>
                <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close Menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="sidebar-nav">
                @auth
                    <div class="nav-section">
                        <div class="nav-section-title">{{ __('Main') }}</div>
                        <a href="{{ route('dashboard') }}" class="nav-item-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-tachometer-alt"></i>{{ __('Dashboard') }}
                        </a>
                        <a href="{{ route('products.index') }}" class="nav-item-custom {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="fas fa-box"></i>{{ __('Products') }}
                        </a>
                        <a href="{{ route('customers.index') }}" class="nav-item-custom {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <i class="fas fa-users"></i>{{ __('Customers') }}
                        </a>
                        <a href="{{ route('sales.index') }}" class="nav-item-custom {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                            <i class="fas fa-chart-line"></i>{{ __('Sales') }}
                        </a>
                        <a href="{{ route('orders.index') }}" class="nav-item-custom {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                            <i class="fas fa-shopping-cart"></i>{{ __('Customer Orders') }}
                        </a>
                        <a href="{{ route('invoices.index') }}" class="nav-item-custom {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                            <i class="fas fa-file-invoice"></i>{{ __('Invoices') }}
                        </a>
                    </div>

                    <div class="nav-section">
                        <div class="nav-section-title">{{ __('Inventory') }}</div>
                        <a href="{{ route('stock-management.index') }}" class="nav-item-custom {{ request()->routeIs('stock-management.*') ? 'active' : '' }}">
                            <i class="fas fa-warehouse"></i>{{ __('Stock Management') }}
                        </a>
                        <a href="{{ route('purchases.index') }}" class="nav-item-custom {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                            <i class="fas fa-shopping-bag"></i>{{ __('Purchases') }}
                        </a>
                        <a href="{{ route('raw-materials.index') }}" class="nav-item-custom {{ request()->routeIs('raw-materials.index') ? 'active' : '' }}">
                            <i class="fas fa-industry"></i>{{ __('Raw Materials') }}
                        </a>
                        <a href="{{ route('raw-materials.low-stock') }}" class="nav-item-custom {{ request()->routeIs('raw-materials.low-stock') ? 'active' : '' }}">
                            <i class="fas fa-exclamation-triangle"></i>{{ __('Low Stock Materials') }}
                        </a>
                        <a href="{{ route('raw-material-usages.index') }}" class="nav-item-custom {{ request()->routeIs('raw-material-usages.*') ? 'active' : '' }}">
                            <i class="fas fa-tools"></i>{{ __('Material Usage') }}
                        </a>
                    </div>

                    <div class="nav-section">
                        <div class="nav-section-title">{{ __('Production') }}</div>
                        <a href="{{ route('recipes.index') }}" class="nav-item-custom {{ request()->routeIs('recipes.*') ? 'active' : '' }}">
                            <i class="fas fa-clipboard-list"></i>{{ __('Recipes/BOM') }}
                        </a>
                        <a href="{{ route('production-plans.index') }}" class="nav-item-custom {{ request()->routeIs('production-plans.*') ? 'active' : '' }}">
                            <i class="fas fa-calendar-alt"></i>{{ __('Production Plans') }}
                        </a>
                        <a href="{{ route('production-reports.index') }}" class="nav-item-custom {{ request()->routeIs('production-reports.*') ? 'active' : '' }}">
                            <i class="fas fa-chart-pie"></i>{{ __('Production Reports') }}
                        </a>
                    </div>

                    @if(auth()->user()->role == 'admin')
                    <div class="nav-section">
                        <div class="nav-section-title">{{ __('Management') }}</div>
                        <a href="{{ route('categories.index') }}" class="nav-item-custom {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                            <i class="fas fa-tags"></i>{{ __('Categories') }}
                        </a>
                        <a href="{{ route('suppliers.index') }}" class="nav-item-custom {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                            <i class="fas fa-truck"></i>{{ __('Suppliers') }}
                        </a>
                        <a href="{{ route('staff.index') }}" class="nav-item-custom {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                            <i class="fas fa-users"></i>{{ __('Staff Management') }}
                        </a>
                        <a href="{{ route('staff-charges.index') }}" class="nav-item-custom {{ request()->routeIs('staff-charges.*') ? 'active' : '' }}">
                            <i class="fas fa-user-tie"></i>{{ __('Staff Charges') }}
                        </a>
                    </div>

                    <div class="nav-section">
                        <div class="nav-section-title">{{ __('Reports') }}</div>
                        <a class="nav-item-custom {{ request()->routeIs('production-costs.*') ? 'active' : '' }}" href="{{ route('production-costs.dashboard') }}">
        <i class="fas fa-chart-line"></i>
        {{ __('Cost Analysis') }}
        
    </a>
                        <a href="{{ route('reports.index') }}" class="nav-item-custom {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <i class="fas fa-chart-bar"></i>{{ __('Reports') }}
                        </a>
                        <a href="{{ route('profit-loss.index') }}" class="nav-item-custom {{ request()->routeIs('profit-loss.*') ? 'active' : '' }}">
                            <i class="fas fa-calculator"></i>{{ __('Profit & Loss') }}
                        </a>
                        
                    </div>
                    @endif
                @endauth
            </div>
        </nav>

        <!-- Sidebar Overlay for mobile -->
        <div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

        <!-- Main Content - 10 columns on lg screens -->
        <main class="main-content" id="mainContent">
            @yield('content')
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Theme Manager - Inline for reliability --}}
    <script>
        // Theme Service
        const ThemeService = {
            STORAGE_KEY: 'app_theme',
            THEMES: { LIGHT: 'light', DARK: 'dark', AUTO: 'auto' },
            
            getCurrentTheme() {
                const stored = localStorage.getItem(this.STORAGE_KEY);
                if (stored && Object.values(this.THEMES).includes(stored)) return stored;
                return this.THEMES.AUTO;
            },
            
            getEffectiveTheme() {
                const theme = this.getCurrentTheme();
                return theme === this.THEMES.AUTO ? this.getSystemTheme() : theme;
            },
            
            getSystemTheme() {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? this.THEMES.DARK : this.THEMES.LIGHT;
            },
            
            saveTheme(theme) {
                localStorage.setItem(this.STORAGE_KEY, theme);
                window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme, effectiveTheme: this.getEffectiveTheme() } }));
                return theme;
            },
            
            watchSystemTheme(callback) {
                const mq = window.matchMedia('(prefers-color-scheme: dark)');
                const handler = () => { if (this.getCurrentTheme() === this.THEMES.AUTO) callback(this.getSystemTheme()); };
                mq.addEventListener('change', handler);
                return () => mq.removeEventListener('change', handler);
            }
        };

        // Theme Manager
        const themeManager = {
            isInitialized: false,
            
            init() {
                if (this.isInitialized) return;
                this.applyTheme(ThemeService.getEffectiveTheme());
                this.setupEventListeners();
                ThemeService.watchSystemTheme((theme) => this.applyTheme(theme));
                window.addEventListener('themeChanged', (e) => this.applyTheme(e.detail.effectiveTheme));
                this.isInitialized = true;
            },
            
            applyTheme(theme) {
                const html = document.documentElement;
                // Use passed theme or get effective theme if not provided
                let effectiveTheme = theme;
                if (theme === 'auto') {
                    effectiveTheme = ThemeService.getSystemTheme();
                }
                
                console.log('Applying theme:', theme, '-> effective:', effectiveTheme);
                
                html.setAttribute('data-bs-theme', effectiveTheme);
                
                if (effectiveTheme === 'dark') {
                    html.classList.add('dark-mode');
                    html.classList.remove('light-mode');
                    document.body?.classList.add('dark-mode');
                    document.body?.classList.remove('light-mode');
                } else {
                    html.classList.add('light-mode');
                    html.classList.remove('dark-mode');
                    document.body?.classList.add('light-mode');
                    document.body?.classList.remove('dark-mode');
                }
                
                this.updateToggleButtons(effectiveTheme);
            },
            
            updateToggleButtons(theme) {
                document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
                    const icon = btn.querySelector('.theme-icon');
                    if (icon) {
                        icon.className = 'theme-icon fas ' + (theme === 'dark' ? 'fa-moon' : theme === 'light' ? 'fa-sun' : 'fa-adjust');
                    }
                });
                
                document.querySelectorAll('[data-theme-check]').forEach(check => {
                    check.style.visibility = check.getAttribute('data-theme-check') === ThemeService.getCurrentTheme() ? 'visible' : 'hidden';
                });
            },
            
            setupEventListeners() {
                document.querySelectorAll('[data-theme-option]').forEach(option => {
                    option.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const theme = option.getAttribute('data-theme-option');
                        console.log('Theme option clicked:', theme);
                        ThemeService.saveTheme(theme);
                        // Get effective theme for 'auto'
                        const effectiveTheme = theme === 'auto' ? ThemeService.getSystemTheme() : theme;
                        this.applyTheme(effectiveTheme);
                        // Update checkmarks
                        document.querySelectorAll('[data-theme-check]').forEach(check => {
                            check.style.visibility = check.getAttribute('data-theme-check') === theme ? 'visible' : 'hidden';
                        });
                    });
                });
            }
        };

        // Make globally available
        window.ThemeService = ThemeService;
        window.themeManager = themeManager;
        
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Theme Manager
            themeManager.init();

            // Left Sidebar Functionality
            const menuToggle = document.getElementById('menuToggle');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
            const leftSidebar = document.getElementById('leftSidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.getElementById('mainContent');

            // Function to open mobile sidebar
            function openMobileSidebar() {
                leftSidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            // Function to close mobile sidebar
            function closeMobileSidebar() {
                leftSidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Function to toggle sidebar on md screens (768px - 991px)
            function toggleSidebarMd() {
                const isHidden = leftSidebar.classList.contains('hidden');
                
                if (isHidden) {
                    // Show sidebar
                    leftSidebar.classList.remove('hidden');
                    mainContent.classList.remove('expanded');
                    sidebarToggleIcon.classList.remove('fa-chevron-right');
                    sidebarToggleIcon.classList.add('fa-chevron-left');
                } else {
                    // Hide sidebar
                    leftSidebar.classList.add('hidden');
                    mainContent.classList.add('expanded');
                    sidebarToggleIcon.classList.remove('fa-chevron-left');
                    sidebarToggleIcon.classList.add('fa-chevron-right');
                }
                
                // Save state to localStorage
                localStorage.setItem('sidebarHidden', isHidden ? 'false' : 'true');
            }

            // Initialize sidebar state on md screens
            function initializeSidebarState() {
                const isHidden = localStorage.getItem('sidebarHidden') === 'true';
                const isMdScreen = window.innerWidth >= 768 && window.innerWidth < 992;
                
                if (isMdScreen && isHidden) {
                    leftSidebar.classList.add('hidden');
                    mainContent.classList.add('expanded');
                    if (sidebarToggleIcon) {
                        sidebarToggleIcon.classList.remove('fa-chevron-left');
                        sidebarToggleIcon.classList.add('fa-chevron-right');
                    }
                }
            }

            // Event listeners for mobile menu toggle
            if (menuToggle) {
                menuToggle.addEventListener('click', openMobileSidebar);
            }

            // Event listeners for md screen sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebarMd);
            }

            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeMobileSidebar);
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeMobileSidebar);
            }

            // Close mobile sidebar when clicking on navigation links
            const navLinks = document.querySelectorAll('.nav-item-custom');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    // Save sidebar scroll position before navigation
                    saveSidebarScrollPosition();
                    
                    if (window.innerWidth < 992) {
                        setTimeout(closeMobileSidebar, 100);
                    }
                });
            });
            
            // ===== SIDEBAR SCROLL POSITION PERSISTENCE =====
            const SIDEBAR_SCROLL_KEY = 'sidebar_scroll_position';
            
            // Save sidebar scroll position
            function saveSidebarScrollPosition() {
                if (leftSidebar) {
                    sessionStorage.setItem(SIDEBAR_SCROLL_KEY, leftSidebar.scrollTop);
                }
            }
            
            // Restore sidebar scroll position
            function restoreSidebarScrollPosition() {
                const savedPosition = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
                if (savedPosition && leftSidebar) {
                    leftSidebar.scrollTop = parseInt(savedPosition, 10);
                }
            }
            
            // Scroll active menu item into view (centered)
            function scrollActiveItemIntoView() {
                const activeItem = leftSidebar?.querySelector('.nav-item-custom.active');
                if (activeItem && leftSidebar) {
                    const savedPosition = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
                    
                    // If we have a saved position, use it; otherwise scroll to active item
                    if (savedPosition) {
                        restoreSidebarScrollPosition();
                    } else {
                        // Scroll active item into view with some offset from top
                        const sidebarRect = leftSidebar.getBoundingClientRect();
                        const itemRect = activeItem.getBoundingClientRect();
                        const itemOffsetTop = activeItem.offsetTop;
                        
                        // Center the active item in the sidebar viewport
                        const scrollTarget = itemOffsetTop - (sidebarRect.height / 2) + (itemRect.height / 2);
                        leftSidebar.scrollTop = Math.max(0, scrollTarget);
                    }
                }
            }
            
            // Save scroll position before page unload
            window.addEventListener('beforeunload', saveSidebarScrollPosition);
            
            // Restore scroll position on page load
            scrollActiveItemIntoView();

            // Close mobile sidebar on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && leftSidebar.classList.contains('active')) {
                    closeMobileSidebar();
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                const width = window.innerWidth;
                
                // Close mobile sidebar on larger screens
                if (width >= 992 && leftSidebar.classList.contains('active')) {
                    closeMobileSidebar();
                }
                
                // Reset sidebar state when switching between breakpoints
                if (width >= 992) {
                    // Large screens - always show sidebar, remove hidden class
                    leftSidebar.classList.remove('hidden');
                    mainContent.classList.remove('expanded');
                } else if (width >= 768 && width < 992) {
                    // Medium screens - restore saved state
                    initializeSidebarState();
                } else {
                    // Small screens - reset to default mobile behavior
                    leftSidebar.classList.remove('hidden');
                    mainContent.classList.remove('expanded');
                }
            });

            // Initialize sidebar state on page load
            initializeSidebarState();
        });
    </script>

    @stack('scripts')
</body>
</html>
