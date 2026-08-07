@echo off
title KICC Data Sync Tool
echo ============================================
echo    KICC Data Sync Tool
echo ============================================
echo.

:: Check internet first
echo [CHECK] Testing connection to TiDB...
ping -n 1 -w 5000 gateway01.eu-central-1.prod.aws.tidbcloud.com >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [FAIL] Cannot reach TiDB. Check internet connection.
    echo        The app will work offline with local data.
    pause
    exit /b 1
)

echo [OK] TiDB is reachable!
echo.
echo Select sync direction:
echo  1. Pull from TiDB ^> Local (download latest data)
echo  2. Push Local ^> TiDB (upload changes)
echo  3. Both directions
echo.
set /p choice="Choice [1-3]: "

cd /d "%~dp0\.."

if "%choice%"=="1" (
    php artisan sync:from-tidb --force
) else if "%choice%"=="2" (
    php artisan sync:to-tidb --force
) else if "%choice%"=="3" (
    echo [SYNC] Pulling from TiDB...
    php artisan sync:from-tidb --force
    echo.
    echo [SYNC] Pushing to TiDB...
    php artisan sync:to-tidb --force
) else (
    echo Invalid choice
)

echo.
pause