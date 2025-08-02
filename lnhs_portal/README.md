# LNHS Documents Request Portal

A comprehensive web-based document request management system built with PHP and MySQL for Lipa National High School (LNHS). This system allows students and alumni to request various school documents online without physically visiting the school.

## 🎯 System Overview

**Title:** LNHS Documents Request Portal

**Purpose:** To provide students and alumni with online access to request school documents and certificates, eliminating the need for physical visits to the school.

**Language:** PHP with MySQL database

**Users:** 
- Students and Alumni (request documents)
- Administrators (manage requests and system)

## ✨ Features

### 🔐 Authentication System
- **Multi-user Login**: Separate login for students, alumni, and administrators
- **User Registration**: Students and alumni can register accounts
- **Session Management**: Secure session handling with automatic timeout
- **Access Control**: Role-based access control for different user types

### 📄 Document Request Management
- **Online Request Form**: Easy-to-use form for requesting documents
- **Document Types**: Support for various document types:
  - Certificate of Enrollment
  - Good Moral Certificate
  - Transcript of Records
  - Certificate of Graduation
  - Certificate of Transfer
  - Certificate of Completion
- **File Upload**: Support for uploading required documents/IDs
- **Request Tracking**: Real-time status tracking for all requests

### 📊 Request Status Tracking
- **Status Flow**: Pending → Processing → Approved/Denied → Ready for Pickup → Completed
- **Status History**: Complete tracking of status changes with timestamps
- **Admin Notes**: Administrators can add notes to requests
- **Request Numbers**: Unique request numbers for easy tracking

### 👨‍💼 Admin Dashboard
- **Request Management**: View, approve, deny, and update request statuses
- **User Management**: Manage student and alumni accounts
- **Document Type Management**: Add, edit, and configure document types
- **Statistics**: Comprehensive analytics and reporting
- **Activity Logs**: Complete audit trail of admin activities

### 📈 Reporting & Analytics
- **Dashboard Statistics**: Real-time overview of requests and users
- **Monthly Trends**: Visual charts showing request trends
- **Export Options**: Generate reports in Excel or PDF format
- **User Analytics**: Track user activity and request patterns

### 🔔 Notification System
- **Portal Notifications**: In-app notifications for status updates
- **Email Notifications**: Email alerts for important events (configurable)
- **SMS Notifications**: SMS alerts (optional, requires SMS gateway)
- **Real-time Updates**: Instant notification of status changes

### 📱 Modern User Interface
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Modern UI**: Clean, professional interface with gradient designs
- **User-Friendly**: Intuitive navigation and clear feedback
- **Accessibility**: WCAG compliant design elements

## 🗄️ Database Structure

### Core Tables
1. **users**: User accounts (students, alumni, admins)
2. **document_types**: Available document types and their configurations
3. **document_requests**: Main request records
4. **request_attachments**: File uploads for requests
5. **request_status_history**: Complete status change history
6. **notifications**: System notifications
7. **admin_logs**: Administrator activity logs
8. **system_settings**: System configuration settings

### Key Features
- **Foreign Key Relationships**: Proper data integrity
- **Indexing**: Optimized for performance
- **Audit Trail**: Complete activity logging
- **File Management**: Secure file upload and storage

## 🛠️ Technical Specifications

### Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx
- **Extensions**: PDO, MySQL, Session support, File upload support

### Technologies Used
- **Backend**: PHP with PDO for database operations
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Database**: MySQL with proper indexing
- **Charts**: Chart.js for data visualization
- **Icons**: Font Awesome 6
- **Security**: Password hashing, prepared statements, CSRF protection

## 🚀 Installation Guide

### Step 1: Database Setup
1. Create a MySQL database named `lnhs_documents_portal`
2. Import the database schema from `database.sql`
3. Default admin credentials will be created:
   - **Email**: admin@lnhs.edu.ph
   - **Password**: password

### Step 2: Configuration
1. Update database credentials in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'lnhs_documents_portal');
   ```

2. Configure email settings (optional):
   ```php
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'your-app-password');
   ```

### Step 3: File Permissions
Ensure proper file permissions for the web server:
```bash
chmod 755 lnhs_portal/
chmod 755 lnhs_portal/uploads/
```

### Step 4: Web Server Setup
1. Point your web server document root to the project directory
2. Ensure mod_rewrite is enabled (for Apache)
3. Configure virtual host if needed

## 📱 System Navigation

### For Students/Alumni
- **Login/Register**: `login.php`
- **Dashboard**: `dashboard.php`
- **Request Document**: `request_document.php`
- **My Requests**: `my_requests.php`
- **Profile**: `profile.php`

### For Administrators
- **Admin Dashboard**: `admin/dashboard.php`
- **Manage Requests**: `admin/requests.php`
- **Manage Users**: `admin/users.php`
- **Document Types**: `admin/document_types.php`
- **Reports**: `admin/reports.php`
- **Activity Logs**: `admin/logs.php`
- **Settings**: `admin/settings.php`

## 🔧 Configuration Options

### System Settings
- **School Information**: Name, address, contact details
- **File Upload**: Maximum file size, allowed file types
- **Notifications**: Email/SMS settings
- **Request Processing**: Auto-approval settings
- **Maintenance Mode**: System maintenance toggle

### Document Type Configuration
- **Processing Time**: Days required for processing
- **Requirements**: Required documents for each type
- **Fees**: Cost for each document type
- **Active Status**: Enable/disable document types

## 🛡️ Security Features

### Authentication & Authorization
- **Password Hashing**: Secure password storage using PHP's password_hash()
- **Session Security**: Secure session handling with timeout
- **CSRF Protection**: Cross-site request forgery protection
- **Input Validation**: Comprehensive input sanitization

### Data Protection
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Output encoding and sanitization
- **File Upload Security**: Type and size validation
- **Access Control**: Role-based permissions

### Audit & Logging
- **Admin Activity Logs**: Complete audit trail
- **Request History**: Full status change tracking
- **User Activity**: Login/logout tracking
- **Error Logging**: Comprehensive error tracking

## 📊 System Statistics

The system provides comprehensive statistics including:
- Total requests and status breakdown
- User statistics (students, alumni, admins)
- Monthly request trends
- Document type popularity
- Processing time analytics
- Admin activity metrics

## 🎨 User Interface

### Design Features
- **Modern UI**: Clean, professional interface design
- **Responsive**: Mobile-friendly responsive design
- **Interactive**: Dynamic charts and real-time updates
- **User-Friendly**: Intuitive navigation and clear feedback
- **Accessibility**: WCAG compliant design elements

### Color Scheme
- **Primary**: Purple gradient (#667eea to #764ba2)
- **Success**: Green tones for positive actions
- **Warning**: Orange/yellow for attention items
- **Danger**: Red tones for critical actions
- **Info**: Blue tones for informational content

## 📝 Usage Instructions

### For Students/Alumni

1. **Registration**: Create an account with your details
2. **Login**: Access the portal with your credentials
3. **Request Document**: Select document type and fill requirements
4. **Upload Files**: Attach required documents if needed
5. **Track Status**: Monitor your request status in real-time
6. **Receive Notifications**: Get notified of status changes

### For Administrators

1. **Login**: Use admin credentials to access the system
2. **Review Requests**: Check pending requests in the dashboard
3. **Update Status**: Change request statuses as needed
4. **Manage Users**: Handle user accounts and permissions
5. **Generate Reports**: Create analytics and reports
6. **Monitor Activity**: Review system logs and activities

## 🔄 Workflow

### Document Request Process
1. **Student/Alumni submits request** → Status: Pending
2. **Admin reviews request** → Status: Processing
3. **Admin approves/denies** → Status: Approved/Denied
4. **Document prepared** → Status: Ready for Pickup
5. **Document collected** → Status: Completed

### Notification Flow
- Request submitted → Notification to admin
- Status changed → Notification to user
- Document ready → Notification to user
- Additional requirements → Notification to user

## 🔧 Maintenance

### Regular Tasks
- Database backup and optimization
- Log file rotation and cleanup
- Security updates and patches
- Performance monitoring
- User access review

### Troubleshooting
- Check database connectivity
- Verify file permissions
- Review error logs
- Validate session configuration
- Test email notifications

## 📞 Support

For technical support or questions about the LNHS Documents Request Portal, please refer to the system documentation or contact your system administrator.

## 📄 License

This system is developed for internal use in Lipa National High School. All rights reserved.

---

**Version**: 1.0  
**Last Updated**: 2024  
**Developed by**: System Development Team  
**Technology Stack**: PHP, MySQL, Bootstrap, Chart.js  
**School**: Lipa National High School