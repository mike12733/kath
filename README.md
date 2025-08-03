# LNHS Documents Request Portal

A comprehensive document request management system for Laguna National High School (LNHS) that allows students and alumni to request official documents online while providing administrators with tools to manage and track these requests.

## 🌟 Features

### For Students & Alumni
- **User Registration & Login**: Secure authentication system for students and alumni
- **Document Request Form**: Online form to request various official documents
- **File Upload**: Upload required documents (ID, previous certificates, etc.)
- **Request Tracking**: Real-time tracking of request status with timeline view
- **Notifications**: Email and portal notifications for status updates
- **Dashboard**: Overview of all requests with statistics

### For Administrators
- **Admin Dashboard**: Comprehensive overview of system statistics
- **Request Management**: View, approve, deny, and update request statuses
- **User Management**: Manage student and alumni accounts
- **Reports**: Generate reports and export data
- **System Settings**: Configure system parameters and document types

### Document Types Available
- Certificate of Enrollment
- Good Moral Certificate
- Transcript of Records
- Diploma Copy
- Certificate of Graduation

## 🚀 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Icons**: Font Awesome 6
- **Server**: Compatible with XAMPP/WAMP/LAMP

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP (for local development)

## 🛠️ Installation & Setup

### Step 1: Download and Extract
1. Download the project files
2. Extract to your web server directory (e.g., `htdocs` for XAMPP)

### Step 2: Database Setup
1. Start your MySQL server (through XAMPP Control Panel)
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database or import the provided SQL file:
   - Click "Import" tab
   - Choose file: `database.sql`
   - Click "Go"

### Step 3: Configuration
1. Update database configuration in `config/database.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'lnhs_portal');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### Step 4: File Permissions
1. Create uploads directory: `mkdir uploads`
2. Set permissions for uploads folder:
   ```bash
   chmod 755 uploads/
   ```

### Step 5: Access the System
1. Open your web browser
2. Navigate to: `http://localhost/lnhs_portal/`
3. You will be redirected to the login page

## 👤 Default Admin Account

**Email**: admin@lnhs.edu.ph  
**Password**: password

⚠️ **Important**: Change the default admin password after first login!

## 📖 User Guide

### For Students/Alumni

1. **Registration**:
   - Click "Create Account" on login page
   - Fill in all required information
   - Select account type (Student/Alumni)
   - Complete registration

2. **Requesting Documents**:
   - Login to your account
   - Click "Request Document"
   - Select document type
   - Fill in purpose and details
   - Upload required files
   - Submit request

3. **Tracking Requests**:
   - Go to "Track Requests"
   - View all your requests
   - Filter by status
   - Click on request to view details

### For Administrators

1. **Managing Requests**:
   - Login with admin account
   - Go to "Manage Requests"
   - Review pending requests
   - Update status and add notes
   - Approve or deny requests

2. **User Management**:
   - Access "Manage Users"
   - View all registered users
   - Activate/deactivate accounts
   - View user details

## 🗂️ File Structure

```
lnhs_portal/
├── admin/                  # Admin-specific pages
│   ├── dashboard.php
│   ├── manage-requests.php
│   └── manage-users.php
├── assets/                 # Static assets
│   └── css/
│       └── style.css
├── classes/                # PHP classes
│   ├── User.php
│   ├── DocumentRequest.php
│   └── Notification.php
├── config/                 # Configuration files
│   └── database.php
├── uploads/                # Uploaded files
├── database.sql            # Database schema
├── index.php              # Main entry point
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # User dashboard
├── request-document.php   # Document request form
├── track-requests.php     # Request tracking
├── view-request.php       # Request details
└── logout.php            # Logout handler
```

## ⚙️ Configuration Options

### Document Types
Edit document types in the database table `document_types`:
- Add new document types
- Set processing days
- Configure fees
- Set requirements

### System Settings
Configure system settings in `system_settings` table:
- Contact information
- Office hours
- File upload limits
- Email notifications

### Email Notifications
To enable email notifications:
1. Configure SMTP settings in `classes/Notification.php`
2. Update system settings to enable email notifications

## 🔧 Customization

### Adding New Document Types
1. Insert into `document_types` table:
   ```sql
   INSERT INTO document_types (name, description, requirements, processing_days, fee) 
   VALUES ('New Document', 'Description', 'Requirements', 5, 100.00);
   ```

### Modifying Status Workflow
Edit status transitions in `classes/DocumentRequest.php`:
- Add new statuses
- Modify workflow logic
- Update status messages

### UI Customization
- Modify `assets/css/style.css` for styling changes
- Update Bootstrap classes in templates
- Add custom JavaScript functionality

## 📊 Database Schema

### Main Tables
- `users` - User accounts and profiles
- `document_requests` - Document request records
- `document_types` - Available document types
- `request_attachments` - Uploaded files
- `notifications` - System notifications
- `activity_logs` - User activity tracking
- `system_settings` - System configuration

## 🛡️ Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- File upload validation
- Session management
- Admin-only access control

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check database credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database exists

2. **File Upload Issues**:
   - Check `uploads/` directory permissions
   - Verify PHP file upload settings
   - Check file size limits

3. **Login Problems**:
   - Verify user exists in database
   - Check password hash
   - Clear browser cache/cookies

4. **Permission Denied**:
   - Check file/folder permissions
   - Verify web server configuration
   - Ensure proper ownership

## 📞 Support

For technical support or questions:
- **Email**: admin@lnhs.edu.ph
- **Phone**: (02) 8123-4567
- **Office Hours**: Monday - Friday, 8:00 AM - 5:00 PM

## 📄 License

This project is developed for Laguna National High School. All rights reserved.

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality
  - User registration and authentication
  - Document request system
  - Admin dashboard
  - Request tracking
  - Notification system

---

**Note**: This system is designed specifically for LNHS document request management. Customize as needed for your institution's requirements.