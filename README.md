# Inventory Tracking and Equipment Management System

A comprehensive web-based inventory management system built with PHP and MySQL for tracking equipment with barcode generation and admin activity logging.

## 🎯 System Overview

**Title:** Inventory Tracking and Equipment Management System

**Purpose:** To track equipments and manage the inventory of equipments using bar-code system

**Language:** PHP with MySQL database

**User:** Admin of the inventory management office

## ✨ Features

### 🔐 Authentication System
- **Login Form**: Secure admin authentication with email and password
- **Session Management**: Secure session handling with automatic logout
- **Access Control**: Role-based access control for admin users

### 📦 Equipment Management (CRUD Operations)
- **Add Equipment**: Add new equipment items with detailed information
- **Update Equipment**: Modify existing equipment details and status
- **Delete Equipment**: Remove duplicate or incorrect equipment entries
- **Search & Filter**: Advanced search functionality across all equipment fields

### 🏷️ Barcode System
- **Auto-generation**: Automatic barcode generation for each equipment
- **Unique Codes**: Ensures each equipment has a unique barcode identifier
- **Visual Display**: Barcode visualization and printing capabilities
- **Format**: EQ[YEAR][5-digit-number] (e.g., EQ202400001)

### 📊 Admin Activity Logs
- **Complete Tracking**: Monitor all admin activities in the system
- **Detailed Logging**: Tracks user actions, IP addresses, timestamps
- **Change History**: Before/after values for equipment modifications
- **Filtering**: Filter logs by admin, action type, and date
- **Pagination**: Efficient handling of large log datasets

### 📈 Reports & Analytics
- **Dashboard Statistics**: Real-time overview of equipment status
- **Visual Charts**: Pie charts, doughnut charts, and line graphs
- **Category Analysis**: Equipment breakdown by categories
- **Condition Tracking**: Monitor equipment condition status
- **Location Management**: Track equipment by location
- **Export Options**: Print-friendly report generation

## 🗄️ Database Structure

### Tables
1. **admin_users**: Admin user accounts and authentication
2. **equipment**: Equipment items with all details
3. **admin_logs**: Complete activity logging system

### Key Fields
- **Equipment**: ID, name, description, category, quantity, condition, location, barcode, purchase info
- **Admin Logs**: Admin ID, action, table affected, old/new values, IP, timestamp
- **Users**: ID, email, password (hashed), full name, timestamps

## 🛠️ Technical Specifications

### Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx
- **Extensions**: PDO, MySQL, Session support

### Technologies Used
- **Backend**: PHP with PDO for database operations
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Database**: MySQL with proper indexing
- **Charts**: Chart.js for data visualization
- **Icons**: Font Awesome 6
- **Security**: Password hashing, prepared statements, CSRF protection

## 🚀 Installation Guide

### Step 1: Database Setup
1. Create a MySQL database named `inventory_system`
2. Import the database schema from `database.sql`
3. Default admin credentials will be created:
   - **Email**: admin@inventory.com
   - **Password**: admin123

### Step 2: Configuration
1. Update database credentials in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'inventory_system');
   ```

### Step 3: File Permissions
Ensure proper file permissions for the web server to read/write files.

### Step 4: Web Server Setup
1. Point your web server document root to the project directory
2. Ensure mod_rewrite is enabled (for Apache)
3. Configure virtual host if needed

## 📱 System Navigation

### Main Pages
- **Login** (`login.php`): Admin authentication
- **Dashboard** (`dashboard.php`): System overview and statistics
- **Equipment List** (`equipment.php`): View and manage all equipment
- **Add Equipment** (`add_equipment.php`): Add new equipment items
- **Edit Equipment** (`edit_equipment.php`): Modify existing equipment
- **Reports** (`reports.php`): Analytics and reporting
- **Activity Logs** (`logs.php`): Admin activity monitoring

### Key Features Per Page
- **Dashboard**: Statistics cards, recent equipment, activity feed
- **Equipment**: Search, filter, barcode viewing, CRUD operations
- **Reports**: Charts, statistics tables, export options
- **Logs**: Filtering, pagination, detailed activity tracking

## 🔧 Configuration Options

### Database Settings
- Connection pooling support
- Error handling and logging
- Prepared statement usage for security

### Session Configuration
- Secure session handling
- Automatic timeout
- Cross-site request forgery protection

### Barcode Settings
- Customizable barcode format
- Unique code generation
- Visual representation options

## 🛡️ Security Features

### Authentication
- Password hashing using PHP's password_hash()
- Session-based authentication
- Automatic logout on inactivity

### Data Protection
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF token implementation

### Access Control
- Role-based access control
- Session validation on each request
- Secure logout functionality

## 📊 System Statistics

The system provides comprehensive statistics including:
- Total equipment count
- Equipment condition breakdown
- Category-wise distribution
- Location-based tracking
- Monthly addition trends
- Most expensive equipment
- Admin activity metrics

## 🎨 User Interface

### Design Features
- **Modern UI**: Clean, professional interface design
- **Responsive**: Mobile-friendly responsive design
- **Interactive**: Dynamic charts and real-time updates
- **User-Friendly**: Intuitive navigation and clear feedback
- **Accessibility**: WCAG compliant design elements

### Color Scheme
- Primary: Purple gradient (#667eea to #764ba2)
- Success: Green tones for positive actions
- Warning: Orange/yellow for attention items
- Danger: Red tones for critical actions
- Info: Blue tones for informational content

## 📝 Usage Instructions

### For Administrators

1. **Login**: Use admin credentials to access the system
2. **Add Equipment**: Navigate to "Add Equipment" and fill in details
3. **Manage Equipment**: Use the equipment list to search, edit, or delete items
4. **View Reports**: Check the reports page for analytics and insights
5. **Monitor Activity**: Review activity logs for system monitoring
6. **Generate Barcodes**: Each equipment automatically gets a unique barcode

### Best Practices
- Regularly backup the database
- Monitor activity logs for security
- Keep equipment information updated
- Use descriptive names and categories
- Maintain accurate location information

## 🔄 Maintenance

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
- Test barcode generation

## 📞 Support

For technical support or questions about the Inventory Tracking and Equipment Management System, please refer to the system documentation or contact your system administrator.

## 📄 License

This system is developed for internal use in inventory management offices. All rights reserved.

---

**Version**: 1.0  
**Last Updated**: 2024  
**Developed by**: System Development Team  
**Technology Stack**: PHP, MySQL, Bootstrap, Chart.js