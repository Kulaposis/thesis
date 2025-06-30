# Database Backup and Restoration Guide

## 📦 Backup Your Database

### Method 1: Using Provided Scripts (Recommended)

#### Option A: PowerShell Script
```bash
powershell -ExecutionPolicy Bypass -File .\backup_database.ps1
```

#### Option B: Batch File
```bash
.\backup_database.bat
```

Both scripts will:
- Create a `database_backups` folder
- Generate a timestamped backup file
- Include all tables and data
- Show backup status and file size

### Method 2: Manual phpMyAdmin Backup

1. Open http://localhost/phpmyadmin
2. Select `thesis_management` database
3. Click "Export" tab
4. Choose "Quick" export method
5. Select "SQL" format
6. Click "Go" to download

### Method 3: Command Line (Manual)

```bash
cd C:\xampp\mysql\bin
mysqldump -u root -p --databases thesis_management > backup.sql
```

## 🔄 Restore Your Database

### Method 1: phpMyAdmin Restore

1. Open http://localhost/phpmyadmin
2. Create new database or select existing one
3. Click "Import" tab
4. Choose your `.sql` backup file
5. Click "Go"

### Method 2: Command Line Restore

```bash
cd C:\xampp\mysql\bin
mysql -u root -p < backup.sql
```

### Method 3: Using MySQL Workbench

1. Open MySQL Workbench
2. Connect to your local server
3. Go to Server → Data Import
4. Select "Import from Self-Contained File"
5. Choose your backup file
6. Click "Start Import"

## 📁 Backup Files Location

All backups are stored in: `database_backups/`

File naming convention: `thesis_management_backup_YYYYMMDD_HHMMSS.sql`

## 🗃️ What's Included in Backups

Your backup contains:
- **users** - Student and adviser accounts
- **theses** - Thesis information and metadata
- **chapters** - Chapter content and status
- **feedback** - Adviser feedback and comments
- **timeline** - Project milestones and deadlines
- **notifications** - System notifications
- **file_uploads** - File upload records
- **document_highlights** - Document review highlights
- **document_comments** - Document review comments

## 🔧 Database Configuration

- **Host**: localhost
- **Database**: thesis_management
- **Username**: root
- **Password**: (empty)
- **Port**: 3306 (default)

## 📋 Backup Schedule Recommendations

### For Development:
- **Daily**: Before major changes
- **Weekly**: Regular development backup
- **Before Updates**: Always backup before system updates

### For Production:
- **Daily**: Automated daily backups
- **Weekly**: Full system backup
- **Monthly**: Archive backup to external storage

## 🚨 Emergency Recovery

If you lose your database:

1. Stop XAMPP MySQL service
2. Navigate to your backup folder
3. Find the most recent backup file
4. Start XAMPP MySQL service
5. Use phpMyAdmin or command line to restore
6. Verify all data is intact

## 💡 Pro Tips

1. **Test Your Backups**: Regularly test restoration process
2. **Multiple Locations**: Store backups in multiple locations
3. **Version Control**: Keep multiple backup versions
4. **Automate**: Set up scheduled backups for production
5. **Compress**: For large databases, compress backup files

## 🔍 Troubleshooting

### Common Issues:

#### "Access Denied" Error
- Ensure MySQL is running in XAMPP
- Check username/password in database config
- Verify database permissions

#### "Database Not Found" Error
- Ensure database name is correct: `thesis_management`
- Check if database exists in phpMyAdmin
- Run database initialization if needed

#### "File Not Found" Error
- Verify mysqldump.exe exists in `C:\xampp\mysql\bin\`
- Check XAMPP installation path
- Ensure you're running from correct directory

#### Large File Issues
- For large databases, use command line instead of phpMyAdmin
- Increase PHP upload limits for phpMyAdmin imports
- Use compression for large backup files

---

**Last Updated**: December 30, 2025
**Database Version**: thesis_management v1.0
**XAMPP Compatibility**: All versions 