<?php
define('APP_INIT', true);
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lapor System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Lapor System</h1>
                <p>Sign in to your account</p>
            </div>
            
            <?php if (isset($_GET['error']) || isset($_SESSION['login_error'])): ?>
                <div class="alert alert-error">
                    <?php
                    // Show detailed error if exists
                    if (isset($_SESSION['login_error'])) {
                        echo htmlspecialchars($_SESSION['login_error']);
                        unset($_SESSION['login_error']);
                    } else {
                        $error = $_GET['error'];
                        if ($error === 'invalid') echo 'Invalid username or password!';
                        elseif ($error === 'inactive') echo 'Your account has been deactivated. Please contact administrator.';
                        elseif ($error === 'logout') echo 'You have been logged out successfully.';
                        elseif ($error === 'access') echo 'Please login to access this page.';
                        else echo 'An error occurred. Please try again.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    $success = $_GET['success'];
                    if ($success === 'registered') echo 'Registration successful! Please login.';
                    elseif ($success === 'reset') echo 'Password reset successful! Please login with your new password.';
                    ?>
                </div>
            <?php endif; ?>
            
            <form action="auth/login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
