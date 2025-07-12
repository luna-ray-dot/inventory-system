@echo off
echo ================================
echo Starting XAMPP Services...
echo ================================

REM Start Apache and MySQL
start "" "%~dp0xampp\xampp-control.exe"

REM Wait for services to start
timeout /t 7 /nobreak >nul

echo ================================
echo Launching Inventory System in browser...
echo ================================

REM Open the app in the default browser
start http://localhost/inventory-system/login.php

echo ================================
echo ✅ Inventory System is ready!
echo ================================
pause
exit
