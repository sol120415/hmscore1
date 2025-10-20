<?php
include_once 'db.php';

// Redirect if not logged in or no 2FA session
if (!isset($_SESSION['twofa_email']) || !isset($_SESSION['twofa_code'])) {
    header('Location: login.php');
    exit;
}

// Check if 2FA code has expired
if (time() > $_SESSION['twofa_expires']) {
    unset($_SESSION['twofa_email'], $_SESSION['twofa_code'], $_SESSION['twofa_expires']);
    $error = 'Verification code has expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';

    // Sanitize input
    $code = trim($code);

    $errors = [];

    // Code validation
    if (empty($code)) {
        $errors[] = 'Verification code is required.';
    } elseif (!is_numeric($code) || strlen($code) !== 6) {
        $errors[] = 'Please enter a valid 6-digit verification code.';
    }

    if (empty($errors)) {
        // Verify the code
        if ($code == $_SESSION['twofa_code']) {
            // Get user details
            $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['twofa_email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Set session variables
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['verified'] = true;

                // Clear 2FA session data
                unset($_SESSION['twofa_email'], $_SESSION['twofa_code'], $_SESSION['twofa_expires']);

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'User not found. Please try logging in again.';
            }
        } else {
            $error = 'Invalid verification code. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Authentication - CoreUI</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ffffff'%3E%3Cpath d='M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'/%3E%3C/svg%3E">

    <!-- CoreUI CSS -->
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-reboot.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link href="css/coreui-forms.min.css" rel="stylesheet">

    <link href="loginbg.css" rel="stylesheet">

    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        }
        .verify-card {
            width: 100%;
            max-width: 400px;
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
  <div class="card verify-card shadow-lg">
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
                                <h4 class="text-white">Two-Factor Authentication</h4>
                                <p class="text-muted">Enter the 6-digit code sent to your email</p>
                            </div>

                            <form action="verify_2fa.php" method="post" class="needs-validation" novalidate hx-post="verify_2fa.php" hx-target="#toast-container" hx-swap="beforeend">
                                <!-- Code Input Group -->
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg class="icon" style="width: 1.2rem; height: 1.2rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <circle cx="12" cy="16" r="1"></circle>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                        </span>
                                        <input type="text" class="form-control text-center" id="code" name="code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" autocomplete="off" required>
                                        
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-outline-light">Verify Code</button>
                                </div>

                                <!-- Links -->
                                <div class="text-center">
                                    <a href="login.php" class="text-decoration-none">
                                        <small>Back to Login</small>
                                    </a>
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

    <?php if (isset($error)) { ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="2000">
            <div class="d-flex">
                <div class="toast-body"><?php echo htmlspecialchars($error); ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- CoreUI JS -->
    <script src="js/coreui.bundle.js"></script>
    <script src="js/bootstrap.bundle.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize toasts on page load
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toastEl => {
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            });

            // Initialize toasts after HTMX swap
            document.body.addEventListener('htmx:afterSwap', function(evt) {
                const toasts = evt.detail.target.querySelectorAll('.toast');
                toasts.forEach(toastEl => {
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                });
            });

            const codeInput = document.getElementById('code');

            // Auto-focus on code input
            codeInput.focus();

            // Real-time validation
            codeInput.addEventListener('input', function() {
                const code = this.value.trim();

                if (code === '') {
                    this.classList.remove('is-valid', 'is-invalid');
                    hideValidationMessage('code');
                } else if (/^\d{6}$/.test(code)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    showValidationMessage('code', 'Looks good!', 'valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    showValidationMessage('code', 'Please enter a valid 6-digit verification code.', 'invalid');
                }
            });


            function showValidationMessage(field, message, type) {
                let feedbackElement = document.querySelector(`#${field}-feedback`);
                if (!feedbackElement) {
                    feedbackElement = document.createElement('div');
                    feedbackElement.id = `${field}-feedback`;
                    feedbackElement.className = 'validation-feedback';
                    document.querySelector(`#${field}`).parentNode.appendChild(feedbackElement);
                }

                feedbackElement.textContent = message;
                feedbackElement.className = `validation-feedback ${type}-feedback`;
            }

            function hideValidationMessage(field) {
                const feedbackElement = document.querySelector(`#${field}-feedback`);
                if (feedbackElement) {
                    feedbackElement.textContent = '';
                }
            }
        });
    </script>

    <style>
        .validation-feedback {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .valid-feedback {
            color: #198754;
        }

        .invalid-feedback {
            color: #dc3545;
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

        .error-alert {
            border-radius: 12px !important;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3) !important;
            margin-bottom: 1rem;
        }
    </style>
</body>
</html>