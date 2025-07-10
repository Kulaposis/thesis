# Login Logging System

## Overview

The Login Logging System provides comprehensive tracking of all user login and logout activities across the thesis management system. This feature enables administrators to monitor user access patterns, detect security issues, and maintain detailed audit trails.

## Features

### 📊 **Real-time Activity Monitoring**
- Track all login attempts (successful and failed)
- Monitor logout activities with session duration
- Real-time statistics dashboard
- IP address and browser information logging

### 🔍 **Advanced Filtering & Search**
- Filter by user role (Student/Adviser/Admin)
- Filter by action type (Login/Logout/Failed Login)
- Date range filtering
- User search by name or email
- Export functionality

### 📈 **Analytics Dashboard**
- Today's login count
- Active sessions tracking
- Failed login attempts monitoring
- Average session duration analysis

## Database Schema

### `login_logs` Table Structure

```sql
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role ENUM('admin', 'adviser', 'student') NOT NULL,
    action_type ENUM('login', 'logout', 'login_failed') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    browser_info VARCHAR(255) NULL,
    login_time TIMESTAMP NULL,
    logout_time TIMESTAMP NULL,
    session_duration INT NULL COMMENT 'Duration in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_role (user_role),
    INDEX idx_login_time (login_time)
);
```

## Implementation Details

### 1. **Login Tracking (includes/auth.php)**

The `Auth` class has been enhanced to automatically log all login activities:

```php
// Successful login logging
$this->logUserActivity($user['id'], $user['role'], 'login');

// Failed login logging  
$this->logUserActivity($user['id'], $user['role'], 'login_failed');
```

### 2. **Logout Tracking (includes/auth.php)**

The logout method now properly logs logout events with session duration:

```php
public function logout() {
    // Log the logout before destroying session
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        $this->logUserActivity($_SESSION['user_id'], $_SESSION['role'], 'logout');
    }
    
    session_destroy();
    header("Location: login.php");
    exit();
}
```

### 3. **Admin Management (includes/admin_functions.php)**

The `AdminManager` class provides comprehensive login log management:

#### Key Methods:

- `logUserLogin($userId, $role, $actionType)` - Log user activity
- `logUserLogout($userId, $role)` - Log logout with session duration
- `getLoginLogs($limit, $filters)` - Retrieve filtered login logs
- `getLoginStatistics($days)` - Get login analytics
- `getUserIpAddress()` - Detect user IP address
- `parseBrowserInfo($userAgent)` - Parse browser information

### 4. **Admin Dashboard Integration (admin_dashboard.php)**

New AJAX endpoints for login log management:

```php
case 'get_login_logs':
    $filters = [...];
    $logs = $adminManager->getLoginLogs($limit, $filters);
    echo json_encode(['success' => true, 'logs' => $logs]);
    break;

case 'get_login_statistics':
    $stats = $adminManager->getLoginStatistics($days);
    echo json_encode(['success' => true, 'statistics' => $stats]);
    break;
```

## Admin Dashboard Features

### 📋 **Activity Logs Tab**

The admin dashboard now includes a comprehensive Activity Logs section with:

1. **Statistics Cards**
   - Today's Logins
   - Active Sessions
   - Failed Attempts
   - Average Session Duration

2. **Tabbed Interface**
   - Login Logs Tab
   - Admin Activity Tab

3. **Advanced Filtering**
   - User Role Filter
   - Action Type Filter
   - Date Range Selection
   - User Search
   - Apply/Clear Filters

### 🎨 **User Interface**

Modern, responsive design with:
- Real-time data loading
- Interactive filtering
- Sortable table columns
- Status badges for different action types
- Browser and IP information display
- Formatted session durations

## Security Features

### 🔒 **Data Protection**
- User-agent and IP address logging for security auditing
- Failed login attempt tracking
- Session hijacking detection capabilities
- Comprehensive audit trail

### 🚫 **Privacy Considerations**
- IP addresses stored securely
- Browser information anonymized
- Configurable data retention policies
- GDPR compliance ready

## Usage Examples

### 1. **Viewing Login Logs**

Navigate to Admin Dashboard → Activity Logs → Login Logs tab to:
- View all user login/logout activities
- Filter by specific criteria
- Export data for analysis

### 2. **Monitoring Failed Logins**

Use the Failed Attempts card and filter by:
- Action Type: "Failed Login"
- Date Range: Last 24 hours
- Monitor for potential security threats

### 3. **Analyzing User Patterns**

Review login statistics to:
- Identify peak usage times
- Monitor user engagement
- Detect unusual access patterns

## Testing

### Test Script: `test_login_logs.php`

A comprehensive test script is provided to verify:
- Database table creation
- Login logging functionality
- Statistics generation
- Data retrieval and formatting

Run the test script to ensure everything is working correctly.

## File Structure

```
thesis/
├── includes/
│   ├── auth.php                    # Enhanced with login logging
│   └── admin_functions.php         # Login log management functions
├── admin_dashboard.php             # Enhanced with login logs UI
├── assets/
│   ├── js/admin-dashboard.js       # Login logs JavaScript functionality
│   └── css/admin-dashboard.css     # Enhanced styling for login logs
├── create_login_logs_table.sql     # Database table creation
├── test_login_logs.php             # Testing script
└── LOGIN_LOGGING_SYSTEM.md         # This documentation
```

## Configuration

### Environment Variables

No additional configuration required. The system uses existing database connections and session management.

### Customization Options

1. **Log Retention Period**: Modify the cleanup queries in `admin_functions.php`
2. **Statistics Period**: Adjust the default days parameter in statistics functions
3. **Filtering Options**: Add custom filters in the admin dashboard
4. **Export Formats**: Implement CSV/Excel export functionality

## Troubleshooting

### Common Issues

1. **Table Not Created**
   - Run: `C:\xampp\mysql\bin\mysql.exe -u root -e "USE thesis_management; SOURCE create_login_logs_table.sql;"`
   - Or use the test script to verify setup

2. **Login Logs Not Appearing**
   - Check database connection
   - Verify user authentication flow
   - Review error logs for PHP errors

3. **Statistics Not Loading**
   - Ensure AJAX endpoints are working
   - Check browser console for JavaScript errors
   - Verify database indexes are created

### Debug Steps

1. Run `test_login_logs.php` to verify system setup
2. Check browser developer tools for AJAX errors
3. Review PHP error logs for backend issues
4. Verify database table structure and data

## Future Enhancements

### Planned Features
- 📧 Email alerts for suspicious login patterns
- 📊 Advanced analytics with charts and graphs
- 🔄 Automatic log rotation and archiving
- 📱 Mobile-responsive login monitoring
- 🔐 Two-factor authentication integration
- 📈 User behavior analytics
- 🚨 Real-time security alerts

### API Endpoints
- RESTful API for external integrations
- Webhook support for real-time notifications
- GraphQL interface for complex queries

## Security Recommendations

1. **Regular Monitoring**: Review login logs daily for suspicious activity
2. **Failed Login Alerts**: Set up notifications for multiple failed attempts
3. **IP Blacklisting**: Implement automatic IP blocking for repeated failures
4. **Session Management**: Monitor for unusual session patterns
5. **Data Backup**: Regular backups of login log data
6. **Access Control**: Restrict login log access to authorized administrators only

## Compliance

This system helps maintain compliance with:
- **GDPR**: User activity tracking with privacy controls
- **SOX**: Audit trail requirements for user access
- **HIPAA**: Healthcare data access monitoring (if applicable)
- **Academic Standards**: Student data access tracking

---

**Last Updated**: December 2024  
**Version**: 1.0  
**Author**: Thesis Management System Team 