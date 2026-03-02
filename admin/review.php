<?php
/**
 * Review Reports - Untuk semua jabatan dengan akses review
 * Kepala Divisi, Manager, Direktur, Kepala Perusahaan, Admin
 */
define('APP_INIT', true);
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . 'index.php?error=access');
}

$conn = getDbConnection();
$message = '';
$messageType = '';

function getCategoryLabel($category) {
    $labels = [
        'staff' => 'Staff',
        'kepala_divisi' => 'Kepala Divisi',
        'manager' => 'Manager',
        'direktur' => 'Direktur',
        'kepala_perusahaan' => 'Kepala Perusahaan'
    ];
    return $labels[$category] ?? ucfirst($category);
}

// Check user position and role
$position = $_SESSION['position'] ?? 'staff';
$isAdmin = $_SESSION['role'] === 'admin';
$canReview = $isAdmin || in_array($position, ['kepala_divisi', 'manager', 'direktur', 'kepala_perusahaan']);

if (!$canReview) {
    redirect(BASE_URL . 'user/dashboard.php');
}

// Handle review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reportId = (int)$_POST['report_id'];
    $action = $_POST['action'];
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($action === 'approve') {
        // Determine new status based on position
        $newStatus = 'resolved';
        $updateField = 'resolved_by';

        if (!$isAdmin) {
            switch ($position) {
                case 'kepala_divisi':
                case 'manager':
                    $newStatus = 'reviewed';
                    $updateField = 'reviewed_by';
                    break;
                case 'direktur':
                case 'kepala_perusahaan':
                    $newStatus = 'resolved';
                    $updateField = 'resolved_by';
                    break;
            }
        }

        $stmt = $conn->prepare("UPDATE reports SET status = ?, notes = ?, $updateField = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssii", $newStatus, $notes, $_SESSION['user_id'], $reportId);

        if ($stmt->execute()) {
            $message = "Report approved successfully! Status: $newStatus";
            $messageType = 'success';
        } else {
            $message = 'Failed to approve report.';
            $messageType = 'error';
        }
        $stmt->close();
        
    } elseif ($action === 'revise') {
        $stmt = $conn->prepare("UPDATE reports SET status = 'rejected', notes = ?, reviewed_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sii", $notes, $_SESSION['user_id'], $reportId);
        
        if ($stmt->execute()) {
            $message = 'Report sent back for revision!';
            $messageType = 'success';
        } else {
            $message = 'Failed to revise report.';
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Get reports based on position hierarchy
$reports = [];
$positionHierarchy = [
    'staff' => 0,
    'kepala_divisi' => 1,
    'manager' => 2,
    'direktur' => 3,
    'kepala_perusahaan' => 4
];

if ($isAdmin) {
    // Admin sees all reports
    $result = $conn->query("SELECT r.*, u.full_name as uploader, u.username, u.position as user_position 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC");
} else {
    // Get reports from users with lower or equal position level
    $myLevel = $positionHierarchy[$position] ?? 0;
    $levels = array_keys(array_filter($positionHierarchy, function($level) use ($myLevel) {
        return $level <= $myLevel;
    }));
    
    $levelsStr = "'" . implode("','", $levels) . "'";
    
    $result = $conn->query("SELECT r.*, u.full_name as uploader, u.username, u.position as user_position 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        WHERE u.position IN ($levelsStr)
        ORDER BY r.created_at DESC");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Get statistics
$stats = ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0, 'rejected' => 0];
if ($isAdmin) {
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM reports");
    $stats = $result->fetch_assoc();
} else {
    $levelsStr = "'" . implode("','", $levels) . "'";
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM reports r
        JOIN users u ON r.user_id = u.id
        WHERE u.position IN ($levelsStr)");
    $stats = $result->fetch_assoc();
}

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
    $classes = [
        'pending' => 'badge-warning',
        'reviewed' => 'badge-info',
        'resolved' => 'badge-success',
        'rejected' => 'badge-danger'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

function getPositionName($position) {
    return ucfirst(str_replace('_', ' ', $position ?? 'staff'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Reports - Lapor System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-warning { background-color: #fef3c7; color: #d97706; }
        .badge-info { background-color: #dbeafe; color: #2563eb; }
        .badge-success { background-color: #dcfce7; color: #16a34a; }
        .badge-danger { background-color: #fef2f2; color: #dc2626; }
        .badge-secondary { background-color: #f1f5f9; color: #64748b; }
        
        .review-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .review-card.pending { border-left-color: #f59e0b; }
        .review-card.reviewed { border-left-color: #3b82f6; }
        .review-card.resolved { border-left-color: #22c55e; }
        .review-card.rejected { border-left-color: #ef4444; }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .uploader-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .review-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .notes-box {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #f59e0b;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #64748b;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
        }
        .position-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .position-staff { background: #f1f5f9; color: #64748b; }
        .position-kepala_divisi { background: #dbeafe; color: #2563eb; }
        .position-manager { background: #fef3c7; color: #d97706; }
        .position-direktur { background: #fee2e2; color: #dc2626; }
        .position-kepala_perusahaan { background: #dcfce7; color: #16a34a; }
    </style>
</head>
<body class="dashboard-page">
    <header class="admin-header">
        <div class="header-left">
            <h2 class="logo">Lapor Review</h2>
        </div>
        <div class="header-right">
            <span class="user-info">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <span class="position-badge position-<?php echo $position; ?>">
                    <?php echo getPositionName($position); ?>
                </span>
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <?php if ($isAdmin): ?>
                    <a href="dashboard.php" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                <?php endif; ?>
                <a href="review.php" class="nav-item active">
                    <span class="nav-icon">✅</span>
                    <span>Review Reports</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📁</span>
                    <span>All Reports</span>
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
                <h1>Review Reports</h1>
                <p>Review and approve reports from your team</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
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
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>Reports for Review</h2>
                
                <?php if (empty($reports)): ?>
                    <div class="info-card">
                        <p>🎉 No reports to review.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="review-card <?php echo $report['status']; ?>">
                            <div class="review-header">
                                <div>
                                    <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($report['title']); ?></h3>
                                    <div class="uploader-info">
                                        <span style="font-size: 24px;">👤</span>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($report['uploader']); ?></div>
                                            <div style="font-size: 13px; color: #64748b;">
                                                @<?php echo htmlspecialchars($report['username']); ?> | 
                                                <span class="position-badge position-<?php echo $report['user_position']; ?>">
                                                    <?php echo getPositionName($report['user_position']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap; font-size: 13px;">
                                        <span>📂 <?php echo getCategoryLabel($report['category'] ?? 'staff'); ?></span>
                                        <span>🔥 <span class="badge badge-<?php echo $report['priority'] ?? 'medium'; ?>"><?php echo ucfirst($report['priority'] ?? 'medium'); ?></span></span>
                                        <span>📅 <?php echo date('d M Y, H:i', strtotime($report['created_at'])); ?></span>
                                    </div>
                                </div>
                                <span class="badge <?php echo getStatusClass($report['status']); ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </div>
                            
                            <p style="color: #64748b; margin-bottom: 15px;">
                                <?php echo htmlspecialchars($report['description']); ?>
                            </p>
                            
                            <div style="padding: 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 20px;">📄</span>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($report['file_name']); ?></div>
                                        <div style="font-size: 12px; color: #64748b;"><?php echo formatFileSize($report['file_size']); ?></div>
                                    </div>
                                    <a href="../uploads/<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-sm btn-success" download target="_blank">👁 View</a>
                                </div>
                            </div>
                            
                            <?php if (!empty($report['notes'])): ?>
                                <div class="notes-box">
                                    <strong>📝 Notes:</strong>
                                    <p style="margin: 5px 0 0 0;"><?php echo htmlspecialchars($report['notes']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($report['status'] === 'pending'): ?>
                                <div class="review-actions">
                                    <button class="btn btn-success" onclick="openApproveModal(<?php echo $report['id']; ?>)">✅ Approve</button>
                                    <button class="btn btn-warning" onclick="openReviseModal(<?php echo $report['id']; ?>)">📝 Request Revision</button>
                                </div>
                            <?php else: ?>
                                <div style="padding: 10px; background: #f0fdf4; border-radius: 6px; color: #16a34a;">
                                    ✅ <?php echo ucfirst($report['status']); ?> - <?php echo date('d M Y', strtotime($report['updated_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeApproveModal()">&times;</span>
            <h2>✅ Approve Report</h2>
            <p>Please provide approval notes:</p>
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="report_id" id="approveReportId">
                <div class="form-group">
                    <label>Approval Notes *</label>
                    <textarea name="notes" required placeholder="Enter your approval notes/comments..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeApproveModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">✅ Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Revise Modal -->
    <div id="reviseModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeReviseModal()">&times;</span>
            <h2>📝 Request Revision</h2>
            <p>Please provide notes for revision:</p>
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="revise">
                <input type="hidden" name="report_id" id="reviseReportId">
                <div class="form-group">
                    <label>Revision Notes *</label>
                    <textarea name="notes" required placeholder="Explain what needs to be revised..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeReviseModal()">Cancel</button>
                    <button type="submit" class="btn btn-warning">📝 Send for Revision</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApproveModal(reportId) {
            document.getElementById('approveReportId').value = reportId;
            document.getElementById('approveModal').style.display = 'block';
        }
        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }
        function openReviseModal(reportId) {
            document.getElementById('reviseReportId').value = reportId;
            document.getElementById('reviseModal').style.display = 'block';
        }
        function closeReviseModal() {
            document.getElementById('reviseModal').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
