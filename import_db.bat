@echo off
echo ================================
echo Starting MySQL Server via XAMPP...
echo ================================

REM Start MySQL using XAMPP's control script
start "" "%~dp0xampp\mysql_start.bat"

REM Give MySQL some time to fully start (adjust if needed)
timeout /t 7 /nobreak >nul

echo ================================
echo Importing Inventory Database...
echo ================================

REM Set paths
set "mysql_path=%~dp0xampp\mysql\bin\mysql.exe"
set "sql_file=%~dp0xampp\htdocs\inventory-system\db\inventory_db.sql"

REM Run the SQL import
"%mysql_path%" -u root < "%sql_file%"

echo ================================
echo ✅ Done! Inventory database imported.
echo ================================
pause
exit
