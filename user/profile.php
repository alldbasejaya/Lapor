<?php
define('APP_INIT', true);
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . 'index.php?error=access');
}

// Redirect admin to admin dashboard
if (isAdmin()) {
    redirect(BASE_URL . 'admin/dashboard.php');
}

$conn = getDbConnection();
$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_profile') {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $position = $_POST['position'];

        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, position = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $position, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $message = 'Profile updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update profile.';
            $messageType = 'error';
        }
        $stmt->close();
    }

    if ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Get current password from database
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($current_password, $user['password'])) {
            $message = 'Current password is incorrect!';
            $messageType = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match!';
            $messageType = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password must be at least 6 characters!';
            $messageType = 'error';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);

            if ($stmt->execute()) {
                $message = 'Password changed successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to change password.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Lapor System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <header class="admin-header">
        <div class="header-left">
            <h2 class="logo">Lapor</h2>
        </div>
        <div class="header-right">
            <span class="user-info">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <span class="badge badge-user"><?php echo ucfirst($_SESSION['role']); ?></span>
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="profile.php" class="nav-item active">
                    <span class="nav-icon">⚙</span>
                    <span>Profile Settings</span>
                </a>
                <a href="../index.php" class="nav-item">
                    <span class="nav-icon">🏠</span>
                    <span>Home</span>
                </a>
                <a href="../auth/logout.php" class="nav-item nav-logout">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="dashboard-main">
            <div class="page-header">
                <h1>Profile Settings</h1>
                <p>Manage your account settings</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <h2>Profile Information</h2>
                <form method="POST" class="modal-form" style="max-width: 500px;">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small style="color: var(--text-secondary);">Username cannot be changed</small>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <select name="position" required>
                            <option value="staff" <?php echo $user['position'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="kepala_divisi" <?php echo $user['position'] === 'kepala_divisi' ? 'selected' : ''; ?>>Kepala Divisi</option>
                            <option value="manager" <?php echo $user['position'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="direktur" <?php echo $user['position'] === 'direktur' ? 'selected' : ''; ?>>Direktur</option>
                            <option value="kepala_perusahaan" <?php echo $user['position'] === 'kepala_perusahaan' ? 'selected' : ''; ?>>Kepala Perusahaan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="dashboard-section">
                <h2>Change Password</h2>
                <form method="POST" class="modal-form" style="max-width: 500px;">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
