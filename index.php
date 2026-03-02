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
<html lang="<?php echo htmlspecialchars(getCurrentLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login.title'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <!-- Language Switcher -->
    <?php include 'includes/language_switcher.php'; ?>

    <!-- Animated Background Bubbles -->
    <div class="login-bubbles">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo __('app_name'); ?></h1>
                <p><?php echo __('login.subtitle'); ?></p>
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
                        if ($error === 'invalid') echo __('login.errors.invalid');
                        elseif ($error === 'inactive') echo __('login.errors.inactive');
                        elseif ($error === 'logout') echo __('login.errors.logout');
                        elseif ($error === 'access') echo __('login.errors.access');
                        else echo __('login.errors.generic');
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    $success = $_GET['success'];
                    if ($success === 'registered') echo __('login.success.registered');
                    elseif ($success === 'reset') echo __('login.success.reset');
                    ?>
                </div>
            <?php endif; ?>

            <form action="auth/login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username"><?php echo __('login.username'); ?></label>
                    <input type="text" id="username" name="username" required autofocus placeholder="<?php echo __('login.username_placeholder'); ?>">
                </div>

                <div class="form-group">
                    <label for="password"><?php echo __('login.password'); ?></label>
                    <input type="password" id="password" name="password" required placeholder="<?php echo __('login.password_placeholder'); ?>">
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span><?php echo __('login.remember_me'); ?></span>
                    </label>
                    <a href="#" class="forgot-link"><?php echo __('login.forgot_password'); ?></a>
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?php echo __('login.sign_in'); ?></button>
            </form>

            <div class="login-footer">
                <p><?php echo __('login.no_account'); ?> <a href="register.php"><?php echo __('login.register_here'); ?></a></p>
            </div>
        </div>
    </div>
</body>
</html>
