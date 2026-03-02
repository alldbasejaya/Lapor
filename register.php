<?php
define('APP_INIT', true);
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'index.php');
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $position = $_POST['position'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm_password) {
        $message = __('register.errors.password_mismatch');
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = __('register.errors.password_short');
        $messageType = 'error';
    } else {
        $conn = getDbConnection();

        // Check if username or email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = __('register.errors.user_exists');
            $messageType = 'error';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, position) VALUES (?, ?, ?, ?, 'user', ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $position);

            if ($stmt->execute()) {
                redirect(BASE_URL . 'index.php?success=registered');
            } else {
                $message = __('register.errors.registration_failed');
                $messageType = 'error';
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('register.title'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <!-- Language Switcher -->
    <?php include 'includes/language_switcher.php'; ?>

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo __('register.title'); ?></h1>
                <p><?php echo __('register.subtitle'); ?></p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="full_name"><?php echo __('register.full_name'); ?></label>
                    <input type="text" id="full_name" name="full_name" required placeholder="<?php echo __('register.full_name_placeholder'); ?>">
                </div>

                <div class="form-group">
                    <label for="username"><?php echo __('register.username'); ?></label>
                    <input type="text" id="username" name="username" required placeholder="<?php echo __('register.username_placeholder'); ?>">
                </div>

                <div class="form-group">
                    <label for="email"><?php echo __('register.email'); ?></label>
                    <input type="email" id="email" name="email" required placeholder="<?php echo __('register.email_placeholder'); ?>">
                </div>

                <div class="form-group">
                    <label><?php echo __('register.position'); ?></label>
                    <select name="position" required>
                        <option value="staff"><?php echo __('register.positions.staff'); ?></option>
                        <option value="kepala_divisi"><?php echo __('register.positions.kepala_divisi'); ?></option>
                        <option value="manager"><?php echo __('register.positions.manager'); ?></option>
                        <option value="direktur"><?php echo __('register.positions.direktur'); ?></option>
                        <option value="kepala_perusahaan"><?php echo __('register.positions.kepala_perusahaan'); ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo __('register.password'); ?></label>
                    <input type="password" id="password" name="password" required placeholder="<?php echo __('register.password_placeholder'); ?>">
                </div>

                <div class="form-group">
                    <label for="confirm_password"><?php echo __('register.confirm_password'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="<?php echo __('register.confirm_password_placeholder'); ?>">
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary"><?php echo __('register.cancel'); ?></a>
                    <button type="submit" class="btn btn-primary"><?php echo __('register.register'); ?></button>
                </div>
            </form>

            <div class="login-footer">
                <p><?php echo __('register.have_account'); ?> <a href="index.php"><?php echo __('register.sign_in_here'); ?></a></p>
            </div>
        </div>
    </div>
</body>
</html>
