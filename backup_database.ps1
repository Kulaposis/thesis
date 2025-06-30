# PowerShell script to backup thesis_management database
Write-Host "Creating backup of thesis_management database..." -ForegroundColor Green
Write-Host ""

# Set the current date and time for the backup filename
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

# Create backup directory if it doesn't exist
$backupDir = "database_backups"
if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
}

# Set backup filename
$backupFile = "$backupDir\thesis_management_backup_$timestamp.sql"

Write-Host "Backup file: $backupFile" -ForegroundColor Yellow
Write-Host ""

try {
    # Check if mysqldump exists
    $mysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"
    if (!(Test-Path $mysqldumpPath)) {
        throw "mysqldump.exe not found at $mysqldumpPath"
    }

    # Run mysqldump to create backup
    Write-Host "Running backup..." -ForegroundColor Blue
    & $mysqldumpPath -u root --databases thesis_management | Out-File -FilePath $backupFile -Encoding UTF8

    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "✓ Database backup completed successfully!" -ForegroundColor Green
        Write-Host "✓ Backup saved to: $backupFile" -ForegroundColor Green
        Write-Host ""
        Write-Host "Backup contains:" -ForegroundColor Cyan
        Write-Host "- All tables structure" -ForegroundColor White
        Write-Host "- All data" -ForegroundColor White
        Write-Host "- Users, theses, chapters, feedback, etc." -ForegroundColor White
        Write-Host ""
        
        # Show file size
        $fileSize = (Get-Item $backupFile).Length
        $fileSizeKB = [math]::Round($fileSize / 1KB, 2)
        Write-Host "File size: $fileSizeKB KB" -ForegroundColor Gray
    } else {
        throw "mysqldump returned error code: $LASTEXITCODE"
    }
} catch {
    Write-Host ""
    Write-Host "✗ Backup failed!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please check if:" -ForegroundColor Yellow
    Write-Host "  - XAMPP MySQL is running" -ForegroundColor White
    Write-Host "  - Database 'thesis_management' exists" -ForegroundColor White
    Write-Host "  - You have the correct permissions" -ForegroundColor White
    Write-Host ""
}

Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 