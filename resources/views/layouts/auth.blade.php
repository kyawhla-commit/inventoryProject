<!DOCTYPE html>
<html>
<head>
    <title>{{ config('app.name', 'Inventroy Management System') }} - {{ __('Authentication') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .auth-logo {
            font-size: 48px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .auth-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .auth-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
            position: relative;
            z-index: 1;
        }

        .auth-body {
            padding: 40px 30px 30px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        .btn-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .btn-link:hover {
            color: #5a6fd8;
            text-decoration: underline;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .invalid-feedback {
            display: block;
            font-size: 14px;
            margin-top: 5px;
        }

        .is-invalid {
            border-color: #dc3545;
        }

        /* Dark mode support */
        .dark-mode {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
        }

        .dark-mode .auth-card {
            background: rgba(45, 55, 72, 0.95);
            color: #e2e8f0;
        }

        .dark-mode .form-control {
            background: #4a5568;
            border-color: #718096;
            color: #e2e8f0;
        }

        .dark-mode .form-control:focus {
            background: #2d3748;
            border-color: #667eea;
        }

        .dark-mode .form-label {
            color: #e2e8f0;
        }

        /* Responsive design */
        @media (max-width: 576px) {
            .auth-container {
                padding: 15px;
            }
            
            .auth-header {
                padding: 25px 20px;
            }
            
            .auth-title {
                font-size: 24px;
            }
            
            .auth-logo {
                font-size: 40px;
            }
            
            .auth-body {
                padding: 30px 20px 25px;
            }
        }

        /* Animation */
        .auth-card {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Theme Toggle for Auth Pages -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Check for saved theme preference or default to light mode
            const currentTheme = localStorage.getItem('theme') || 'light';
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>

    @stack('scripts')
</body>
</html>