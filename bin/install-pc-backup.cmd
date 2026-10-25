@echo off
rem Double-click this file once (Step 7c-2). It runs install-pc-backup.ps1 next to it and keeps the window open
rem so you can read the result.
echo.
echo Setting up the daily your-tutor backup copy on this PC...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-pc-backup.ps1"
echo.
echo ---------------------------------------------------------------
echo Read the lines above. The last one should start with "OK, newest backup".
echo Press any key to close this window.
pause >nul
