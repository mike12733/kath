# LNHS Documents Request Portal

A comprehensive web-based document request management system for LNHS (Local National High School) that allows students and alumni to request various certificates and documents online.

## 🎯 System Overview

**Title:** LNHS Documents Request Portal

**Purpose:** Provides students and alumni with online access to request school documents and certificates without physically visiting the school.

## ✨ Features

### 🔐 Authentication System
- **Login System** for students, alumni, and admin
- **User Registration** for students and alumni
- **Session Management** with secure logout
- **Role-based Access Control** (Admin, Student, Alumni)

### 📋 Document Request Management
- **Online Request Form** with the following fields:
  - Document type selection
  - Purpose of request
  - Preferred release date
  - File upload for requirements/valid ID
- **Supported Document Types:**
  - Certificate of Enrollment
  - Good Moral Certificate
  - Transcript of Records
  - Certificate of Graduation
  - Certificate of Transfer

### 📊 Request Tracking System
- **Status Tracking:** Pending → Processing → Approved/Denied → Ready for Pickup
- **Real-time Status Updates**
- **Request History** for users
- **Detailed Request View** with attachments

### 👨‍💼 Admin Dashboard
- **Comprehensive Dashboard** with statistics
- **Request Management** with filtering and search
- **Bulk Status Updates** for multiple requests
- **User Management** capabilities
- **System Logs** and activity tracking

### 📈 Reporting & Export
- **Generate Reports** in Excel and PDF formats
- **Data Export** with filtering options
- **System Statistics** and analytics
- **Activity Logs** for audit trails

### 🔔 Notification System
- **Portal Notifications** for status updates
- **Email/SMS Ready** notification system
- **Real-time Alerts** for new requests
- **Admin Notes** and communication

## 🛠️ Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework:** Bootstrap 5.1.3
- **Icons:** Font Awesome 6.0
- **Server:** Apache/Nginx (XAMPP compatible)

## 📋 Requirements

- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Web Server:** Apache/Nginx
- **XAMPP:** For local development
- **Browser:** Modern web browser with JavaScript enabled

## 🚀 Installation Guide

### Step 1: Download and Extract
1. Download the project files
2. Extract to your XAMPP `htdocs` folder:
   ```
   C:\xampp\htdocs\lnhs-portal\
   ```

### Step 2: Database Setup
1. Start XAMPP Control Panel
2. Start Apache and MySQL services
3. Open phpMyAdmin: `http://localhost/phpmyadmin`
4. Create a new database or import the provided `database.sql` file
5. The system will automatically create the database if it doesn't exist

### Step 3: Configuration
1. Open `config/database.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'lnhs_documents_portal');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### Step 4: File Permissions
1. Create uploads directory (if not exists):
   ```bash
   mkdir uploads
   ```
2. Set write permissions for uploads folder:
   ```bash
   chmod 755 uploads
   ```

### Step 5: Access the System
1. Open your browser
2. Navigate to: `http://localhost/lnhs-portal/`
3. Use default admin credentials:
   - **Username:** admin
   - **Password:** password

## 👥 User Types

### 🔧 Admin
- **Access:** Full system access
- **Features:**
  - Manage all document requests
  - Update request statuses
  - Generate reports and exports
  - Manage users
  - View system logs
  - Configure document types

### 👨‍🎓 Student
- **Access:** Limited to own requests
- **Features:**
  - Submit document requests
  - Track request status
  - View request history
  - Upload required documents
  - Receive notifications

### 👨‍🎓 Alumni
- **Access:** Limited to own requests
- **Features:**
  - Submit document requests
  - Track request status
  - View request history
  - Upload required documents
  - Receive notifications

## 📁 File Structure

```
lnhs-portal/
├── config/
│   └── database.php          # Database configuration
├── admin/
│   ├── dashboard.php         # Admin dashboard
│   ├── manage_requests.php   # Request management
│   ├── export_data.php       # Data export
│   └── ...                   # Other admin files
├── user/
│   ├── dashboard.php         # User dashboard
│   ├── request_document.php  # Document request processing
│   └── ...                   # Other user files
├── uploads/                  # File upload directory
├── index.php                 # Login page
├── register.php              # Registration page
├── logout.php                # Logout functionality
├── database.sql              # Database structure
└── README.md                 # This file
```

## 🔧 Configuration

### Database Configuration
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lnhs_documents_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Document Types
Default document types are included in the database:
- Certificate of Enrollment (₱50.00)
- Good Moral Certificate (₱75.00)
- Transcript of Records (₱150.00)
- Certificate of Graduation (₱100.00)
- Certificate of Transfer (₱50.00)

## 🚀 Usage Guide

### For Students/Alumni
1. **Register** an account or **Login** if already registered
2. **Submit Request** by filling out the document request form
3. **Upload Requirements** (valid ID, etc.)
4. **Track Status** through the dashboard
5. **Receive Notifications** for status updates

### For Admin
1. **Login** with admin credentials
2. **View Dashboard** for system overview
3. **Manage Requests** through the requests page
4. **Update Statuses** individually or in bulk
5. **Generate Reports** and export data
6. **Monitor System** through logs and statistics

## 🔒 Security Features

- **Password Hashing** using PHP's password_hash()
- **SQL Injection Prevention** with prepared statements
- **Session Management** with secure logout
- **File Upload Validation** for security
- **Role-based Access Control**
- **Activity Logging** for audit trails

## 📊 Database Schema

### Main Tables
- **users** - User accounts and profiles
- **document_types** - Available document types
- **document_requests** - Document request records
- **request_attachments** - Uploaded files
- **notifications** - System notifications
- **system_logs** - Activity logs

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check XAMPP services are running
   - Verify database credentials in config/database.php
   - Ensure database exists

2. **File Upload Issues**
   - Check uploads directory permissions
   - Verify PHP upload settings in php.ini
   - Check file size limits

3. **Session Issues**
   - Clear browser cookies
   - Check PHP session configuration
   - Verify session storage permissions

### Error Logs
Check XAMPP error logs:
- Apache: `C:\xampp\apache\logs\error.log`
- PHP: `C:\xampp\php\logs\php_error_log`

## 🔄 Updates and Maintenance

### Regular Maintenance
1. **Backup Database** regularly
2. **Monitor Logs** for errors
3. **Update PHP** and dependencies
4. **Clean Uploads** directory periodically

### Adding New Features
1. **Document Types** - Add to document_types table
2. **User Roles** - Modify user_type enum
3. **Status Types** - Update status enum in document_requests

## 📞 Support

For technical support or questions:
- Check the troubleshooting section
- Review error logs
- Ensure all requirements are met
- Verify XAMPP configuration

## 📄 License

This project is developed for educational purposes and internal use by LNHS.

## 🎉 Credits

Developed for LNHS Documents Request Portal
- **Language:** PHP
- **Framework:** Bootstrap
- **Icons:** Font Awesome
- **Database:** MySQL

---

**Note:** This system is designed to be compatible with XAMPP and can be easily imported into phpMyAdmin. All features are fully functional and organized for optimal user experience.