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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'delete' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        if ($user_id !== $_SESSION['user_id']) { // Prevent deleting own account
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = 'User deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete user.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
    
    if ($action === 'toggle_status' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        if ($user_id !== $_SESSION['user_id']) {
            $stmt = $conn->prepare("UPDATE users SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = 'User status updated!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update status.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
    
    if ($action === 'add_user') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $position = $_POST['position'];

        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, position) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $password, $email, $full_name, $role, $position);
        if ($stmt->execute()) {
            $message = 'User added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add user. Username or email may already exist.';
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Lapor System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-main">
            <div class="page-header">
                <h1>User Management</h1>
                <p>Manage all users in the system</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="table-actions">
                <button class="btn btn-primary" onclick="document.getElementById('addUserModal').style.display='block'">
                    + Add New User
                </button>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><span class="badge badge-secondary"><?php echo ucfirst(str_replace('_', ' ', $user['position'])); ?></span></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            <td class="actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning" 
                                            <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="document.getElementById('addUserModal').style.display='none'">&times;</span>
            <h2>Add New User</h2>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <select name="position">
                        <option value="staff">Staff</option>
                        <option value="kepala_divisi">Kepala Divisi</option>
                        <option value="manager">Manager</option>
                        <option value="direktur">Direktur</option>
                        <option value="kepala_perusahaan">Kepala Perusahaan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addUserModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
