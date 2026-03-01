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
$message = '';
$messageType = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $reportId = (int)$_POST['report_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $reportId);
        
        if ($stmt->execute()) {
            $message = 'Report status updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update report status.';
            $messageType = 'error';
        }
        $stmt->close();
    }
    
    if ($_POST['action'] === 'delete_report') {
        $reportId = (int)$_POST['report_id'];
        
        // Get file path
        $stmt = $conn->prepare("SELECT file_path FROM reports WHERE id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $report = $result->fetch_assoc();
            $filePath = '../uploads/' . $report['file_path'];
            
            // Delete file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->bind_param("i", $reportId);
            
            if ($stmt->execute()) {
                $message = 'Report deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete report.';
                $messageType = 'error';
            }
            $stmt->close();
        }
        $stmt->close();
    }
}

// Get all reports
$reports = [];
$result = $conn->query("SELECT r.*, u.full_name as uploader, u.username FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Get statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM reports");
$stats['total'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM reports WHERE status = 'pending'");
$stats['pending'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM reports WHERE status = 'reviewed'");
$stats['reviewed'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM reports WHERE status = 'resolved'");
$stats['resolved'] = $result->fetch_assoc()['total'];

$conn->close();

// Format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Get status badge class
function getStatusClass($status) {
    switch ($status) {
        case 'pending': return 'badge-warning';
        case 'reviewed': return 'badge-info';
        case 'resolved': return 'badge-success';
        case 'rejected': return 'badge-danger';
        default: return 'badge-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Management - Lapor System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-warning { background-color: #fef3c7; color: #d97706; }
        .badge-info { background-color: #dbeafe; color: #2563eb; }
        .badge-success { background-color: #dcfce7; color: #16a34a; }
        .badge-danger { background-color: #fef2f2; color: #dc2626; }
        .badge-secondary { background-color: #f1f5f9; color: #64748b; }
        .status-select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 13px;
            cursor: pointer;
        }
    </style>
</head>
<body class="dashboard-page">
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="page-header">
                <h1>Reports Management</h1>
                <p>View and manage user submitted reports</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">📁</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Reports</p>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon">👁</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['reviewed']; ?></h3>
                        <p>Reviewed</p>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon">✓</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>All Reports</h2>

                <?php if (empty($reports)): ?>
                    <div class="info-card">
                        <p>No reports submitted yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Uploader</th>
                                    <th>File</th>
                                    <th>Status</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td>#<?php echo $report['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                            <br>
                                            <small style="color: var(--text-secondary);"><?php echo htmlspecialchars(substr($report['description'], 0, 60)) . (strlen($report['description']) > 60 ? '...' : ''); ?></small>
                                        </td>
                                        <td><span class="badge badge-secondary"><?php echo ucfirst($report['category'] ?? 'general'); ?></span></td>
                                        <td>
                                            <?php
                                            $priorityClass = 'badge-secondary';
                                            $priority = $report['priority'] ?? 'medium';
                                            if ($priority === 'urgent') $priorityClass = 'badge-danger';
                                            elseif ($priority === 'high') $priorityClass = 'badge-warning';
                                            elseif ($priority === 'medium') $priorityClass = 'badge-info';
                                            ?>
                                            <span class="badge <?php echo $priorityClass; ?>"><?php echo ucfirst($priority); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($report['uploader']); ?>
                                            <br>
                                            <small style="color: var(--text-secondary);">@<?php echo htmlspecialchars($report['username']); ?></small>
                                        </td>
                                        <td>
                                            <a href="../uploads/<?php echo htmlspecialchars($report['file_path']); ?>" class="download-link" download style="color: var(--primary-color); text-decoration: none;">
                                                📄 <?php echo htmlspecialchars($report['file_name']); ?>
                                            </a>
                                            <br>
                                            <small style="color: var(--text-secondary);"><?php echo formatFileSize($report['file_size']); ?></small>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $report['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="reviewed" <?php echo $report['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                    <option value="resolved" <?php echo $report['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                    <option value="rejected" <?php echo $report['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?php echo date('d M Y, H:i', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="../uploads/<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-sm btn-success" download>⬇ Download</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this report?');">
                                                    <input type="hidden" name="action" value="delete_report">
                                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">🗑 Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
