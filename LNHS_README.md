# LNHS Documents Request Portal

A comprehensive web-based system for managing document requests at LNHS (Laguna National High School). This system allows students and alumni to request academic documents online without needing to visit the school physically.

## 🎯 Features

### For Students & Alumni
- **User Registration & Login**: Secure account creation for students and alumni
- **Document Request Form**: Online form to request various documents including:
  - Certificate of Enrollment
  - Good Moral Certificate
  - Transcript of Records
  - Diploma Copy
  - Certificate of Graduation
- **File Upload**: Upload required documents (Valid ID, etc.)
- **Request Tracking**: Real-time status tracking with progress indicators:
  - ✅ Pending → Processing → Approved/Denied → Ready for Pickup → Completed
- **Notifications**: In-portal notifications for status updates
- **Profile Management**: Update personal information

### For Administrators
- **Admin Dashboard**: Comprehensive overview of all requests
- **Request Management**: View, process, approve/deny requests
- **Status Updates**: Update request status with notes
- **User Management**: View all registered users
- **Reports & Export**: Export request data to CSV
- **Statistics**: Dashboard with key metrics

## 🛠 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **Architecture**: MVC Pattern with OOP

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO MySQL extension enabled

## 🚀 Installation & Setup

### 1. Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE lnhs_documents_portal;
```

2. Import the database schema:
```bash
mysql -u your_username -p lnhs_documents_portal < lnhs_database.sql
```

### 2. Configuration

1. Update database connection settings in `config/lnhs_database.php`:
```php
define('LNHS_DB_HOST', 'localhost');
define('LNHS_DB_USERNAME', 'your_username');
define('LNHS_DB_PASSWORD', 'your_password');
define('LNHS_DB_NAME', 'lnhs_documents_portal');
```

### 3. File Permissions

Create necessary directories and set permissions:
```bash
mkdir uploads/requests
chmod 777 uploads/requests
mkdir logs
chmod 777 logs
```

### 4. Web Server Setup

Point your web server document root to the project directory or create a virtual host.

## 👤 Default Admin Account

- **Email**: admin@lnhs.edu.ph
- **Password**: admin123

⚠️ **Important**: Change the default admin password after first login!

## 📁 Project Structure

```
lnhs-documents-portal/
├── admin/                  # Admin dashboard and functions
│   ├── dashboard.php
│   ├── update_status.php
│   └── export_requests.php
├── auth/                   # Authentication scripts
│   ├── login_process.php
│   ├── register_process.php
│   └── logout.php
├── classes/                # PHP classes
│   ├── User.php
│   ├── DocumentRequest.php
│   └── EmailNotification.php
├── config/                 # Configuration files
│   └── lnhs_database.php
├── user/                   # User dashboard and functions
│   ├── dashboard.php
│   ├── request_process.php
│   ├── request_details.php
│   └── profile_update.php
├── uploads/                # File uploads directory
│   └── requests/
├── logs/                   # System logs
├── lnhs_index.php         # Main entry point
├── lnhs_database.sql      # Database schema
└── LNHS_README.md         # This file
```

## 🔧 Usage

### For Students/Alumni

1. **Registration**:
   - Visit the main page
   - Click "Register" tab
   - Fill in required information
   - Select account type (Student/Alumni)
   - Submit registration

2. **Login**:
   - Use email and password to login
   - Redirected to user dashboard

3. **Request Documents**:
   - Click "New Document Request"
   - Select document type
   - Fill in purpose and preferred date
   - Upload required files
   - Submit request

4. **Track Requests**:
   - View request status on dashboard
   - Click "View" for detailed information
   - Receive notifications for updates

### For Administrators

1. **Login**:
   - Use admin credentials
   - Redirected to admin dashboard

2. **Manage Requests**:
   - View pending requests
   - Process requests by changing status
   - Add admin notes
   - Approve/deny requests

3. **Generate Reports**:
   - Export all requests to CSV
   - View statistics and metrics

## 🔐 Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session management for authentication
- File upload validation and restrictions
- Access control for admin functions

## 📊 Database Schema

### Main Tables:
- `users` - User accounts (students, alumni, admin)
- `document_types` - Available document types
- `document_requests` - Main requests table
- `request_attachments` - Uploaded files
- `request_status_history` - Status change tracking
- `notifications` - User notifications

## 🚨 Troubleshooting

### Common Issues:

1. **Database Connection Error**:
   - Check database credentials in config file
   - Ensure MySQL service is running
   - Verify database exists

2. **File Upload Issues**:
   - Check upload directory permissions
   - Verify PHP upload settings in php.ini
   - Ensure sufficient disk space

3. **Email Notifications Not Working**:
   - Configure PHP mail settings
   - Check server mail configuration
   - Enable email debug mode for testing

## 🔄 Updates & Maintenance

### Regular Tasks:
- Monitor upload directory size
- Review and archive old requests
- Update admin passwords regularly
- Backup database regularly

### Logs:
- Check error logs in `logs/` directory
- Monitor email logs for delivery issues

## 📞 Support

For technical support or questions about the LNHS Documents Request Portal, please contact the IT department or system administrator.

## 📄 License

This system is developed specifically for LNHS internal use. All rights reserved.

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Developed for**: Laguna National High School