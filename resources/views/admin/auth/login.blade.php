<!doctype html>
<html lang="en" class="layout-wide customizer-hide" dir="ltr" data-skin="default"
    data-template="vertical-menu-template" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Admin Login | Zupply</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/logo.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 25%, #7c3aed 50%, #6d28d9 75%, #5b21b6 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Public Sans', sans-serif;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5);
            padding: 2rem 2rem;
            animation: slideUp 0.6s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.5);
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

        .logo-container {
            text-align: center;
            margin-bottom: 1rem;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .logo-container img {
            width: 140px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.05);
        }

        .welcome-text {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .welcome-text h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-text p {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            transition: all 0.3s ease;
            color: #000000 !important;
        }

        .form-control:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
            transform: translateY(-2px);
            color: #000000 !important;
        }

        .form-control::placeholder {
            color: #000000 !important;
            opacity: 0.6;
        }

        .input-group {
            position: relative;
        }

        .input-group-merge {
            display: flex;
            align-items: center;
        }

        .input-group-merge .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-right: none;
        }

        .input-group-text {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-left: none;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            padding: 0.7rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
        }

        .input-group-text:hover {
            background: #f8fafc;
            color: #8b5cf6;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-top: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
            cursor: pointer;
            accent-color: #8b5cf6;
        }

        .form-check-label {
            color: #64748b;
            font-size: 0.85rem;
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
            position: relative;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border: none;
            font-weight: 500;
            font-size: 0.9rem;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error {
            font-size: 0.8rem;
            margin-top: 0.35rem;
            color: #ef4444;
            font-weight: 500;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        /* Floating shapes decoration */
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -100px;
            right: -100px;
            animation-delay: 5s;
        }

        .shape:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            right: -75px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }
            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 1.5rem 1.25rem;
            }

            .welcome-text h4 {
                font-size: 1.3rem;
            }

            .logo-container img {
                width: 120px;
            }
        }

        .container-xxl {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .authentication-wrapper {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }

        .authentication-inner {
            padding: 0 !important;
        }
    </style>
</head>

<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <div class="login-container">
                    <div class="login-card">
                        <!-- Logo -->
                        <div class="logo-container">
                            <img src="{{ asset('assets/img/logo.png') }}" alt="Zupply Logo" />
                        </div>

                        <!-- Welcome Text -->
                        <div class="welcome-text">
                            <h4>Welcome Back! 👋</h4>
                            <p>Sign in to your admin account to continue</p>
                        </div>

                        <!-- Alert Messages -->
                        <div id="alert-message" class="alert d-none"></div>

                        <!-- Login Form -->
                        <form id="login-form" action="{{ route('admin.login.post') }}" method="POST">
                            @csrf
                            
                            <!-- Email Field -->
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="icon-base ti tabler-mail me-2"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email"
                                       placeholder="Enter your email"
                                       style="color: #000000 !important;" 
                                       value="{{ old('email') }}" 
                                       autofocus 
                                       required />
                                <div class="error" id="email-error"></div>
                            </div>

                            <!-- Password Field -->
                            <div class="form-group">
                                <label class="form-label" for="password">
                                    <i class="icon-base ti tabler-lock me-2"></i>Password
                                </label>
                                <div class="input-group input-group-merge">
                                    <input type="password" 
                                           id="password"
                                           class="form-control" 
                                           name="password"
                                           placeholder="Enter your password"
                                           style="color: #000000 !important;"
                                           required />
                                    <span class="input-group-text" id="toggle-password">
                                        <i class="icon-base ti tabler-eye-off"></i>
                                    </span>
                                </div>
                                <div class="error" id="password-error"></div>
                            </div>
                            
                            <!-- Remember Me & Submit Button -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
                                <div class="form-check" style="margin: 0;">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="remember"
                                           id="remember-me" />
                                    <label class="form-check-label" for="remember-me">
                                        Remember me
                                    </label>
                                </div>
                                
                                <button class="btn-login" type="submit" id="login-btn" style="width: auto; padding: 0.7rem 2rem; margin: 0;">
                                    <span id="login-btn-text">Sign In</span>
                                    <span id="login-btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#toggle-password').on('click', function() {
                const passwordInput = $('#password');
                const icon = $(this).find('i');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('tabler-eye-off').addClass('tabler-eye');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('tabler-eye').addClass('tabler-eye-off');
                }
            });

            // Handle form submission with AJAX
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                
                // Clear previous errors
                clearErrors();
                hideAlert();
                
                // Disable submit button and show loading
                const submitBtn = $('#login-btn');
                const btnText = $('#login-btn-text');
                const btnSpinner = $('#login-btn-spinner');
                
                submitBtn.prop('disabled', true);
                btnText.addClass('d-none');
                btnSpinner.removeClass('d-none');
                
                // Get form data
                const formData = $(this).serialize();
                const url = $(this).attr('action') || '{{ route("admin.login.post") }}';
                
                // AJAX request
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showAlert('success', response.message || 'Login successful! Redirecting...');
                            
                            // Redirect after short delay
                            setTimeout(function() {
                                window.location.href = response.redirect || '{{ route("admin.dashboard") }}';
                            }, 500);
                        } else {
                            // Show error message
                            showAlert('danger', response.message || 'Login failed. Please try again.');
                            showFieldErrors(response.errors || {});
                            
                            // Re-enable submit button
                            submitBtn.prop('disabled', false);
                            btnText.removeClass('d-none');
                            btnSpinner.addClass('d-none');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON?.errors || {};
                            const message = xhr.responseJSON?.message || 'Validation failed. Please check your inputs.';
                            
                            showAlert('danger', message);
                            showFieldErrors(errors);
                        } else if (xhr.status === 401 || xhr.status === 403) {
                            errorMessage = xhr.responseJSON?.message || 'Invalid credentials. Please try again.';
                            showAlert('danger', errorMessage);
                        } else {
                            showAlert('danger', errorMessage);
                        }
                        
                        // Re-enable submit button
                        submitBtn.prop('disabled', false);
                        btnText.removeClass('d-none');
                        btnSpinner.addClass('d-none');
                    }
                });
            });
            
            // Function to show alert message
            function showAlert(type, message) {
                const alertDiv = $('#alert-message');
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                alertDiv.removeClass('d-none alert-success alert-danger alert-warning alert-info')
                         .addClass(alertClass)
                         .html('<i class="icon-base ti ' + (type === 'success' ? 'tabler-check' : 'tabler-alert-circle') + ' me-2"></i>' + message);
            }
            
            // Function to hide alert message
            function hideAlert() {
                $('#alert-message').addClass('d-none').removeClass('alert-success alert-danger alert-warning alert-info');
            }
            
            // Function to show field errors
            function showFieldErrors(errors) {
                $.each(errors, function(field, messages) {
                    const errorDiv = $('#' + field + '-error');
                    if (errorDiv.length) {
                        errorDiv.html(Array.isArray(messages) ? messages[0] : messages);
                        $('#' + field).addClass('is-invalid');
                    }
                });
            }
            
            // Function to clear all errors
            function clearErrors() {
                $('.error').html('');
                $('.form-control').removeClass('is-invalid');
            }
        });
    </script>
</body>

</html>
