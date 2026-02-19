# Backup Script for ProFarm Project
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "$HOME\Desktop\ProFarm_Backup_$timestamp"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

Write-Host "Creating Backup at $backupDir..." -ForegroundColor Green

# 1. Backup Frontend
Write-Host "Backing up Frontend..."
Compress-Archive -Path "C:\Farm2.0\*" -DestinationPath "$backupDir\frontend.zip" -Force

# 2. Backup Backend
Write-Host "Backing up Backend..."
Compress-Archive -Path "C:\Farm2.0_PHP\*" -DestinationPath "$backupDir\backend.zip" -Force

# 3. Request Database Backup (via PHP script)
Write-Host "Attempting Database Backup via PHP..."
try {
    # Try to run the php script locally if PHP is in path
    php "C:\Farm2.0_PHP\api\backup_db_util.php"
    
    # Check for created .sql files in the api directory and move them
    $sqlFiles = Get-ChildItem "C:\Farm2.0_PHP\api\backup_db_*.sql"
    if ($sqlFiles) {
        Move-Item $sqlFiles.FullName $backupDir
        Write-Host "Database backup moved to backup folder." -ForegroundColor Green
    } else {
        Write-Warning "No database backup file found. Please run http://localhost/api/backup_db_util.php manually and save the output."
    }
} catch {
    Write-Warning "Could not run PHP command. Please manually export the database or access /api/backup_db_util.php via browser."
}

Write-Host "Backup Complete! Files located in $backupDir" -ForegroundColor Cyan
Invoke-Item $backupDir
