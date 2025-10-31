@echo off
REM Batch file to add domains to hosts file (runs PowerShell as Admin)
echo.
echo This script will add hospital domains to your hosts file.
echo You may be prompted for Administrator privileges.
echo.
pause

PowerShell -ExecutionPolicy Bypass -Command "Start-Process powershell -Verb RunAs -ArgumentList '-ExecutionPolicy','Bypass','-File','%~dp0add-domains-to-hosts.ps1'"

