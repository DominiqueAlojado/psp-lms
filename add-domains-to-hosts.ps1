# PowerShell script to add hospital domains to Windows hosts file
# Run as Administrator: Right-click -> Run as Administrator

$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$laragonMarker = "#laragon magic!"

# All hospital domains from TenantSeeder
$domains = @(
    'citygeneral.test',
    'metropolitan.test',
    'riverside.test',
    'memorial.test',
    'stmarys.test',
    'university.test',
    'regional.test',
    'central.test',
    'westside.test',
    'eastside.test',
    'northshore.test',
    'southview.test',
    'parkview.test',
    'lakeside.test',
    'hillside.test',
    'sunset.test',
    'sunrise.test',
    'oakwood.test',
    'pineview.test',
    'greenwood.test',
    'blueridge.test',
    'mountainview.test'
)

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click the PowerShell window and select 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Or run this command:" -ForegroundColor Yellow
    Write-Host "Start-Process powershell -Verb RunAs -ArgumentList '-File','$PSScriptRoot\add-domains-to-hosts.ps1'" -ForegroundColor Cyan
    exit 1
}

# Backup hosts file
$backupPath = "$hostsPath.backup.$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item $hostsPath $backupPath -ErrorAction SilentlyContinue
Write-Host "Backup created: $backupPath" -ForegroundColor Green

# Read current hosts file
$hostsContent = Get-Content $hostsPath

# Get existing domains
$existingDomains = @()
foreach ($line in $hostsContent) {
    if ($line -match '^\s*127\.0\.0\.1\s+(\S+)') {
        $existingDomains += $matches[1]
    }
}

# Prepare entries to add
$entriesToAdd = @()
foreach ($domain in $domains) {
    if ($existingDomains -notcontains $domain) {
        $entriesToAdd += "127.0.0.1      $domain    $laragonMarker"
        Write-Host "Adding: $domain" -ForegroundColor Cyan
    } else {
        Write-Host "Skipping (already exists): $domain" -ForegroundColor Yellow
    }
}

# Add new entries
if ($entriesToAdd.Count -gt 0) {
    # Add a separator if needed
    $hasLaragonMarker = $hostsContent | Where-Object { $_ -match [regex]::Escape($laragonMarker) }
    
    if (-not $hasLaragonMarker) {
        Add-Content -Path $hostsPath -Value "`n# Laragon domains"
    }
    
    # Add all new entries
    foreach ($entry in $entriesToAdd) {
        Add-Content -Path $hostsPath -Value $entry
    }
    
    Write-Host "`nSuccessfully added $($entriesToAdd.Count) domain(s) to hosts file!" -ForegroundColor Green
} else {
    Write-Host "`nAll domains are already in the hosts file." -ForegroundColor Green
}

Write-Host "`nTo test, visit: http://citygeneral.test/" -ForegroundColor Cyan
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

