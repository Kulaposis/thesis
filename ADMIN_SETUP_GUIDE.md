# Admin Dashboard Setup Guide

## 🎯 **What We've Built**

Your new admin dashboard includes:
- ✅ **System Overview**: Real-time statistics and health monitoring
- ✅ **User Management**: Create, edit, delete users with bulk operations
- ✅ **Advanced Analytics**: Department performance, activity trends, adviser workload
- ✅ **Announcements**: System-wide communication tools  
- ✅ **Activity Logs**: Complete audit trail of admin actions
- ✅ **Security**: Role-based access control and admin logging

---

## 🛠️ **Installation Steps**

### **Step 1: Setup Database**

1. **Run the database setup script:**
   ```bash
   # Option 1: Via phpMyAdmin
   # - Open phpMyAdmin (http://localhost/phpmyadmin)
   # - Select your thesis_management database
   # - Go to SQL tab
   # - Copy and paste the contents of admin_database_setup.sql
   # - Click Go

   # Option 2: Via command line
   mysql -u root -p thesis_management < admin_database_setup.sql
   ```

2. **Verify the setup:**
   - Check that new tables were created: `system_settings`, `admin_logs`, `announcements`, etc.
   - Verify the admin user was created: `admin@thesis.edu`

### **Step 2: Copy Files to Your Project**

1. **Copy the admin functions:**
   ```bash
   # Copy includes/admin_functions.php to your includes/ directory
   cp includes/admin_functions.php /path/to/your/thesis_management/includes/
   ```

2. **Copy the admin dashboard:**
   ```bash
   # Copy admin_dashboard.php to your project root
   cp admin_dashboard.php /path/to/your/thesis_management/
   ```

### **Step 3: Test Access**

1. **Login as Super Admin:**
   - URL: `http://localhost/thesis_management/admin_dashboard.php`
   - Email: `admin@thesis.edu`
   - Password: `password123`

2. **If you get access denied:**
   - The system redirects non-admins to studentDashboard.php
   - Make sure you're using the admin credentials above

---

## 🔧 **Configuration Options**

### **Add Admin Access to Existing Users**

You can promote existing users to admin:

```sql
-- Make an existing user an admin
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';

-- Or make them a super admin
UPDATE users SET role = 'super_admin' WHERE email = 'your-email@example.com';
```

### **Customize System Settings**

The system includes configurable settings:

```sql
-- View current settings
SELECT * FROM system_settings;

-- Update a setting
UPDATE system_settings SET setting_value = 'My Custom Thesis System' WHERE setting_key = 'site_name';
```

---

## 📊 **Features Overview**

### **1. Overview Tab** 
- **System Statistics**: Student count, active theses, pending reviews
- **Health Monitoring**: Database status, file permissions, error rates  
- **Visual Analytics**: Department performance charts, activity trends
- **Recent Activity**: Latest announcements and admin actions

### **2. User Management Tab**
- **User List**: Searchable, filterable table of all users
- **Bulk Operations**: Mass password reset, role changes, deletions
- **Advanced Filters**: By role, department, name/email search
- **Real-time Actions**: Reset passwords, delete users instantly

### **3. Analytics Tab**
- **Department Performance**: Student counts, progress averages  
- **Adviser Workload**: Supervision statistics and performance
- **Trend Analysis**: Monthly activity patterns
- **Custom Reports**: Detailed performance breakdowns

### **4. Announcements Tab**
- **System Announcements**: Broadcast messages to users
- **Targeted Communication**: By role or department
- **Priority Levels**: Normal, high, urgent messaging
- **Expiration Dates**: Auto-expiring announcements

### **5. Activity Logs Tab**
- **Complete Audit Trail**: Every admin action logged
- **Detailed Information**: Who, what, when, where
- **Security Monitoring**: Track all system changes
- **Filtering Options**: By admin, action type, date range

---

## 🎨 **UI/UX Features**

### **Design Consistency**
- **Matches Your System**: Uses same TailwindCSS styling as existing pages
- **Responsive Layout**: Works on desktop, tablet, mobile
- **Lucide Icons**: Consistent iconography throughout
- **Color Scheme**: Maintains your blue/gray theme

### **Interactive Elements**
- **Live Charts**: Real-time data visualization with Chart.js
- **Tab Navigation**: Smooth switching between admin sections  
- **Real-time Filters**: Instant search and filtering
- **Loading States**: Proper feedback for async operations

---

## 🔒 **Security Features**

### **Access Control**
- **Role-Based Permissions**: Only admins and super_admins can access
- **Session Validation**: Automatic redirect for unauthorized users
- **Action Logging**: Every admin action is recorded

### **Audit Trail**
- **Complete Logging**: IP address, user agent, timestamp
- **Action Details**: Full context of what was changed
- **Security Monitoring**: Track failed access attempts
- **Data Integrity**: All changes are traceable

---

## 🚀 **Testing Your Setup**

### **Basic Functionality Test**

1. **Login as Admin:**
   - Navigate to `admin_dashboard.php`
   - Use admin credentials: `admin@thesis.edu` / `password123`

2. **Test Overview Tab:**
   - Check that statistics cards show correct numbers
   - Verify charts are displaying (may be empty with sample data)
   - Confirm system health shows green status

3. **Test User Management:**
   - Click "User Management" tab
   - Verify user list loads
   - Test filters (role, search, department)
   - Try resetting a password (will show new password in alert)

4. **Test Activity Logs:**
   - Click "Activity Logs" tab  
   - Should show recent admin actions
   - Try performing an action (like password reset) and refresh to see it logged

### **Troubleshooting Common Issues**

**Issue 1: "Access Denied" or redirected to student dashboard**
```php
// Solution: Check user role in database
SELECT email, role FROM users WHERE email = 'admin@thesis.edu';
// Should show role as 'super_admin'
```

**Issue 2: Charts not displaying**
```javascript
// Check browser console for errors
// Ensure Chart.js is loading: https://cdn.jsdelivr.net/npm/chart.js
// Verify analytics data is being returned
```

**Issue 3: User table not loading**
```php
// Check if admin_functions.php is in the correct location
// Verify database connection in config/database.php
// Check browser network tab for AJAX errors
```

**Issue 4: Database errors**
```sql
-- Verify all tables were created
SHOW TABLES LIKE 'admin_%';
SHOW TABLES LIKE 'system_%';

-- Check if admin user exists
SELECT * FROM users WHERE role IN ('admin', 'super_admin');
```

---

## 🔧 **Customization Options**

### **Add Your Own Admin Sections**

1. **Add New Tab:**
   ```javascript
   // Add to tab navigation
   <button onclick="showTab('mytab')" class="tab-button">My Feature</button>
   
   // Add tab content
   <div id="mytab-tab" class="tab-content hidden">
       <!-- Your content here -->
   </div>
   ```

2. **Add New Admin Functions:**
   ```php
   // In includes/admin_functions.php
   public function myCustomFunction() {
       // Your admin functionality
   }
   ```

3. **Add New AJAX Actions:**
   ```php
   // In admin_dashboard.php POST handler
   case 'my_action':
       // Handle your custom action
       break;
   ```

### **Modify Styling**

```css
/* The system uses TailwindCSS, you can customize by adding classes */
/* Colors: blue-600, green-600, red-600, gray-600 */
/* Backgrounds: bg-white, bg-gray-50, bg-blue-100 */
/* Text: text-gray-900, text-gray-600, text-blue-600 */
```

---

## 📞 **Next Steps**

1. **Test the basic functionality** using the testing guide above
2. **Customize the system settings** to match your institution
3. **Add additional admin users** as needed
4. **Explore the analytics features** to understand your system usage
5. **Set up announcements** to communicate with users

### **Optional Enhancements**

- **Email Integration**: Add email notifications for admin actions
- **Backup System**: Automated database backups
- **Advanced Permissions**: Granular role-based permissions
- **API Documentation**: REST API for mobile apps
- **Export Features**: CSV/Excel export for reports

---

## 🎉 **Congratulations!**

You now have a comprehensive admin dashboard that provides:
- **Complete System Control**: Manage all aspects of your thesis system
- **Real-time Monitoring**: Track system health and user activity  
- **Advanced Analytics**: Understand system usage and performance
- **Professional Interface**: Modern, responsive, and intuitive design

Your thesis management system is now enterprise-ready with powerful administrative capabilities!

**Need help?** Check the troubleshooting section above or review the code comments for implementation details.