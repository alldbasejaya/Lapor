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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $user_id = $_SESSION['user_id'];

    // Validate file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            $message = 'Invalid file type. Only PDF, Images (JPG, PNG), Word, and Excel files are allowed.';
            $messageType = 'error';
        } elseif ($file['size'] > $maxSize) {
            $message = 'File size exceeds the maximum limit of 10MB.';
            $messageType = 'error';
        } else {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'LAPOR_' . $user_id . '_' . uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $stmt = $conn->prepare("INSERT INTO reports (user_id, title, description, file_path, file_name, file_type, file_size, category, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssss", $user_id, $title, $description, $filename, $file['name'], $file['type'], $file['size'], $category, $priority);

                if ($stmt->execute()) {
                    $message = __('user.report_created');
                    $messageType = 'success';
                } else {
                    $message = __('user.report_failed');
                    $messageType = 'error';
                    unlink($filePath);
                }
                $stmt->close();
            } else {
                $message = 'Failed to upload file.';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Please select a file to upload.';
        $messageType = 'error';
    }
}

$userId = $_SESSION['user_id'];
$stats = ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0, 'rejected' => 0];
$result = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reports WHERE user_id = $userId");
$stats = $result->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('user.upload_report'); ?> - <?php echo __('app_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .upload-form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b; }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus { outline: none; border-color: #4f46e5; }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .file-upload-box {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s;
        }
        .file-upload-box:hover { border-color: #4f46e5; background: #f1f5f9; }
        .file-upload-box input[type="file"] { display: none; }
        .file-upload-label {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .file-upload-icon { font-size: 48px; }
        .file-upload-text { font-weight: 600; color: #4f46e5; }
        .file-upload-hint { font-size: 12px; color: #64748b; }
        .selected-file {
            margin-top: 15px;
            padding: 10px;
            background: #dcfce7;
            border-radius: 8px;
            color: #16a34a;
            display: none;
        }
        .form-actions { display: flex; gap: 15px; margin-top: 30px; }
        .btn-upload {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            flex: 1;
        }
        .btn-upload:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(79, 70, 229, 0.4); }
        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
            padding: 14px 28px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }
        .btn-cancel:hover { background: #e2e8f0; }
        .required { color: #dc2626; }
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
                <span class="badge badge-user"><?php echo ucfirst($_SESSION['role']); ?></span>
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <?php include __DIR__ . '/../includes/language_switcher.php'; ?>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span><?php echo __('common.dashboard'); ?></span>
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📁</span>
                    <span><?php echo __('user.my_reports'); ?></span>
                </a>
                <a href="upload.php" class="nav-item active">
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
                <h1><?php echo __('user.upload_report'); ?></h1>
                <p><?php echo __('user.new_report'); ?></p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="upload-form-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">

                    <div class="form-group">
                        <label><?php echo __('user.report_title'); ?> <span class="required">*</span></label>
                        <input type="text" name="title" required placeholder="<?php echo __('user.enter_title'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Jabatan</label>
                        <select name="category" required>
                            <option value="staff"><?php echo __('positions.staff'); ?></option>
                            <option value="kepala_divisi"><?php echo __('positions.kepala_divisi'); ?></option>
                            <option value="manager"><?php echo __('positions.manager'); ?></option>
                            <option value="direktur"><?php echo __('positions.direktur'); ?></option>
                            <option value="kepala_perusahaan"><?php echo __('positions.kepala_perusahaan'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('user.priority'); ?></label>
                        <select name="priority" required>
                            <option value="low"><?php echo __('priority_labels.low'); ?></option>
                            <option value="medium" selected><?php echo __('priority_labels.medium'); ?></option>
                            <option value="high"><?php echo __('priority_labels.high'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('user.description'); ?> <span class="required">*</span></label>
                        <textarea name="description" required placeholder="<?php echo __('user.enter_description'); ?>"></textarea>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('user.file'); ?> <span class="required">*</span></label>
                        <div class="file-upload-box">
                            <label class="file-upload-label">
                                <span class="file-upload-icon">📎</span>
                                <span class="file-upload-text"><?php echo __('user.select_file'); ?></span>
                                <span class="file-upload-hint">PDF, Images (JPG/PNG), Word, Excel (Max 10MB)</span>
                                <input type="file" name="file" id="fileInput" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                            </label>
                            <div class="selected-file" id="selectedFile"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="reports.php" class="btn-cancel"><?php echo __('common.cancel'); ?></a>
                        <button type="submit" class="btn-upload">📤 <?php echo __('user.upload_report'); ?></button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const selectedFileDiv = document.getElementById('selectedFile');
            if (file) {
                const size = (file.size / 1024 / 1024).toFixed(2);
                selectedFileDiv.textContent = '📄 ' + file.name + ' (' + size + ' MB)';
                selectedFileDiv.style.display = 'block';
            } else {
                selectedFileDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>
