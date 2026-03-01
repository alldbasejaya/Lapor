<?php
define('APP_INIT', true);
require_once '../config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'index.php');
}

// Get and sanitize input
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

// Validate input
if (empty($username) || empty($password)) {
    redirect(BASE_URL . 'index.php?error=invalid');
}

// Connect to database
$conn = getDbConnection();

// Prepare statement to prevent SQL injection
$stmt = $conn->prepare("SELECT id, username, password, email, full_name, role, status, position FROM users WHERE username = ?");

if (!$stmt) {
    $_SESSION['login_error'] = "Prepare failed: " . $conn->error;
    redirect(BASE_URL . 'index.php?error=invalid');
}

$stmt->bind_param("s", $username);

if (!$stmt->execute()) {
    $_SESSION['login_error'] = "Execute failed: " . $stmt->error;
    redirect(BASE_URL . 'index.php?error=invalid');
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['login_error'] = "User not found: " . $username;
    $stmt->close();
    $conn->close();
    redirect(BASE_URL . 'index.php?error=invalid');
}

// Fetch user data
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Verify password
if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = "Password mismatch";
    redirect(BASE_URL . 'index.php?error=invalid');
}

// Check if account is active
if ($user['status'] !== 'active') {
    redirect(BASE_URL . 'index.php?error=inactive');
}

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['position'] = $user['position'];
$_SESSION['status'] = $user['status'];

// Redirect based on role
if ($user['role'] === 'admin') {
    redirect(BASE_URL . 'admin/dashboard.php');
} else {
    redirect(BASE_URL . 'user/dashboard.php');
}
?>
