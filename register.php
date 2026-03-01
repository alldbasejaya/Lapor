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
        $message = 'Passwords do not match!';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters!';
        $messageType = 'error';
    } else {
        $conn = getDbConnection();

        // Check if username or email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = 'Username or email already exists!';
            $messageType = 'error';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, position) VALUES (?, ?, ?, ?, 'user', ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $position);

            if ($stmt->execute()) {
                redirect(BASE_URL . 'index.php?success=registered');
            } else {
                $message = 'Registration failed. Please try again.';
                $messageType = 'error';
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Lapor System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Create Account</h1>
                <p>Register for a new account</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Choose a username">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label>Position</label>
                    <select name="position" required>
                        <option value="staff">Staff</option>
                        <option value="kepala_divisi">Kepala Divisi</option>
                        <option value="manager">Manager</option>
                        <option value="direktur">Direktur</option>
                        <option value="kepala_perusahaan">Kepala Perusahaan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <div class="login-footer">
                <p>Already have an account? <a href="index.php">Sign in here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
