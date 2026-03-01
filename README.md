# Lapor System - Multi-User Login Application

A web-based multi-user login system with administrator functionality built with PHP and MySQL.

## Features

- 🔐 **Multi-User Authentication** - Secure login system with password hashing
- 👑 **Role-Based Access** - Admin and User roles with different permissions
- 📊 **Admin Dashboard** - Statistics and overview of the system
- 👥 **User Management** - Admin can add, edit, activate/deactivate, and delete users
- 🎨 **Modern UI** - Clean and responsive design
- 🔒 **Security Features** - SQL injection prevention, XSS protection, session management

## Requirements

- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser (Chrome, Firefox, Edge, etc.)

## Installation

### 1. Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** and **MySQL** services

### 2. Create Database
- Open phpMyAdmin (http://localhost/phpmyadmin)
- Click "Import" tab
- Select the `database.sql` file from this directory
- Click "Go" to execute

### 3. Access the Application
- Open your browser
- Navigate to: `http://localhost/Lapor/`

## Default Credentials

### Administrator Account
- **Username:** `admin`
- **Password:** `admin123`

### Regular User Account
- **Username:** `user`
- **Password:** `user123`

## File Structure

```
Lapor/
├── admin/
│   ├── dashboard.php       # Admin dashboard page
│   ├── users.php           # User management page
│   ├── profile.php         # Admin profile settings
│   └── includes/
│       ├── header.php      # Admin header component
│       └── sidebar.php     # Admin sidebar navigation
├── auth/
│   ├── login.php          # Login authentication logic
│   └── logout.php         # Logout handler
├── user/
│   └── dashboard.php      # User dashboard page
├── assets/
│   └── css/
│       └── style.css      # Application styles
├── config/
│   └── config.php         # Database configuration
├── index.php              # Login page
├── register.php           # User registration page
└── database.sql           # Database schema and initial data
```

## Usage

### For Administrators
1. Login with admin credentials
2. Access the admin dashboard to view statistics
3. Navigate to "User Management" to:
   - Add new users
   - Activate/Deactivate user accounts
   - Delete users
   - View all registered users
4. Update profile and change password in "Profile Settings"

### For Regular Users
1. Login with user credentials (or register a new account)
2. Access the user dashboard
3. View account information
4. Update profile settings

## Security Notes

- Passwords are hashed using PHP's `password_hash()` function (bcrypt)
- Prepared statements are used to prevent SQL injection
- Sessions are used for authentication state management
- Input sanitization is applied to prevent XSS attacks

## Customization

### Database Configuration
Edit `config/config.php` to change database settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lapor_db');
```

### Styling
Modify `assets/css/style.css` to change the appearance.

## Troubleshooting

### Cannot Login
- Ensure database is imported correctly
- Check if Apache and MySQL are running in XAMPP
- Verify database credentials in `config/config.php`

### Database Connection Error
- Make sure MySQL service is running
- Check if `lapor_db` database exists
- Verify database user permissions

## License

This project is open-source and available for educational purposes.

## Support

For issues or questions, please contact the administrator.
