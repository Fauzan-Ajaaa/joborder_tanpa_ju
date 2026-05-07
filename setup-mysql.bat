@echo off
echo ========================================
echo Setup MySQL Database
echo ========================================
echo.

echo Step 1: Creating database...
echo Please create database 'windsurf_project_4' in phpMyAdmin first!
echo URL: http://localhost/phpmyadmin
echo.
pause

echo.
echo Step 2: Updating .env file...
echo Please update your .env file with:
echo.
echo DB_CONNECTION=mysql
echo DB_HOST=127.0.0.1
echo DB_PORT=3306
echo DB_DATABASE=windsurf_project_4
echo DB_USERNAME=root
echo DB_PASSWORD=
echo.
pause

echo.
echo Step 3: Clearing cache...
php artisan config:clear
php artisan cache:clear

echo.
echo Step 4: Running migrations and seeders...
php artisan migrate:fresh --seed

echo.
echo Step 5: Testing database connection...
php test-db.php

echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Check phpMyAdmin to verify data
echo 2. Restart server: php artisan serve
echo 3. Login to admin panel
echo.
pause
