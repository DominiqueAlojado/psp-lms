# PowerShell script to configure citygeneral.test virtual host in Laragon
# Run as Administrator

$vhostsFile = "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\extra\httpd-vhosts.conf"
$documentRoot = "C:/laragon/www/psp-lms/public"
$domain = "citygeneral.test"

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click the PowerShell window and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

# Check if file exists
if (-not (Test-Path $vhostsFile)) {
    Write-Host "ERROR: Virtual hosts file not found at: $vhostsFile" -ForegroundColor Red
    Write-Host "Please check your Laragon installation path." -ForegroundColor Yellow
    exit 1
}

# Read current content
$content = Get-Content $vhostsFile -Raw

# Check if virtual host already exists
if ($content -match [regex]::Escape($domain)) {
    Write-Host "Virtual host for $domain already exists in the file." -ForegroundColor Yellow
    Write-Host "Current virtual host configuration:" -ForegroundColor Cyan
    $content -split "`n" | Select-String -Pattern $domain -Context 0,10 | ForEach-Object { Write-Host $_ }
} else {
    # Create virtual host configuration
    $vhostConfig = @"

# Virtual Host for $domain
<VirtualHost *:80>
    DocumentRoot "$documentRoot"
    ServerName $domain
    ServerAlias www.$domain
    
    <Directory "$documentRoot">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/$domain-error.log"
    CustomLog "logs/$domain-access.log" common
</VirtualHost>

"@

    # Add to end of file (before closing tags if any, or just append)
    $vhostConfig | Add-Content -Path $vhostsFile
    
    Write-Host "Virtual host configuration added for $domain" -ForegroundColor Green
}

Write-Host "`nConfiguration complete!" -ForegroundColor Green
Write-Host "Please restart Apache in Laragon for changes to take effect." -ForegroundColor Cyan
Write-Host "Right-click Laragon tray icon -> Stop All -> Start All" -ForegroundColor Yellow
Write-Host "`nThen visit: http://$domain/" -ForegroundColor Cyan

