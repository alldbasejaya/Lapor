-- Create database
CREATE DATABASE IF NOT EXISTS lapor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lapor_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    position ENUM('staff', 'kepala_divisi', 'manager', 'direktur', 'kepala_perusahaan') DEFAULT 'staff',
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role, status)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@lapor.com', 'Administrator', 'admin', 'active');

-- Insert default user (password: user123)
INSERT INTO users (username, password, email, full_name, role, status)
VALUES ('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@lapor.com', 'Regular User', 'user', 'active');

-- Insert sample users with positions
-- staff (password: staff123)
INSERT INTO users (username, password, email, full_name, position, role, status)
VALUES ('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff@lapor.com', 'Staff Employee', 'staff', 'user', 'active');

-- kepala_divisi (password: kadiv123)
INSERT INTO users (username, password, email, full_name, position, role, status)
VALUES ('kadiv', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kadiv@lapor.com', 'Kepala Divisi', 'kepala_divisi', 'user', 'active');

-- manager (password: manager123)
INSERT INTO users (username, password, email, full_name, position, role, status)
VALUES ('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@lapor.com', 'Manager', 'manager', 'user', 'active');

-- direktur (password: direktur123)
INSERT INTO users (username, password, email, full_name, position, role, status)
VALUES ('direktur', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'direktur@lapor.com', 'Direktur', 'direktur', 'user', 'active');

-- kepala_perusahaan (password: kp123)
INSERT INTO users (username, password, email, full_name, position, role, status)
VALUES ('kp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kp@lapor.com', 'Kepala Perusahaan', 'kepala_perusahaan', 'user', 'active');

-- Create reports table for file uploads
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100),
    file_size INT,
    status ENUM('pending', 'reviewed', 'resolved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
