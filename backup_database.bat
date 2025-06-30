@echo off
echo Creating backup of thesis_management database...
echo.

REM Set the current date and time for the backup filename
set timestamp=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set timestamp=%timestamp: =0%

REM Create backup directory if it doesn't exist
if not exist "database_backups" mkdir database_backups

REM Set backup filename
set backup_file=database_backups\thesis_management_backup_%timestamp%.sql

echo Backup file: %backup_file%
echo.

REM Run mysqldump to create backup
C:\xampp\mysql\bin\mysqldump.exe -u root -p --databases thesis_management > "%backup_file%"

if %errorlevel% equ 0 (
    echo.
    echo ✓ Database backup completed successfully!
    echo ✓ Backup saved to: %backup_file%
    echo.
    echo Backup contains:
    echo - All tables structure
    echo - All data
    echo - Users, theses, chapters, feedback, etc.
    echo.
) else (
    echo.
    echo ✗ Backup failed! Please check if:
    echo   - XAMPP MySQL is running
    echo   - Database exists
    echo   - You have the correct permissions
    echo.
)

pause 