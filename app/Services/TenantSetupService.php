<?php

namespace App\Services;

class TenantSetupService
{
    /**
     * Setup local development environment for a tenant.
     *
     * @return array{hosts: bool|string, virtual_host: bool, apache_restart: bool|string, message: string}
     */
    public function setupLocalDevelopment(string $domain, bool $restartApache = true): array
    {
        $results = [
            'hosts' => false,
            'virtual_host' => false,
            'apache_restart' => false,
            'message' => '',
        ];

        // Step 1: Add to hosts file
        $hostsResult = $this->addToHostsFile($domain);
        $results['hosts'] = $hostsResult;

        // Step 2: Create virtual host (or check if covered by wildcard)
        // NOTE: Wildcard detection is only for local development.
        // In production, each tenant gets its own domain via Coolify.
        $wildcardCovered = $this->isCoveredByWildcardVirtualHost($domain);
        if ($wildcardCovered) {
            $results['virtual_host'] = true; // Covered by wildcard (local dev only)
            $virtualHostCreated = true;
        } else {
            $virtualHostCreated = $this->createVirtualHost($domain);
            $results['virtual_host'] = $virtualHostCreated;
        }

        // Step 3: Restart Apache if virtual host exists (created or already existed or covered by wildcard)
        if ($restartApache && $virtualHostCreated) {
            $results['apache_restart'] = $this->restartApache();
        }

        // Generate message
        $messages = [];
        if ($results['hosts'] === true) {
            $messages[] = '✅ Added to hosts file';
        } elseif ($results['hosts'] === 'exists') {
            $messages[] = 'ℹ️  Already in hosts file';
        } else {
            $messages[] = '⚠️  Could not add to hosts file (requires admin access)';
        }

        if ($results['virtual_host']) {
            $messages[] = '✅ Virtual host configuration created';
        } else {
            $messages[] = '❌ Failed to create virtual host configuration';
        }

        if ($results['apache_restart'] === true) {
            $messages[] = '✅ Apache restarted';
        } elseif ($results['apache_restart'] === 'skipped') {
            $messages[] = 'ℹ️  Apache restart skipped';
        } elseif ($results['apache_restart'] === false) {
            $messages[] = '⚠️  Apache restart failed (may need manual restart)';
        }

        $results['message'] = implode(' | ', $messages);

        return $results;
    }

    /**
     * Check local setup status for a tenant.
     *
     * @return array{hosts_configured: bool, virtual_host_configured: bool}
     */
    public function checkLocalSetupStatus(string $domain): array
    {
        // Check if domain is covered by a wildcard virtual host
        $wildcardCovered = $this->isCoveredByWildcardVirtualHost($domain);

        return [
            'hosts_configured' => $this->isInHostsFile($domain),
            'virtual_host_configured' => $wildcardCovered || $this->virtualHostExists($domain),
        ];
    }

    /**
     * Add domain to Windows hosts file.
     *
     * @return bool|string Returns true on success, 'exists' if already exists, false on failure
     */
    private function addToHostsFile(string $domain): bool|string
    {
        $hostsPath = 'C:\Windows\System32\drivers\etc\hosts';

        // Check if already exists
        if ($this->isInHostsFile($domain)) {
            return 'exists';
        }

        // Try to add via PowerShell with admin elevation
        $powershellScript = <<<POWERSHELL
\$hostsPath = "C:\\Windows\\System32\\drivers\\etc\\hosts"
\$entry = "127.0.0.1      {$domain}    #laragon magic!"
\$content = Get-Content \$hostsPath -ErrorAction SilentlyContinue
if (\$content -notmatch [regex]::Escape("{$domain}")) {
    Add-Content -Path \$hostsPath -Value \$entry -ErrorAction Stop
    exit 0
} else {
    exit 2
}
POWERSHELL;

        $tempScript = tempnam(sys_get_temp_dir(), 'hosts_').'.ps1';
        file_put_contents($tempScript, $powershellScript);

        // Try with elevation
        $command = 'powershell.exe -ExecutionPolicy Bypass -Command "Start-Process powershell -ArgumentList \'-ExecutionPolicy\',\'Bypass\',\'-File\',\''
            .str_replace('\\', '\\\\', $tempScript).'\' -Verb RunAs -Wait -NoNewWindow"';

        exec($command, $output, $exitCode);

        @unlink($tempScript);

        if ($exitCode === 0) {
            return true;
        } elseif ($exitCode === 2) {
            return 'exists';
        }

        return false;
    }

    /**
     * Check if domain exists in hosts file.
     */
    private function isInHostsFile(string $domain): bool
    {
        $hostsPath = 'C:\Windows\System32\drivers\etc\hosts';

        if (! file_exists($hostsPath)) {
            return false;
        }

        $content = file_get_contents($hostsPath);

        return strpos($content, $domain) !== false;
    }

    /**
     * Create virtual host configuration for Laragon.
     */
    private function createVirtualHost(string $domain): bool
    {
        $sitesEnabledPath = 'C:\laragon\etc\apache2\sites-enabled';
        $configFile = "{$sitesEnabledPath}\\{$domain}.conf";

        // Check if already exists
        if (file_exists($configFile)) {
            return true;
        }

        // Create directory if it doesn't exist
        if (! is_dir($sitesEnabledPath)) {
            if (! mkdir($sitesEnabledPath, 0755, true)) {
                return false;
            }
        }

        $projectPath = base_path();
        $publicPath = str_replace('\\', '/', $projectPath).'/public';

        $config = <<<APACHE
<VirtualHost *:80> 
    DocumentRoot "{$publicPath}"
    ServerName {$domain}
    ServerAlias *.{$domain}
    <Directory "{$publicPath}">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# If you want to use SSL, enable it by going to Menu > Apache > SSL > Enabled

APACHE;

        return file_put_contents($configFile, $config) !== false;
    }

    /**
     * Check if virtual host configuration exists.
     */
    private function virtualHostExists(string $domain): bool
    {
        $sitesEnabledPath = 'C:\laragon\etc\apache2\sites-enabled';
        $configFile = "{$sitesEnabledPath}\\{$domain}.conf";

        return file_exists($configFile);
    }

    /**
     * Check if domain is covered by a wildcard virtual host (e.g., *.lms.test).
     *
     * NOTE: This is only applicable for local development. In production (Coolify),
     * each tenant gets its own individual domain with proper DNS and SSL.
     */
    private function isCoveredByWildcardVirtualHost(string $domain): bool
    {
        // Extract the base domain pattern (e.g., "lms.test" from "central.lms.test")
        $parts = explode('.', $domain);

        if (count($parts) < 2) {
            return false;
        }

        $sitesEnabledPath = 'C:\laragon\etc\apache2\sites-enabled';
        $httpdConfPath = 'C:\laragon\bin\apache';

        // Check sites-enabled directory for wildcard virtual hosts
        if (is_dir($sitesEnabledPath)) {
            $files = glob("{$sitesEnabledPath}\\*.conf");
            foreach ($files as $file) {
                $content = file_get_contents($file);
                // Check if file contains ServerAlias with wildcard pattern matching our domain
                if (preg_match('/ServerAlias\s+([^\s\n]+)/i', $content, $matches)) {
                    $serverAlias = trim($matches[1]);
                    // Check if it's a wildcard that would match our domain
                    // e.g., *.lms.test matches central.lms.test
                    if (preg_match('/^\*\.(.+)$/', $serverAlias, $wildcardMatches)) {
                        $wildcardBase = $wildcardMatches[1];
                        // Check if our domain ends with the wildcard base
                        if (str_ends_with($domain, '.'.$wildcardBase) || $domain === $wildcardBase) {
                            return true;
                        }
                    }
                }
            }
        }

        // Also check main Apache httpd-vhosts.conf for wildcard entries
        // This is where Laragon often stores virtual host configurations
        if (is_dir($httpdConfPath)) {
            $dirs = scandir($httpdConfPath);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                $httpdVhostsFile = "{$httpdConfPath}\\{$dir}\\conf\\extra\\httpd-vhosts.conf";
                if (file_exists($httpdVhostsFile)) {
                    $content = file_get_contents($httpdVhostsFile);
                    // Check for wildcard ServerAlias
                    if (preg_match('/ServerAlias\s+([^\s\n]+)/i', $content, $matches)) {
                        $serverAlias = trim($matches[1]);
                        if (preg_match('/^\*\.(.+)$/', $serverAlias, $wildcardMatches)) {
                            $wildcardBase = $wildcardMatches[1];
                            if (str_ends_with($domain, '.'.$wildcardBase) || $domain === $wildcardBase) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Restart Apache gracefully using multiple methods.
     *
     * @return bool|string Returns true on success, 'skipped' if not needed, false on failure
     */
    private function restartApache(): bool|string
    {
        // Check if Apache is running
        if (! $this->isApacheRunning()) {
            return 'skipped';
        }

        // Method 1: Try using Laragon's API/CLI if available
        $laragonExe = 'C:\laragon\laragon.exe';
        if (file_exists($laragonExe)) {
            // Try to restart Apache via Laragon CLI
            $powershellScript = <<<POWERSHELL
\$laragonExe = {$this->escapePowerShellPath($laragonExe)}
\$process = Start-Process -FilePath \$laragonExe -ArgumentList "apache","restart" -Wait -NoNewWindow -PassThru -ErrorAction SilentlyContinue
if (\$process.ExitCode -eq 0 -or \$process.ExitCode -eq \$null) {
    Start-Sleep -Seconds 2
    exit 0
} else {
    exit 1
}
POWERSHELL;

            $tempScript = tempnam(sys_get_temp_dir(), 'apache_restart_laragon_').'.ps1';
            file_put_contents($tempScript, $powershellScript);

            exec('powershell.exe -ExecutionPolicy Bypass -File '.escapeshellarg($tempScript), $output, $exitCode);

            @unlink($tempScript);

            if ($exitCode === 0) {
                sleep(2);

                return true;
            }
        }

        // Method 2: Try to restart Apache Windows service
        $powershellScript = <<<'POWERSHELL'
$service = Get-Service -Name "*Apache*" -ErrorAction SilentlyContinue | Select-Object -First 1
if ($service) {
    Restart-Service -Name $service.Name -Force -ErrorAction Stop
    Start-Sleep -Seconds 2
    exit 0
} else {
    exit 1
}
POWERSHELL;

        $tempScript = tempnam(sys_get_temp_dir(), 'apache_restart_service_').'.ps1';
        file_put_contents($tempScript, $powershellScript);

        exec('powershell.exe -ExecutionPolicy Bypass -Command '.escapeshellarg($powershellScript), $output, $exitCode);

        @unlink($tempScript);

        if ($exitCode === 0) {
            sleep(2);

            return true;
        }

        // Method 3: Try using httpd.exe -k restart
        $apacheBasePath = 'C:\laragon\bin\apache';
        $httpdExe = null;

        // Look for httpd.exe in common Apache directories
        if (is_dir($apacheBasePath)) {
            $dirs = scandir($apacheBasePath);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                $potentialPath = $apacheBasePath.'\\'.$dir.'\bin\httpd.exe';
                if (file_exists($potentialPath)) {
                    $httpdExe = $potentialPath;
                    break;
                }
            }
        }

        if (! $httpdExe || ! file_exists($httpdExe)) {
            // Fallback to default path
            $httpdExe = 'C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe';
            if (! file_exists($httpdExe)) {
                return false;
            }
        }

        // Use httpd.exe -k restart to gracefully restart Apache
        $powershellScript = <<<POWERSHELL
\$httpdExe = {$this->escapePowerShellPath($httpdExe)}
try {
    \$process = Start-Process -FilePath \$httpdExe -ArgumentList "-k","restart" -Wait -NoNewWindow -PassThru -ErrorAction Stop
    Start-Sleep -Seconds 2
    exit 0
} catch {
    exit 1
}
POWERSHELL;

        $tempScript = tempnam(sys_get_temp_dir(), 'apache_restart_httpd_').'.ps1';
        file_put_contents($tempScript, $powershellScript);

        exec('powershell.exe -ExecutionPolicy Bypass -File '.escapeshellarg($tempScript), $output, $exitCode);

        @unlink($tempScript);

        if ($exitCode === 0) {
            sleep(2);

            return true;
        }

        // Last resort: Try direct exec
        $result = exec('"'.$httpdExe.'" -k restart 2>&1', $output, $exitCode);

        if ($exitCode === 0 || $exitCode === 1) {
            // Exit code 1 might still be success for Apache restart
            sleep(2);

            return true;
        }

        return false;
    }

    /**
     * Escape a path for use in PowerShell.
     */
    private function escapePowerShellPath(string $path): string
    {
        return '"'.str_replace('\\', '\\\\', $path).'"';
    }

    /**
     * Check if Apache is currently running.
     */
    private function isApacheRunning(): bool
    {
        $command = 'powershell.exe -Command "Get-Process -Name httpd -ErrorAction SilentlyContinue | Measure-Object | Select-Object -ExpandProperty Count"';
        exec($command, $output, $exitCode);

        if (! empty($output) && is_numeric($output[0])) {
            return (int) $output[0] > 0;
        }

        return false;
    }
}
