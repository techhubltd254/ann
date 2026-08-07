@echo off
title KICC Platform Installer
color 0A
setlocal enabledelayedexpansion

echo ╔═══════════════════════════════╗
echo ║  KICC Digital Economy Platform ║
echo ║  Desktop App Installer          ║
echo ╚═══════════════════════════════╝
echo.
set APPDIR=%USERPROFILE%\.kicc-platform
choice /C YN /M "Install KICC Platform to %APPDIR%"
if errorlevel 2 exit /b

mkdir "%APPDIR%" 2>nul
echo Copying files...
copy /Y "%~dp0server\kicc-engine.jar" "%APPDIR%" >nul
xcopy /E /Y "%~dp0web" "%APPDIR%\web\" >nul
xcopy /E /Y "%~dp0electron" "%APPDIR%\electron\" >nul

REM Create launcher
> "%APPDIR%\start-kicc.bat" echo @echo off
>> "%APPDIR%\start-kicc.bat" echo title KICC Platform
>> "%APPDIR%\start-kicc.bat" echo mkdir "%%~dp0data" 2^>nul
>> "%APPDIR%\start-kicc.bat" echo start /MIN javaw -jar "%%~dp0kicc-engine.jar" --server.port=8091 --kicc.web-dir="%%~dp0web" --spring.datasource.url="jdbc:h2:file:%%~dp0data/engine;CIPHER=AES;MODE=MySQL;AUTO_SERVER=TRUE"
>> "%APPDIR%\start-kicc.bat" echo echo Starting KICC Platform...
>> "%APPDIR%\start-kicc.bat" echo timeout /t 12 /nobreak ^>nul
>> "%APPDIR%\start-kicc.bat" echo cd /d "%%~dp0electron"
>> "%APPDIR%\start-kicc.bat" echo start "" electron main.js --no-sandbox
>> "%APPDIR%\start-kicc.bat" echo echo KICC Platform is running
>> "%APPDIR%\start-kicc.bat" echo echo Admin: admin@kicc.go.ke / Admin@2026

REM Create Desktop shortcut
powershell -Command "$s=(New-Object -ComObject WScript.Shell).CreateShortcut('%USERPROFILE%\Desktop\KICC-Platform.lnk'); $s.TargetPath='%APPDIR%\start-kicc.bat'; $s.WorkingDirectory='%APPDIR%'; $s.Description='KICC Digital Economy Platform'; $s.Save()" 2>nul

echo.
echo ╔═══════════════════════════════╗
echo ║  ✅ Installation complete!     ║
echo ║                               ║
echo ║  Desktop shortcut created:     ║
echo ║  KICC-Platform                ║
echo ║                               ║
echo ║  admin@kicc.go.ke             ║
echo ║  Admin@2026                   ║
echo ╚═══════════════════════════════╝
choice /C YN /M "Launch KICC now"
if errorlevel 2 exit /b
start "" "%APPDIR%\start-kicc.bat"
