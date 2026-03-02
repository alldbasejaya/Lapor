# Lapor System - Multi-User Login Application

A web-based multi-user login system with administrator functionality built with PHP and MySQL.

## Features

- 🔐 **Multi-User Authentication** - Secure login system with password hashing
- 👑 **Role-Based Access** - Admin and User roles with different permissions
- 📊 **Admin Dashboard** - Statistics and overview of the system
- 👥 **User Management** - Admin can add, edit, activate/deactivate, and delete users
- 🌐 **Multi-Language Support** - Indonesia (ID) and English (EN)
- 🎨 **Modern UI** - Clean and responsive design
- 🔒 **Security Features** - SQL injection prevention, XSS protection, session management

## Languages

This application supports the following languages:
- 🇮🇩 **Indonesia (Bahasa Indonesia)** - Default
- 🇬🇧 **English**

Language can be switched using the language selector available on:
- Login/Register pages (top-right corner)
- Admin sidebar (top of navigation)
- User sidebar (top of navigation)

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
│   ├── dashboard.php      # User dashboard page
│   ├── upload.php         # Upload report page
│   ├── reports.php        # User reports list
│   ├── review.php         # Report review page
│   ├── profile.php        # User profile settings
│   └── includes/
│       └── sidebar.php    # User sidebar navigation
├── assets/
│   └── css/
│       └── style.css      # Application styles
├── config/
│   ├── config.php         # Database configuration
│   └── language.php       # Language helper functions
├── includes/
│   └── language_switcher.php  # Language switcher component
├── lang/
│   ├── id.json            # Indonesian translations
│   ├── en.json            # English translations
│   └── switch.php         # Language switch handler
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

### Changing Language
1. Look for the language selector (dropdown) on the page
2. Select your preferred language (Indonesia or English)
3. The page will automatically refresh with the selected language
4. Language preference is stored in session

## Adding New Translations

To add new translations or modify existing ones:

1. Edit the language files in `lang/` folder:
   - `lang/id.json` for Indonesian
   - `lang/en.json` for English

2. Use dot notation to access nested keys:
   ```php
   __('login.username')      // Output: Username / Username
   __('user.upload_report')  // Output: Upload Report / Unggah Laporan
   ```

3. Add new keys following the same JSON structure:
   ```json
   {
       "new_section": {
           "new_key": "English Text"
       }
   }
   ```

4. Add corresponding translation in both `id.json` and `en.json`

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

### Language Configuration
Edit `config/language.php` to:
- Add new languages (modify `AVAILABLE_LANGUAGES`)
- Change default language (modify `DEFAULT_LANGUAGE`)

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

### Language Not Changing
- Ensure sessions are enabled in PHP
- Check if `lang/` folder contains both `id.json` and `en.json`
- Verify JSON files are valid (no syntax errors)

## License

This project is open-source and available for educational purposes.

## Support

For issues or questions, please contact the administrator.
