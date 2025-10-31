# PowerShell script to add ALL tenant domains to Windows hosts file
# Run as Administrator - This adds all *.psp-lms.test subdomains at once

$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$laragonMarker = "#laragon magic!"

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Right-click PowerShell and select 'Run as Administrator', then run:" -ForegroundColor Yellow
    Write-Host "  .\add-all-tenants.ps1" -ForegroundColor Cyan
    exit 1
}

# List of all tenant domains
# These match the domains in TenantSeeder
$domains = @(
    "psp-lms.test",
    "citygeneral.psp-lms.test",
    "metropolitan.psp-lms.test",
    "riverside.psp-lms.test",
    "memorial.psp-lms.test",
    "stmarys.psp-lms.test",
    "university.psp-lms.test",
    "regional.psp-lms.test",
    "central.psp-lms.test",
    "westside.psp-lms.test",
    "eastside.psp-lms.test",
    "northshore.psp-lms.test",
    "southview.psp-lms.test",
    "parkview.psp-lms.test",
    "lakeside.psp-lms.test",
    "hillside.psp-lms.test",
    "sunset.psp-lms.test",
    "sunrise.psp-lms.test",
    "oakwood.psp-lms.test",
    "pineview.psp-lms.test",
    "greenwood.psp-lms.test",
    "blueridge.psp-lms.test",
    "mountainview.psp-lms.test",
    "world-citi.psp-lms.test"
)

# Read current hosts file
$hostsContent = Get-Content $hostsPath -ErrorAction Stop
$addedCount = 0
$existingCount = 0

Write-Host ""
Write-Host "Adding tenant domains to hosts file..." -ForegroundColor Cyan
Write-Host ""

foreach ($domain in $domains) {
    $entry = "127.0.0.1      $domain    $laragonMarker"
    
    # Check if domain already exists in hosts file
    $domainExists = $false
    foreach ($line in $hostsContent) {
        if ($line -match [regex]::Escape($domain)) {
            $domainExists = $true
            break
        }
    }
    
    if ($domainExists) {
        Write-Host "  [SKIP] $domain (already exists)" -ForegroundColor Gray
        $existingCount++
    } else {
        # Add domain entry
        Add-Content -Path $hostsPath -Value $entry -ErrorAction Stop
        # Re-read hosts content to include new entry
        $hostsContent = Get-Content $hostsPath -ErrorAction Stop
        Write-Host "  [ADDED] $domain" -ForegroundColor Green
        $addedCount++
    }
}

Write-Host ""
Write-Host "Summary:" -ForegroundColor Cyan
Write-Host "  Added: $addedCount domains" -ForegroundColor Green
Write-Host "  Already existed: $existingCount domains" -ForegroundColor Gray
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Restart Apache in Laragon (Right-click icon -> Stop All -> Start All)" -ForegroundColor White
Write-Host "  2. Visit any tenant: http://domain.psp-lms.test/" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

