# Testing the Adviser Dashboard

This guide will help you test the new adviser dashboard functionality for managing students.

## Prerequisites

- XAMPP installed and running (Apache + MySQL)
- The thesis management system installed in your XAMPP htdocs directory
- Database initialized with `init_database.php`

## Test Data Setup

1. Import the test data SQL file to add test users:

```sql
-- Run this in phpMyAdmin or MySQL command line
SOURCE test_adviser_dashboard.sql;
```

Or navigate to:
```
http://localhost/thesis_management/test_adviser_dashboard.sql
```

This will create:
- A test adviser account
- Two unassigned students
- One student already assigned to the test adviser

## Test Credentials

### Test Adviser:
- **Email**: `test_adviser@example.com`
- **Password**: `password`

### Unassigned Students:
- **Email**: `unassigned1@example.com` or `unassigned2@example.com`
- **Password**: `password`

### Assigned Student:
- **Email**: `assigned1@example.com`
- **Password**: `password`

## Testing Procedure

### 1. Adviser Dashboard Access

1. Go to `http://localhost/thesis_management/login.php`
2. Login with the test adviser credentials
3. Verify you can see the adviser dashboard

### 2. View Existing Students

1. Click on the "Students" tab in the sidebar
2. Verify you can see the assigned student ("Assigned Student 1")
3. Check that the student's thesis information is displayed correctly

### 3. Add Existing Student

1. Click the "Add Student" button
2. In the modal, select one of the unassigned students from the dropdown
3. Optionally enter a thesis title and abstract
4. Click "Add Student"
5. Verify that a success message appears
6. Verify that the student now appears in your students list

### 4. Create New Student

1. Click the "Add Student" button
2. Scroll down to the "Or register a new student" section
3. Fill in the student details:
   - Full Name: "New Test Student"
   - Email: "new_student@example.com"
   - Student ID: "TEST123"
   - Program: "Test Program"
   - Password: (leave blank to generate random password)
4. Optionally enter a thesis title and abstract
5. Click "Add Student"
6. Verify that a success message appears with the generated password
7. Verify that the new student appears in your students list

### 5. Student Assignment Validation

1. Try to add a student that is already assigned to you
2. Verify that an error message appears
3. Try to add a student with an email that already exists
4. Verify that an error message appears

## Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `config/database.php`

2. **Form Submission Issues**
   - Check browser console for JavaScript errors
   - Verify that the API endpoint (`api/add_student_to_adviser.php`) is accessible

3. **Student Not Appearing in List**
   - Refresh the page
   - Check the database to ensure the student was added correctly

4. **Permission Issues**
   - Ensure you're logged in as an adviser
   - Check that the user role is set correctly in the database

## Reporting Issues

If you encounter any issues during testing, please document:

1. The specific steps that led to the issue
2. Any error messages displayed
3. Expected behavior vs. actual behavior
4. Browser and XAMPP version information 