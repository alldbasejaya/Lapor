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

// Handle review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reportId = (int)$_POST['report_id'];
    $action = $_POST['action'];
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Verify this report is from lower level
    $stmt = $conn->prepare("SELECT r.*, u.position as user_position FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$report) {
        $message = 'Report not found!';
        $messageType = 'error';
    } else {
        $userLevel = $positionHierarchy[$report['user_position']] ?? 0;
        
        // Verify user has authority to review this report
        if ($userLevel >= $myLevel && !$isAdmin) {
            $message = 'You do not have authority to review this report!';
            $messageType = 'error';
        } else {
            if ($action === 'approve') {
                // Determine next status based on current status and my level
                $newStatus = $report['status'];
                
                // Kepala Divisi (level 1) approves pending → reviewed
                if ($myLevel === 1 && $report['status'] === 'pending') {
                    $newStatus = 'reviewed';
                    $updateField = 'reviewed_by';
                }
                // Manager (level 2) approves reviewed → reviewed (still in review chain)
                elseif ($myLevel === 2 && $report['status'] === 'reviewed') {
                    $newStatus = 'reviewed';
                    $updateField = 'reviewed_by';
                }
                // Direktur (level 3) approves reviewed → reviewed (still in review chain)
                elseif ($myLevel === 3 && $report['status'] === 'reviewed') {
                    $newStatus = 'reviewed';
                    $updateField = 'reviewed_by';
                }
                // Kepala Perusahaan (level 4) final approval → resolved
                elseif ($myLevel === 4 && $report['status'] === 'reviewed') {
                    $newStatus = 'resolved';
                    $updateField = 'resolved_by';
                }
                else {
                    $newStatus = 'reviewed';
                    $updateField = 'reviewed_by';
                }
                
                $stmt = $conn->prepare("UPDATE reports SET status = ?, notes = ?, $updateField = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("ssii", $newStatus, $notes, $_SESSION['user_id'], $reportId);
                
                if ($stmt->execute()) {
                    $message = "Report approved successfully! Status: " . ucfirst($newStatus);
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
    }
}

// Redirect back to dashboard
redirect(BASE_URL . 'user/dashboard.php' . ($message ? '?success=1' : ''));
?>
