@echo off
echo.
echo ========================================
echo   Setup All Tenant Domains
echo ========================================
echo.
echo This will add ALL tenant domains (*.psp-lms.test) to your hosts file.
echo You only need to run this ONCE!
echo.
echo Press Ctrl+C to cancel, or
pause

REM Run PowerShell script as Administrator
PowerShell -ExecutionPolicy Bypass -Command "Start-Process powershell -Verb RunAs -ArgumentList '-ExecutionPolicy','Bypass','-File','%~dp0add-all-tenants.ps1'"

echo.
echo Done! Now restart Apache in Laragon (Stop All -> Start All)
pause

