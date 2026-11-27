<?php
session_start();
require_once __DIR__ . '/dbcon.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';

if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = trim($_POST['username'] ?? '');
    $password_input = trim($_POST['password'] ?? '');

    if (empty($user_input) || empty($password_input)) {
        $_SESSION['login_error'] = 'Please enter both username/email and password.';
        header('Location: login.php');
        exit;
    } else {
        $authenticated = false;
        $user_data = null;

        if ($db_connected && $conn) {
            $stmt = $conn->prepare("SELECT user_id, role, first_name, last_name, email, username, password FROM users WHERE username = ? OR email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $user_input, $user_input);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    // Verify plain-text or hashed password
                    if ($password_input === $row['password'] || password_verify($password_input, $row['password'])) {
                        $authenticated = true;
                        $user_data = $row;
                    }
                }
                $stmt->close();
            }
        } else {
            // Standalone Demo Mode Fallback
            if (($user_input === 'admin' || $user_input === 'ezekielreyes@gmail.com') && $password_input === 'admin') {
                $authenticated = true;
                $user_data = [
                    'user_id' => 1,
                    'first_name' => 'Ezekiel',
                    'last_name' => 'Reyes',
                    'email' => 'ezekielreyes@gmail.com',
                    'username' => 'admin'
                ];
            }
        }

        if ($authenticated && $user_data) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_data['user_id'];
            $_SESSION['role'] = strtolower(trim($user_data['role'] ?? 'admin'));
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
            $_SESSION['email'] = $user_data['email'];

            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_error'] = 'Invalid username/email or password. Please try again.';
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - AquaSync System Panel</title>
    <!-- Favicon / Water drop icon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230284c7'><path d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/></svg>">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="bootstrap5/css/bootstrap.min.css">
    
    <!-- Modular Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 (local) -->
    <script src="bootstrap5/js/sweetalert2.all.min.js"></script>
</head>
<body class="login-body login-body-minimal d-flex flex-column min-vh-100">

    <!-- TOP-LEFT SYSTEM BRAND LOGO -->
    <header class="pt-4 px-4 px-sm-5 pb-2 d-flex align-items-center">
        <a href="landing.php" class="brand-header text-decoration-none">
            <div class="brand-logo-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
            </div>
            <div class="brand-text-container">
                <span class="brand-title lh-1">AquaSync</span>
                <small class="text-muted" style="font-size: 0.72rem;">Smart Auto Refilling System</small>
            </div>
        </a>
    </header>

    <!-- CENTERED FLOATING SIGN-IN CARD -->
    <main class="flex-grow-1 d-flex align-items-center justify-content-center px-3 pt-3 pb-5">
        <div class="w-100" style="max-width: 420px;">
            <div class="login-floating-card">
                <div class="login-card-head">
                    <h2 class="login-card-title">Welcome back</h2>
                    <p class="login-card-subtitle">Sign in to your account to continue</p>
                </div>

                <!-- Alert messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger py-2 px-3 rounded-3 small mb-3 d-flex align-items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="login.php" class="login-form">
                    <div class="login-form-group mb-3">
                        <label for="username" class="login-form-label">Username</label>
                        <input type="text" class="form-control login-input" id="username" name="username" placeholder="Enter your username" autocomplete="username" required autofocus>
                    </div>

                    <div class="login-form-group mb-3">
                        <label for="password" class="login-form-label">Password</label>
                        <div class="login-password-wrapper">
                            <input type="password" class="form-control login-input" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="login-password-toggle" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                                <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="login-form-options d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                            <label class="form-check-label small text-secondary" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary login-btn-submit w-100">
                        Sign In
                    </button>
                </form>

                <!-- Staff Portal Divider -->
                <div class="login-card-divider mb-0">
                    <span>AQUASYNC STAFF PORTAL</span>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Remove ?logged_out=1 from URL so the logout message disappears on refresh
        if (window.history && window.history.replaceState && window.location.search.includes('logged_out')) {
            window.history.replaceState(null, '', window.location.pathname);
        }

        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const toggleButton = document.querySelector('.login-password-toggle');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                if (toggleButton) {
                    toggleButton.setAttribute('aria-label', 'Hide password');
                    toggleButton.setAttribute('aria-pressed', 'true');
                }
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                pwd.type = 'password';
                if (toggleButton) {
                    toggleButton.setAttribute('aria-label', 'Show password');
                    toggleButton.setAttribute('aria-pressed', 'false');
                }
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        }
    </script>
</body>
</html>
