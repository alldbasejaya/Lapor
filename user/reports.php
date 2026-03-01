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

// Get filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = "r.user_id = {$_SESSION['user_id']}";
if ($filter !== 'all') {
    $where .= " AND r.status = '$filter'";
}
if ($search) {
    $search = sanitize($search);
    $where .= " AND (r.title LIKE '%$search%' OR r.description LIKE '%$search%')";
}

// Get reports
$reports = [];
$result = $conn->query("SELECT r.*, 
    CASE 
        WHEN r.reviewed_by IS NOT NULL THEN (SELECT full_name FROM users WHERE id = r.reviewed_by)
        ELSE NULL
    END as reviewer_name,
    CASE 
        WHEN r.resolved_by IS NOT NULL THEN (SELECT full_name FROM users WHERE id = r.resolved_by)
        ELSE NULL
    END as resolver_name
    FROM reports r 
    WHERE $where
    ORDER BY r.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Get statistics
$stats = [];
$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reports WHERE user_id = {$_SESSION['user_id']}");
$stats = $result->fetch_assoc();

$conn->close();

// Helper functions
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function getStatusClass($status) {
    switch ($status) {
        case 'pending': return 'badge-warning';
        case 'reviewed': return 'badge-info';
        case 'resolved': return 'badge-success';
        case 'rejected': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

function getPriorityClass($priority) {
    switch ($priority) {
        case 'low': return 'badge-secondary';
        case 'medium': return 'badge-info';
        case 'high': return 'badge-warning';
        case 'urgent': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

function getCategoryIcon($category) {
    $icons = [
        'general' => '📋',
        'finance' => '💰',
        'hr' => '👥',
        'operation' => '⚙️',
        'marketing' => '📢',
        'it' => '💻',
        'legal' => '⚖️',
        'other' => '📁'
    ];
    return $icons[$category] ?? '📁';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - Lapor System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-warning { background-color: #fef3c7; color: #d97706; }
        .badge-info { background-color: #dbeafe; color: #2563eb; }
        .badge-success { background-color: #dcfce7; color: #16a34a; }
        .badge-danger { background-color: #fef2f2; color: #dc2626; }
        .badge-secondary { background-color: #f1f5f9; color: #64748b; }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #64748b;
            background: #f1f5f9;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }
        .filter-tab:hover {
            background: #e2e8f0;
        }
        .filter-tab.active {
            background: #4f46e5;
            color: white;
        }
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .search-box input:focus {
            outline: none;
            border-color: #4f46e5;
        }
        .report-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        .report-card:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .report-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .report-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #64748b;
        }
        .report-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .upload-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
            border: none;
            cursor: pointer;
        }
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(79, 70, 229, 0.4);
        }
    </style>
</head>
<body class="dashboard-page">
    <header class="admin-header">
        <div class="header-left">
            <h2 class="logo">Lapor System</h2>
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
                <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📁</span>
                    <span>My Reports</span>
                </a>
                <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
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
                <h1>My Reports</h1>
                <p>View and manage your submitted reports</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">📁</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                        <p>Total</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">👁</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['reviewed'] ?? 0; ?></h3>
                        <p>Reviewed</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['resolved'] ?? 0; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h2>All Reports</h2>
                    <a href="dashboard.php" class="upload-btn">📤 Upload New Report</a>
                </div>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All (<?php echo $stats['total'] ?? 0; ?>)</a>
                    <a href="?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending (<?php echo $stats['pending'] ?? 0; ?>)</a>
                    <a href="?filter=reviewed" class="filter-tab <?php echo $filter === 'reviewed' ? 'active' : ''; ?>">Reviewed (<?php echo $stats['reviewed'] ?? 0; ?>)</a>
                    <a href="?filter=resolved" class="filter-tab <?php echo $filter === 'resolved' ? 'active' : ''; ?>">Resolved (<?php echo $stats['resolved'] ?? 0; ?>)</a>
                    <a href="?filter=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejected (<?php echo $stats['rejected'] ?? 0; ?>)</a>
                </div>

                <!-- Search Box -->
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search reports by title or description..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <?php if ($search): ?>
                        <a href="reports.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if (empty($reports)): ?>
                    <div class="info-card">
                        <p>📭 No reports found.</p>
                        <?php if ($filter !== 'all' || $search): ?>
                            <p>Try changing your filter or search criteria.</p>
                        <?php else: ?>
                            <p>Click "Upload New Report" to submit your first report.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="report-card">
                            <div class="report-header">
                                <div>
                                    <div class="report-title"><?php echo htmlspecialchars($report['title']); ?></div>
                                    <div class="report-meta">
                                        <span><?php echo getCategoryIcon($report['category']); ?> <?php echo ucfirst($report['category']); ?></span>
                                        <span><span class="badge <?php echo getPriorityClass($report['priority']); ?>"><?php echo ucfirst($report['priority']); ?></span></span>
                                        <span>📅 <?php echo date('d M Y, H:i', strtotime($report['created_at'])); ?></span>
                                    </div>
                                </div>
                                <span class="badge <?php echo getStatusClass($report['status']); ?>"><?php echo ucfirst($report['status']); ?></span>
                            </div>
                            
                            <p style="color: #64748b; font-size: 14px; margin-bottom: 15px;">
                                <?php echo htmlspecialchars(substr($report['description'], 0, 200)) . (strlen($report['description']) > 200 ? '...' : ''); ?>
                            </p>
                            
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding: 10px; background: #f8fafc; border-radius: 8px;">
                                <span style="font-size: 20px;">📄</span>
                                <div style="flex: 1;">
                                    <div style="font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($report['file_name']); ?></div>
                                    <div style="font-size: 12px; color: #64748b;"><?php echo formatFileSize($report['file_size']); ?></div>
                                </div>
                                <a href="../uploads/<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-sm btn-success" download>⬇ Download</a>
                            </div>
                            
                            <?php if ($report['status'] !== 'pending'): ?>
                                <div style="padding: 15px; background: #f0fdf4; border-radius: 8px; margin-top: 15px;">
                                    <strong style="color: #16a34a;">✓ Progress:</strong>
                                    <?php if ($report['status'] === 'reviewed'): ?>
                                        <span style="color: #475569;"> Reviewed by <?php echo htmlspecialchars($report['reviewer_name'] ?? 'Supervisor'); ?> on <?php echo date('d M Y', strtotime($report['updated_at'])); ?></span>
                                    <?php elseif ($report['status'] === 'resolved'): ?>
                                        <span style="color: #475569;"> Resolved by <?php echo htmlspecialchars($report['resolver_name'] ?? 'Management'); ?> on <?php echo date('d M Y', strtotime($report['updated_at'])); ?></span>
                                    <?php elseif ($report['status'] === 'rejected'): ?>
                                        <span style="color: #dc2626;"> Rejected - Please review feedback and resubmit</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="report-actions">
                                <a href="../uploads/<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-sm btn-success" download>⬇ Download</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
