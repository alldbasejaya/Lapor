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

function getCategoryLabel($category) {
    $labels = [
        'staff' => __('positions.staff'),
        'kepala_divisi' => __('positions.kepala_divisi'),
        'manager' => __('positions.manager'),
        'direktur' => __('positions.direktur'),
        'kepala_perusahaan' => __('positions.kepala_perusahaan')
    ];
    return $labels[$category] ?? ucfirst($category);
}

// Get user position
$position = $_SESSION['position'] ?? 'staff';
$userId = $_SESSION['user_id'];

// Position hierarchy levels
$positionHierarchy = [
    'staff' => 0,
    'kepala_divisi' => 1,
    'manager' => 2,
    'direktur' => 3,
    'kepala_perusahaan' => 4
];

$myLevel = $positionHierarchy[$position] ?? 0;
$canReview = $myLevel >= 1; // Kepala Divisi and above can review

// Get user's reports
$myReports = [];
$result = $conn->query("SELECT * FROM reports WHERE user_id = $userId ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $myReports[] = $row;
    }
}

// Get reports for review based on position level
$reportsForReview = [];
$reviewStats = ['pending' => 0, 'reviewed' => 0, 'total' => 0];

if ($canReview) {
    // Get reports from lower levels that need my approval
    $lowerLevels = array_keys(array_filter($positionHierarchy, function($level) use ($myLevel) {
        return $level < $myLevel;
    }));

    if (!empty($lowerLevels)) {
        $lowerLevelsStr = "'" . implode("','", $lowerLevels) . "'";

        $result = $conn->query("SELECT r.*, u.full_name as uploader, u.username, u.position as user_position,
            rev.full_name as reviewed_by_name,
            res.full_name as resolved_by_name
            FROM reports r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN users rev ON r.reviewed_by = rev.id
            LEFT JOIN users res ON r.resolved_by = res.id
            WHERE u.position IN ($lowerLevelsStr)
            ORDER BY
                CASE r.status
                    WHEN 'pending' THEN 1
                    WHEN 'reviewed' THEN 2
                    WHEN 'resolved' THEN 3
                    WHEN 'rejected' THEN 4
                END,
                r.created_at ASC");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $reportsForReview[] = $row;
            }
        }

        $result = $conn->query("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN r.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed
            FROM reports r
            JOIN users u ON r.user_id = u.id
            WHERE u.position IN ($lowerLevelsStr)");
        $reviewStats = $result->fetch_assoc();
    }
}

// Get my reports statistics
$myStats = ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0, 'rejected' => 0];
$result = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reports WHERE user_id = $userId");
$myStats = $result->fetch_assoc();

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
    return __($position);
}

function getPositionBadgeClass($position) {
    $classes = [
        'staff' => 'position-staff',
        'kepala_divisi' => 'position-kepala_divisi',
        'manager' => 'position-manager',
        'direktur' => 'position-direktur',
        'kepala_perusahaan' => 'position-kepala_perusahaan'
    ];
    return $classes[$position] ?? 'position-staff';
}

function getApprovalFlow($reportStatus) {
    $flow = [];

    if ($reportStatus === 'pending') {
        $flow = [
            ['level' => 1, 'name' => __('positions.kepala_divisi'), 'status' => 'pending', 'icon' => '⏳'],
            ['level' => 2, 'name' => __('positions.manager'), 'status' => 'waiting', 'icon' => '⏸'],
            ['level' => 3, 'name' => __('positions.direktur'), 'status' => 'waiting', 'icon' => '⏸'],
            ['level' => 4, 'name' => __('positions.kepala_perusahaan'), 'status' => 'waiting', 'icon' => '⏸']
        ];
    } elseif ($reportStatus === 'reviewed') {
        $flow = [
            ['level' => 1, 'name' => __('positions.kepala_divisi'), 'status' => 'approved', 'icon' => '✅'],
            ['level' => 2, 'name' => __('positions.manager'), 'status' => 'pending', 'icon' => '⏳'],
            ['level' => 3, 'name' => __('positions.direktur'), 'status' => 'waiting', 'icon' => '⏸'],
            ['level' => 4, 'name' => __('positions.kepala_perusahaan'), 'status' => 'waiting', 'icon' => '⏸']
        ];
    } elseif ($reportStatus === 'resolved') {
        $flow = [
            ['level' => 1, 'name' => __('positions.kepala_divisi'), 'status' => 'approved', 'icon' => '✅'],
            ['level' => 2, 'name' => __('positions.manager'), 'status' => 'approved', 'icon' => '✅'],
            ['level' => 3, 'name' => __('positions.direktur'), 'status' => 'approved', 'icon' => '✅'],
            ['level' => 4, 'name' => __('positions.kepala_perusahaan'), 'status' => 'approved', 'icon' => '✅']
        ];
    } elseif ($reportStatus === 'rejected') {
        $flow = [
            ['level' => 1, 'name' => __('positions.kepala_divisi'), 'status' => 'rejected', 'icon' => '❌'],
            ['level' => 2, 'name' => __('positions.manager'), 'status' => 'waiting', 'icon' => '⏸'],
            ['level' => 3, 'name' => __('positions.direktur'), 'status' => 'waiting', 'icon' => '⏸'],
            ['level' => 4, 'name' => __('positions.kepala_perusahaan'), 'status' => 'waiting', 'icon' => '⏸']
        ];
    }

    return $flow;
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('user.dashboard'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge-warning { background-color: #fef3c7; color: #d97706; }
        .badge-info { background-color: #dbeafe; color: #2563eb; }
        .badge-success { background-color: #dcfce7; color: #16a34a; }
        .badge-danger { background-color: #fef2f2; color: #dc2626; }
        .badge-secondary { background-color: #f1f5f9; color: #64748b; }
        .badge-low { background-color: #dcfce7; color: #16a34a; }
        .badge-medium { background-color: #fef3c7; color: #d97706; }
        .badge-high { background-color: #fef2f2; color: #dc2626; }

        .position-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .position-staff { background: #f1f5f9; color: #64748b; }
        .position-kepala_divisi { background: #dbeafe; color: #2563eb; }
        .position-manager { background: #fef3c7; color: #d97706; }
        .position-direktur { background: #fee2e2; color: #dc2626; }
        .position-kepala_perusahaan { background: #dcfce7; color: #16a34a; }

        .approval-flow {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        .approval-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 10px 15px;
            background: #f8fafc;
            border-radius: 8px;
            min-width: 120px;
        }
        .approval-step.approved { background: #dcfce7; }
        .approval-step.pending { background: #fef3c7; }
        .approval-step.rejected { background: #fef2f2; }
        .approval-step.waiting { background: #f1f5f9; opacity: 0.6; }
        .approval-icon { font-size: 24px; }
        .approval-name { font-size: 12px; font-weight: 600; text-align: center; }
        .approval-arrow { color: #94a3b8; font-size: 20px; }

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
        .notes-box.approved { background: #dcfce7; border-left-color: #22c55e; }
        .notes-box.rejected { background: #fef2f2; border-left-color: #ef4444; }

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
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }
        .form-group textarea:focus { outline: none; border-color: #4f46e5; }

        .welcome-card {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .welcome-card h2 { margin-bottom: 10px; }
        .welcome-card p { opacity: 0.9; }

        .progress-info {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .progress-info h4 { margin-bottom: 10px; color: #1e293b; }
    </style>
</head>
<body class="dashboard-page">
    <header class="admin-header">
        <div class="header-left">
            <h2 class="logo"><?php echo __('app_name'); ?></h2>
        </div>
        <div class="header-right">
            <span class="user-info">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <span class="position-badge <?php echo getPositionBadgeClass($position); ?>">
                    <?php echo getPositionName($position); ?>
                </span>
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <?php include __DIR__ . '/../includes/language_switcher.php'; ?>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span><?php echo __('common.dashboard'); ?></span>
                </a>
                <?php if ($canReview): ?>
                    <a href="review.php" class="nav-item">
                        <span class="nav-icon">✅</span>
                        <span><?php echo __('user.pending_approval'); ?></span>
                        <?php if ($reviewStats['pending'] > 0): ?>
                            <span class="badge badge-danger" style="margin-left: 5px;"><?php echo $reviewStats['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📁</span>
                    <span><?php echo __('user.my_reports'); ?></span>
                </a>
                <a href="upload.php" class="nav-item">
                    <span class="nav-icon">📤</span>
                    <span><?php echo __('user.upload_report'); ?></span>
                </a>
                <a href="profile.php" class="nav-item">
                    <span class="nav-icon">⚙</span>
                    <span><?php echo __('common.profile'); ?></span>
                </a>
                <a href="../index.php" class="nav-item">
                    <span class="nav-icon">🏠</span>
                    <span><?php echo __('common.home'); ?></span>
                </a>
                <a href="../auth/logout.php" class="nav-item nav-logout">
                    <span class="nav-icon">🚪</span>
                    <span><?php echo __('common.logout'); ?></span>
                </a>
            </nav>
        </aside>

        <main class="dashboard-main">
            <div class="page-header">
                <h1><?php echo __('common.dashboard'); ?></h1>
                <p><?php echo str_replace('{name}', htmlspecialchars($_SESSION['full_name']), __('user.welcome')); ?></p>
            </div>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>👋 <?php echo __('common.welcome_back'); ?>, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p><?php echo __('user.position'); ?>: <strong><?php echo getPositionName($position); ?></strong></p>
                <?php if ($canReview): ?>
                    <p style="margin-top: 10px;">
                        📋 <?php echo $reviewStats['pending']; ?> <?php echo __('user.reports_pending'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($canReview): ?>
                <!-- Review Statistics -->
                <div class="stats-grid">
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3><?php echo $reviewStats['pending']; ?></h3>
                            <p><?php echo __('user.pending_approval'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3><?php echo $reviewStats['reviewed']; ?></h3>
                            <p><?php echo __('status_labels.reviewed'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3><?php echo $reviewStats['total']; ?></h3>
                            <p><?php echo __('common.total_reports'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-icon">📁</div>
                        <div class="stat-content">
                            <h3><?php echo $myStats['total']; ?></h3>
                            <p><?php echo __('user.my_reports'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Reports for Review -->
                <div class="dashboard-section">
                    <h2><?php echo __('user.approval_queue'); ?></h2>
                    <p style="color: #64748b; margin-bottom: 20px;">
                        <?php echo __('user.reports_pending'); ?>
                    </p>

                    <?php if (empty($reportsForReview)): ?>
                        <div class="info-card">
                            <p>🎉 <?php echo __('user.no_reports_to_review'); ?></p>
                            <p><?php echo __('user.all_processed'); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reportsForReview as $report): ?>
                            <div class="review-card <?php echo $report['status']; ?>">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div style="flex: 1;">
                                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($report['title']); ?></h3>
                                        <div style="display: flex; gap: 10px; flex-wrap: wrap; font-size: 13px; color: #64748b; margin-bottom: 10px;">
                                            <span>👤 <?php echo htmlspecialchars($report['uploader']); ?>
                                                <span class="position-badge position-<?php echo $report['user_position']; ?>" style="font-size: 11px; padding: 2px 8px;">
                                                    <?php echo getPositionName($report['user_position']); ?>
                                                </span>
                                            </span>
                                            <span>|</span>
                                            <span>📂 <?php echo getCategoryLabel($report['category'] ?? 'staff'); ?></span>
                                            <span>🔥 <span class="badge badge-<?php echo $report['priority'] ?? 'medium'; ?>"><?php echo ucfirst($report['priority'] ?? 'medium'); ?></span></span>
                                            <span>📅 <?php echo date('d M Y', strtotime($report['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo getStatusClass($report['status']); ?>" style="font-size: 14px; padding: 8px 16px;">
                                        <?php echo __($report['status']); ?>
                                    </span>
                                </div>

                                <p style="color: #64748b; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars(substr($report['description'], 0, 200)) . (strlen($report['description']) > 200 ? '...' : ''); ?>
                                </p>

                                <!-- Approval Flow -->
                                <div class="progress-info">
                                    <h4><?php echo __('user.approval_progress'); ?></h4>
                                    <div class="approval-flow">
                                        <?php
                                        $flow = getApprovalFlow($report['status']);
                                        foreach ($flow as $index => $step):
                                        ?>
                                            <div class="approval-step <?php echo $step['status']; ?>">
                                                <span class="approval-icon"><?php echo $step['icon']; ?></span>
                                                <span class="approval-name"><?php echo $step['name']; ?></span>
                                            </div>
                                            <?php if ($index < count($flow) - 1): ?>
                                                <span class="approval-arrow">→</span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- File Info -->
                                <div style="padding: 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 15px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 20px;">📄</span>
                                        <div style="flex: 1;">
                                            <div style="font-weight: 500;"><?php echo htmlspecialchars($report['file_name']); ?></div>
                                            <div style="font-size: 12px; color: #64748b;"><?php echo formatFileSize($report['file_size']); ?></div>
                                        </div>
                                        <a href="../uploads/<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-sm btn-success" download target="_blank">👁 <?php echo __('common.view'); ?></a>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <?php if (!empty($report['notes'])): ?>
                                    <div class="notes-box <?php echo $report['status'] === 'rejected' ? 'rejected' : 'approved'; ?>">
                                        <strong>📝 <?php echo __('user.notes'); ?>:</strong>
                                        <p style="margin: 5px 0 0 0;"><?php echo htmlspecialchars($report['notes']); ?></p>
                                        <?php if ($report['reviewed_by_name']): ?>
                                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #64748b;">
                                                - <?php echo htmlspecialchars($report['reviewed_by_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <?php if ($report['status'] === 'pending' && $myLevel === 1): ?>
                                    <div class="review-actions">
                                        <button class="btn btn-sm btn-success" onclick="openApproveModal(<?php echo $report['id']; ?>)">✅ <?php echo __('user.approve'); ?></button>
                                        <button class="btn btn-sm btn-warning" onclick="openReviseModal(<?php echo $report['id']; ?>)">📝 <?php echo __('user.request_revision'); ?></button>
                                    </div>
                                <?php elseif ($report['status'] === 'reviewed' && $myLevel === 2): ?>
                                    <div class="review-actions">
                                        <button class="btn btn-sm btn-success" onclick="openApproveModal(<?php echo $report['id']; ?>)">✅ <?php echo __('positions.manager'); ?></button>
                                        <button class="btn btn-sm btn-warning" onclick="openReviseModal(<?php echo $report['id']; ?>)">📝 <?php echo __('user.request_revision'); ?></button>
                                    </div>
                                <?php elseif ($report['status'] === 'reviewed' && $myLevel === 3): ?>
                                    <div class="review-actions">
                                        <button class="btn btn-sm btn-success" onclick="openApproveModal(<?php echo $report['id']; ?>)">✅ <?php echo __('positions.direktur'); ?></button>
                                        <button class="btn btn-sm btn-warning" onclick="openReviseModal(<?php echo $report['id']; ?>)">📝 <?php echo __('user.request_revision'); ?></button>
                                    </div>
                                <?php elseif ($report['status'] === 'reviewed' && $myLevel === 4): ?>
                                    <div class="review-actions">
                                        <button class="btn btn-sm btn-success" onclick="openApproveModal(<?php echo $report['id']; ?>)">✅ <?php echo __('user.final_approval'); ?></button>
                                        <button class="btn btn-sm btn-warning" onclick="openReviseModal(<?php echo $report['id']; ?>)">📝 <?php echo __('user.request_revision'); ?></button>
                                    </div>
                                <?php elseif ($report['status'] === 'resolved'): ?>
                                    <div style="padding: 10px; background: #dcfce7; border-radius: 6px; color: #16a34a;">
                                        ✅ <?php echo __('user.approved_by'); ?> <?php echo __('positions.kepala_perusahaan'); ?> <?php echo date('d M Y', strtotime($report['updated_at'])); ?>
                                    </div>
                                <?php elseif ($report['status'] === 'rejected'): ?>
                                    <div style="padding: 10px; background: #fef2f2; border-radius: 6px; color: #dc2626;">
                                        🔄 <?php echo __('user.request_revision'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Staff Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">📁</div>
                        <div class="stat-content">
                            <h3><?php echo $myStats['total']; ?></h3>
                            <p><?php echo __('common.total_reports'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3><?php echo $myStats['pending']; ?></h3>
                            <p><?php echo __('status_labels.pending'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-icon">👁</div>
                        <div class="stat-content">
                            <h3><?php echo $myStats['reviewed']; ?></h3>
                            <p><?php echo __('status_labels.reviewed'); ?></p>
                        </div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3><?php echo $myStats['resolved']; ?></h3>
                            <p><?php echo __('status_labels.resolved'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- My Recent Reports -->
            <div class="dashboard-section">
                <h2>📊 <?php echo __('user.my_reports'); ?></h2>

                <?php if (empty($myReports)): ?>
                    <div class="info-card">
                        <p>📭 <?php echo __('common.no_data'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><?php echo __('user.report_title'); ?></th>
                                    <th><?php echo __('user.category'); ?></th>
                                    <th><?php echo __('user.priority'); ?></th>
                                    <th><?php echo __('common.status'); ?></th>
                                    <th><?php echo __('user.date'); ?></th>
                                    <th><?php echo __('common.actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myReports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['title']); ?></td>
                                        <td><?php echo getCategoryLabel($report['category'] ?? 'staff'); ?></td>
                                        <td><span class="badge badge-<?php echo $report['priority'] ?? 'medium'; ?>"><?php echo __($report['priority'] ?? 'medium'); ?></span></td>
                                        <td><span class="badge <?php echo getStatusClass($report['status']); ?>"><?php echo __($report['status']); ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <a href="../uploads/<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-sm btn-success" download>⬇ <?php echo __('user.download'); ?></a>
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

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeApproveModal()">&times;</span>
            <h2>✅ <?php echo __('user.approve'); ?></h2>
            <p><?php echo __('user.notes'); ?></p>
            <form method="POST" action="review.php" style="margin-top: 20px;">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="report_id" id="approveReportId">
                <div class="form-group">
                    <label><?php echo __('user.notes'); ?> *</label>
                    <textarea name="notes" required placeholder="<?php echo __('user.enter_description'); ?>"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeApproveModal()"><?php echo __('common.cancel'); ?></button>
                    <button type="submit" class="btn btn-success">✅ <?php echo __('user.approve'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Revise Modal -->
    <div id="reviseModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeReviseModal()">&times;</span>
            <h2>📝 <?php echo __('user.request_revision'); ?></h2>
            <p><?php echo __('user.notes'); ?></p>
            <form method="POST" action="review.php" style="margin-top: 20px;">
                <input type="hidden" name="action" value="revise">
                <input type="hidden" name="report_id" id="reviseReportId">
                <div class="form-group">
                    <label><?php echo __('user.notes'); ?> *</label>
                    <textarea name="notes" required placeholder="<?php echo __('user.enter_description'); ?>"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeReviseModal()"><?php echo __('common.cancel'); ?></button>
                    <button type="submit" class="btn btn-warning">📝 <?php echo __('user.request_revision'); ?></button>
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
