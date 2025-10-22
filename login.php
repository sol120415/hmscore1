<?php
include_once 'db.php';
require_once 'google_oauth_config.php';
// Redirect if already logged in
if (isset($_SESSION['email'])) {
    header('Location: dashboard.php');
    exit;
}



// Using MySQL database with schema from login.sql
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Sanitize and validate inputs
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    $password = trim($password);

    $errors = [];

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($email) > 254) {
        $errors[] = 'Email address is too long.';
    }

    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } elseif (strlen($password) > 72) {
        $errors[] = 'Password is too long (maximum 72 characters).';
    }

    // If no validation errors, proceed with login
    if (empty($errors)) {
        // Validate inputs
        if (empty($email) || empty($password)) {
            $error = 'Please fill in both email and password fields.';
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'No account found with this email address.';
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // If a code was already sent within the last minute for this email, reuse it and redirect
                    if (
                        isset($_SESSION['twofa_email']) && $_SESSION['twofa_email'] === $email &&
                        isset($_SESSION['twofa_code']) && isset($_SESSION['twofa_last_sent']) &&
                        (time() - $_SESSION['twofa_last_sent'] < 60)
                    ) {
                        header('Location: verify_2fa.php');
                        exit;
                    }

                    // Generate 2FA code
                    $twofa_code = rand(100000, 999999);
                    $_SESSION['twofa_email'] = $email;
                    $_SESSION['twofa_code'] = $twofa_code;
                    $_SESSION['twofa_expires'] = time() + 300; // 5 minutes

                    // Send 2FA code via email
                    require_once 'email_config.php';
                    $subject = 'Your 2FA Code - CORE1 Hotel Management';
                    $body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .code { font-size: 24px; font-weight: bold; color: #0dcaf0; }
                        </style>
                    </head>
                    <body>
                        <h2>Two-Factor Authentication</h2>
                        <p>Your verification code is:</p>
                        <p class='code'>$twofa_code</p>
                        <p>This code will expire in 5 minutes.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                    </body>
                    </html>
                    ";

                    if (sendEmail($email, $subject, $body)) {
                        $_SESSION['twofa_last_sent'] = time();
                        header('Location: verify_2fa.php');
                        exit;
                    } else {
                        $error = 'Failed to send verification code. Please try again.';
                    }
                } else {
                    $error = 'Incorrect password. Please try again.';
                }
            }
        }
    } else {
        $error = implode(' ', $errors);
    }

    // No HTMX response needed
}
?>
<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CoreUI</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ffffff'%3E%3Cpath d='M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'/%3E%3C/svg%3E">

    <!-- CoreUI CSS -->
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-reboot.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link href="css/coreui-forms.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/all.min.css">

    <script src="js/htmx.min.js"></script>
    <script src="/js/htmx.min.js"></script>

    <link href="loginbg.css" rel="stylesheet">

    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        }
        .login-card {
            width: 300px;
            max-width: 400px;
        }
        .btn-google {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            backdrop-filter: blur(2px);
        }
        .btn-google:hover { background: rgba(255,255,255,0.12); }
        .gmail-icon {
            width: 18px;
            height: 18px;
            display: inline-block;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path fill="%23FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.8 32.9 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.1 29.7 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c10 0 19-7.3 19-20 0-1.3-.1-2.3-.4-3.5z"/><path fill="%234CAF50" d="M6.3 14.7l6.6 4.8C14.7 16.2 18.9 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.1 29.7 4 24 4 16 4 8.9 8.4 6.3 14.7z"/><path fill="%232196F3" d="M24 44c5.2 0 10-1.8 13.6-4.9l-6.3-5.2C29.2 35.7 26.7 36 24 36c-5.2 0-9.6-3.5-11.2-8.2l-6.6 5.1C8.9 39.6 15.9 44 24 44z"/><path fill="%23F44336" d="M43.6 20.5H42V20H24v8h11.3c-1.9 4.9-6.4 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.1 29.7 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c10 0 19-7.3 19-20 0-1.3-.1-2.3-.4-3.5z"/></svg>') no-repeat center/contain;
        }
    </style>
</head>
<body>
<div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container"></div>
<header>

    <?php //include 'header.php'; ?>

</header>

<section>
  <div class="wave">
    <span></span>
    <span></span>
    <span></span>
  </div>
  <div class="content">
  <div class="card login-card shadow-lg">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="logo-container mb-3">
                                    <h1 class="hotel-logo" style="font-family: 'Poppins', sans-serif; font-weight: 700; color: #ffffff; font-size: 2.5rem; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); letter-spacing: 3px;">
                                        CORE<span class="ms-3" style="color: #0dcaf0;">1</span>
                                    </h1>
                                    <p class="text-muted" style="font-size: 0.9rem; margin: 5px 0 0 0; letter-spacing: 1px;">HOTEL  MANAGEMENT SYSTEM</p>
                                </div>
                            </div>

                            <div class="text-center mb-4">
                                <?php if (isset($error)) { ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                                <?php } ?>

                            <form action="login.php" method="post" class="needs-validation" novalidate>
                                <!-- Email Input Group -->
                                <div class="mb-3">
                                    
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg class="icon" style="width: 1.2rem; height: 1.2rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                <polyline points="22,6 12,13 2,6"></polyline>
                                            </svg>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" aria-describedby="email-addon" required>
                                    </div>
                                    <div id="email-feedback" class="validation-feedback"></div>
                                </div>

                                <!-- Password Input Group with Remember Me -->
                                <div class="mb-3">
                                    
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg class="icon" style="width: 1.2rem; height: 1.2rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <circle cx="12" cy="16" r="1"></circle>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Toggle password visibility">
                                            <svg class="icon">
                                                <use xlink:href="#cil-eye"></use>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="password-feedback" class="validation-feedback"></div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-outline-light">Login</button>
                                </div>

                                <!-- Links -->
                                <div class="text-center">
                                    <a href="forgot_password.php" class="text-decoration-none">
                                        <small>Forgot password?</small>
                                    </a>
                                </div>

                                <hr class="my-4">

                                <div class="text-center text-muted mb-3">OR</div>

                                <div class="d-grid mb-3">
                                    <button type="button" id="googleLoginBtn" class="btn btn-google">Login via Gmail</button>
                                </div>

                                <div class="text-center">
                                    <small class="text-muted">
                                        Don't have an account?
                                        <a href="register.php" class="text-decoration-none">Sign up</a>
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>

  </div>
</section>

    <!-- Load CoreUI Free Icons -->
    <script src="js/free/free-set.js"></script>
    <!-- Load CoreUI Brand Icons -->
    <script src="js/brand/brand-set.js"></script>

    <!-- SVG Sprite for CoreUI Icons -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <defs>
            <symbol id="cil-account" viewBox="0 0 512 512">
                <path fill="currentColor" d="M256,288c79.5,0,144-64.5,144-144S335.5,0,256,0,112,64.5,112,144,176.5,288,256,288ZM256,32c61.8,0,112,50.2,112,112s-50.2,112-112,112-112-50.2-112-112S194.2,32,256,32ZM480,512v-32c0-88.2-71.8-160-160-160H192c-88.2,0-160,71.8-160,160v32H480Z"/>
            </symbol>
            <symbol id="cil-envelope-closed" viewBox="0 0 512 512">
                <path fill="currentColor" d="M0 128l256 128 256-128v256H0V128zM0 96h512v32L256 256 0 128V96z"/>
            </symbol>
            <symbol id="cil-eye" viewBox="0 0 512 512">
                <path fill="currentColor" d="M256,128c-81.9,0-159.4,51.7-227.2,144 67.8,92.3,145.3,144,227.2,144s159.4-51.7,227.2-144C415.4,179.7,337.9,128,256,128z M256,352c-53,0-96-43-96-96s43-96,96-96,96,43,96,96S309,352,256,352z"/>
            </symbol>
            <symbol id="cil-warning" viewBox="0 0 512 512">
                <path fill="currentColor" d="M256,32L32,480h448L256,32z M256,160l96,256H160L256,160z M256,320c-17.6,0-32,14.4-32,32s14.4,32,32,32,32-14.4,32-32S273.6,320,256,320z"/>
            </symbol>
        </defs>
    </svg>

    <?php if (isset($db_status)) { ?>
    <div class="debug-info">
        <?php echo $db_status; ?>
    </div>
    <?php } ?>



    <!-- CoreUI JS -->
    <script src="js/coreui.bundle.js"></script>
    <script src="js/bootstrap.bundle.js"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize toasts after HTMX swap
            document.body.addEventListener('htmx:afterSwap', function(evt) {
                const toasts = evt.detail.target.querySelectorAll('.toast');
                toasts.forEach(toastEl => {
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                });
            });
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle icon
                const icon = this.querySelector('.icon use');
                const href = type === 'password' ? '#cil-eye' : '#cil-eye-closed';
                icon.setAttribute('xlink:href', href);
            });

            // Real-time validation
            const emailInput = document.getElementById('email');
            const submitBtn = document.querySelector('button[type="submit"]');

            // Email validation on input
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (email === '') {
                    this.classList.remove('is-valid', 'is-invalid');
                    hideValidationMessage('email');
                } else if (emailRegex.test(email)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    showValidationMessage('email', 'Looks good!', 'valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    showValidationMessage('email', 'Please enter a valid email address.', 'invalid');
                }
            });


            // Form submission validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                const password = passwordInput.value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                let isValid = true;

                // Validate email
                if (email === '') {
                    emailInput.classList.add('is-invalid');
                    showValidationMessage('email', 'Email address is required.', 'invalid');
                    isValid = false;
                } else if (!emailRegex.test(email)) {
                    emailInput.classList.add('is-invalid');
                    showValidationMessage('email', 'Please enter a valid email address.', 'invalid');
                    isValid = false;
                }

                // Validate password
                if (password === '') {
                    passwordInput.classList.add('is-invalid');
                    showValidationMessage('password', 'Password is required.', 'invalid');
                    isValid = false;
                } else if (password.length < 6) {
                    passwordInput.classList.add('is-invalid');
                    showValidationMessage('password', 'Password must be at least 6 characters.', 'invalid');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    // Errors are already shown inline
                }
            });

            function showValidationMessage(field, message, type) {
                const feedbackElement = document.querySelector(`#${field}-feedback`);
                if (feedbackElement) {
                    feedbackElement.textContent = message;
                    feedbackElement.className = `validation-feedback ${type}-feedback`;
                }
            }

            function hideValidationMessage(field) {
                const feedbackElement = document.querySelector(`#${field}-feedback`);
                if (feedbackElement) {
                    feedbackElement.textContent = '';
                    feedbackElement.className = 'validation-feedback';
                }
            }

            function showToast(message, type = 'error') {
                const toastContainer = document.getElementById('toast-container');
                const toastId = 'toast-' + Date.now();
                const toastDiv = document.createElement('div');
                toastDiv.id = toastId;
                toastDiv.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'success'} border-0`;
                toastDiv.setAttribute('role', 'alert');
                toastDiv.setAttribute('aria-live', 'assertive');
                toastDiv.setAttribute('aria-atomic', 'true');
                toastDiv.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
                toastContainer.appendChild(toastDiv);
                const toast = new bootstrap.Toast(toastDiv);
                toast.show();
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    toast.hide();
                }, 5000);
            }
        });

        // Trigger Google One Tap chooser on custom button
        document.addEventListener('click', function(e){
            if (e.target && (e.target.id === 'googleLoginBtn' || e.target.closest('#googleLoginBtn'))) {
                if (window.google && window.google.accounts && window.google.accounts.id) {
                    google.accounts.id.initialize({
                        client_id: '<?php echo defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'; ?>',
                        callback: handleGoogleSignIn,
                        ux_mode: 'popup',
                    });
                    google.accounts.id.prompt(); // shows account chooser
                }
            }
        });

        // Google Sign-In callback
        function handleGoogleSignIn(response) {
            const formData = new URLSearchParams();
            formData.append('credential', response.credential);
            fetch('google_callback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            }).then(r => r.json())
              .then(data => {
                  if (data && data.success) {
                      window.location.href = 'dashboard.php';
                  } else {
                      showToast('Google sign-in failed.', 'error');
                  }
              }).catch(() => showToast('Google sign-in failed.', 'error'));
        }
    </script>

    <style>
        .validation-feedback {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
            min-height: 1.25rem; /* Reserve space to prevent layout shift */
        }

        .valid-feedback {
            color: #198754;
        }

        .invalid-feedback {
            color: #dc3545;
        }

        /* Ensure card maintains consistent height like verify_2fa */
        .login-card .card-body {
            padding-bottom: 2rem;
        }

        .form-control.is-valid {
            border-color: #198754;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

    </style>
</body>
</html>