<?php
define('APP_INIT', true);
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    redirect(BASE_URL . 'index.php?error=access');
}

if (!isAdmin()) {
    redirect(BASE_URL . 'user/dashboard.php');
}

$conn = getDbConnection();

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Active users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['active_users'] = $result->fetch_assoc()['total'];

// Total reports
$result = $conn->query("SELECT COUNT(*) as total FROM reports");
$stats['total_reports'] = $result->fetch_assoc()['total'];

// Pending reports
$result = $conn->query("SELECT COUNT(*) as total FROM reports WHERE status = 'pending'");
$stats['pending_reports'] = $result->fetch_assoc()['total'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin.dashboard'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="page-header">
                <h1><?php echo __('common.dashboard'); ?></h1>
                <p><?php echo str_replace('{name}', htmlspecialchars($_SESSION['full_name']), __('admin.welcome')); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p><?php echo __('common.total_users'); ?></p>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon">✓</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active_users']; ?></h3>
                        <p><?php echo __('common.active_users'); ?></p>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-icon">📁</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending_reports']; ?></h3>
                        <p><?php echo __('common.pending_reports'); ?></p>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_reports']; ?></h3>
                        <p><?php echo __('common.total_reports'); ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h2><?php echo __('common.quick_actions'); ?></h2>
                <div class="actions-grid">
                    <a href="reports.php" class="action-card">
                        <span class="action-icon">📁</span>
                        <span><?php echo __('admin.manage_reports'); ?></span>
                    </a>
                    <a href="users.php" class="action-card">
                        <span class="action-icon">👤</span>
                        <span><?php echo __('admin.manage_users'); ?></span>
                    </a>
                    <a href="profile.php" class="action-card">
                        <span class="action-icon">⚙</span>
                        <span><?php echo __('common.settings'); ?></span>
                    </a>
                    <a href="../auth/logout.php" class="action-card">
                        <span class="action-icon">🚪</span>
                        <span><?php echo __('common.logout'); ?></span>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
