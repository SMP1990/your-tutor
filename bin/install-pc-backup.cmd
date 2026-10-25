@echo off
rem Double-click this file once (Step 7c-2). Backups go to F:\YourTutorBackups.
rem To use another drive, start it from a command window:  install-pc-backup.cmd E:\YourTutorBackups
echo.
echo Setting up the daily your-tutor backup copy on this PC...
echo.
if "%~1"=="" (
    powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-pc-backup.ps1"
) else (
    powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-pc-backup.ps1" -Destination "%~1"
)
echo.
echo ---------------------------------------------------------------
echo Read the lines above. The last one should start with "OK, newest backup".
echo Press any key to close this window.
pause >nul
