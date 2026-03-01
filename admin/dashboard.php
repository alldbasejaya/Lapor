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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lapor System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-main">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon">✓</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active_users']; ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-icon">📁</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending_reports']; ?></h3>
                        <p>Pending Reports</p>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_reports']; ?></h3>
                        <p>Total Reports</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2>Quick Actions</h2>
                <div class="actions-grid">
                    <a href="reports.php" class="action-card">
                        <span class="action-icon">📁</span>
                        <span>Manage Reports</span>
                    </a>
                    <a href="users.php" class="action-card">
                        <span class="action-icon">👤</span>
                        <span>Manage Users</span>
                    </a>
                    <a href="profile.php" class="action-card">
                        <span class="action-icon">⚙</span>
                        <span>Settings</span>
                    </a>
                    <a href="../auth/logout.php" class="action-card">
                        <span class="action-icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
