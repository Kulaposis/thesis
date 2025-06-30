# Thesis Management System

A comprehensive online thesis management system built with PHP and MySQL for XAMPP. This system allows students and advisers to manage thesis projects collaboratively with real-time progress tracking, feedback management, and timeline monitoring.

## Features

### For Students:
- **Dashboard Overview**: View thesis progress, chapter status, and recent activity
- **Chapter Management**: Create, edit, and submit thesis chapters
- **Progress Tracking**: Visual progress bars and completion percentages
- **Adviser Feedback**: View feedback and comments from advisers
- **Timeline Management**: Track milestones and deadlines
- **Document Uploads**: Upload and manage thesis documents (PDF, DOC, DOCX) for each chapter

### For Advisers:
- **Student Management**: Oversee multiple student theses
- **Student Assignment**: Add new students or assign existing students to yourself
- **Review System**: Review and approve/reject chapters
- **Feedback Tools**: Provide detailed feedback on submissions
- **Progress Monitoring**: Track student progress across all supervised theses
- **Analytics Dashboard**: View statistics and reports

### System Features:
- **User Authentication**: Secure login/registration for students and advisers
- **Role-Based Access**: Different interfaces for students and advisers
- **Real-time Updates**: Dynamic content updates without page refresh
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Database-Driven**: All data stored securely in MySQL database

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: TailwindCSS for styling
- **Icons**: Lucide Icons
- **Server**: Apache (XAMPP)

## Installation Instructions

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Modern web browser
- At least 100MB free disk space

### Step-by-Step Setup

1. **Install XAMPP**
   - Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Install and start Apache and MySQL services

2. **Download/Clone the Project**
   - Place the project folder in `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
   - Rename the folder to `thesis_management` for consistency

3. **Initialize the Database**
   - Open your web browser
   - Navigate to `http://localhost/thesis_management/init_database.php`
   - This will create the database and sample data automatically

4. **Access the System**
   - Go to `http://localhost/thesis_management/login.php`
   - Use the sample credentials provided below

## Sample Login Credentials

### Adviser Account:
- **Email**: `adviser@example.com`
- **Password**: `password123`
- **Role**: Adviser

### Student Account:
- **Email**: `student@example.com`
- **Password**: `password123`
- **Role**: Student

## Database Structure

The system uses the following main tables:

- **users**: Store user accounts (students and advisers)
- **theses**: Store thesis information and metadata
- **chapters**: Store individual thesis chapters
- **feedback**: Store adviser feedback on chapters
- **timeline**: Store thesis milestones and deadlines
- **notifications**: Store system notifications
- **file_uploads**: Store uploaded file information

## File Structure

```
thesis_management/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── auth.php             # Authentication functions
│   └── thesis_functions.php # Thesis management functions
├── uploads/                 # File upload directory (auto-created)
├── login.php               # Login/Registration page
├── studentDashboard.php    # Student dashboard
├── systemFunda.php         # Adviser dashboard
├── logout.php              # Logout handler
├── init_database.php       # Database initialization
└── README.md               # This file
```

## Configuration

### Database Configuration
Edit `config/database.php` to modify database connection settings:

```php
private $host = "localhost";
private $database_name = "thesis_management";
private $username = "root";
private $password = "";
```

### File Upload Settings
The system automatically creates an `uploads/` directory for file storage. Ensure your web server has write permissions to this directory.

## Usage Guide

### For Students:

1. **Login**: Use your student credentials to access the system
2. **Dashboard**: View your thesis overview and progress
3. **Create Thesis**: If no thesis exists, create one with title and abstract
4. **Manage Chapters**: Add, edit, and submit chapters for review
5. **Upload Documents**: Upload thesis documents (PDF, DOC, DOCX) for each chapter
6. **View Feedback**: Check adviser comments and suggestions
7. **Track Progress**: Monitor your completion percentage and timeline

### For Advisers:

1. **Login**: Use your adviser credentials to access the system
2. **Dashboard**: View all supervised theses and recent activity
3. **Manage Students**: Add new students or assign existing students to yourself
4. **Review Chapters**: Approve or reject student submissions
5. **Provide Feedback**: Add comments and suggestions for improvements
6. **Monitor Progress**: Track student progress across all theses
7. **Generate Reports**: View analytics and progress reports

## Security Features

- **Password Hashing**: All passwords are hashed using PHP's password_hash()
- **Session Management**: Secure session handling with proper timeout
- **SQL Injection Prevention**: All database queries use prepared statements
- **XSS Protection**: All user inputs are sanitized and escaped
- **Role-Based Access**: Users can only access appropriate functionality

## Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `config/database.php`
   - Make sure the database exists (run `init_database.php`)

2. **Permission Denied for File Uploads**
   - Ensure the `uploads/` directory has write permissions
   - On Windows: Right-click → Properties → Security → Edit permissions
   - On Mac/Linux: `chmod 755 uploads/`

3. **Page Not Found**
   - Ensure the project is in the correct XAMPP directory
   - Check that Apache is running
   - Verify the URL includes the correct folder name

4. **Session Issues**
   - Clear browser cookies and cache
   - Restart Apache in XAMPP
   - Check PHP session configuration

## Development Notes

### Adding New Features:

1. **Database Changes**: Add new tables/columns to `config/database.php`
2. **Business Logic**: Add functions to `includes/thesis_functions.php`
3. **UI Components**: Use TailwindCSS classes for consistent styling
4. **AJAX Requests**: Use fetch() API for dynamic content updates

### Code Standards:

- Follow PSR-12 coding standards for PHP
- Use semantic HTML5 elements
- Implement responsive design principles
- Add comments for complex business logic
- Use prepared statements for all database queries

## Support and Documentation

For additional support or questions:

1. Check the troubleshooting section above
2. Review the code comments for implementation details
3. Ensure all prerequisites are properly installed
4. Verify database connectivity and permissions

## License

This project is developed for educational purposes. Feel free to modify and extend according to your needs.

## Version History

- **v1.0**: Initial release with basic thesis management functionality
- **v1.1**: Added dynamic data loading and improved UI
- **v1.2**: Enhanced security and database optimization
- **v1.3**: Added adviser student management functionality

---

**Note**: This system is designed for local development with XAMPP. For production deployment, additional security measures and server configuration would be required. 