<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:setup 
                            {domain : The domain name to setup (e.g., blueridge.test)}
                            {--name= : The name of the hospital/tenant (optional)}
                            {--database= : The database name (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup a tenant: add to hosts file, create virtual host, and configure for local development';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $domain = $this->argument('domain');
        $name = $this->option('name') ?? $this->generateNameFromDomain($domain);
        $database = $this->option('database') ?? $this->generateDatabaseName($domain);

        // Validate domain format
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i', $domain)) {
            $this->error('Invalid domain format. Use format like: blueridge.test');

            return Command::FAILURE;
        }

        // Check if tenant exists
        $tenant = Tenant::where('domain', $domain)->first();

        if (! $tenant) {
            $this->warn("Tenant with domain '{$domain}' not found in database.");
            $this->info('Creating tenant first...');

            // Create tenant
            $tenant = Tenant::create([
                'name' => $name,
                'domain' => $domain,
                'database' => $database,
            ]);

            // Create default roles
            $tenant->createDefaultRoles();

            $this->info('✅ Tenant created successfully!');
        }

        $this->info("Setting up local environment for: {$domain}");
        $this->newLine();

        // Step 1: Add to hosts file
        $this->info('📝 Step 1: Adding to hosts file...');
        $hostsResult = $this->addToHostsFile($domain);
        if ($hostsResult === true) {
            $this->info('   ✅ Added to hosts file');
        } elseif ($hostsResult === 'exists') {
            $this->warn('   ℹ️  Domain already exists in hosts file');
        } else {
            $this->error('   ❌ Failed to add to hosts file (requires admin access)');
            $this->newLine();
            $this->warn('   Manual step required:');
            $this->line('   1. Open Notepad as Administrator');
            $this->line('   2. Open: C:\\Windows\\System32\\drivers\\etc\\hosts');
            $this->line("   3. Add: 127.0.0.1      {$domain}    #laragon magic!");
            $this->newLine();
        }

        // Step 2: Create virtual host
        $this->info('📝 Step 2: Creating virtual host configuration...');
        if ($this->createVirtualHost($domain)) {
            $this->info('   ✅ Virtual host configuration created');
        } else {
            $this->error('   ❌ Failed to create virtual host configuration');
        }

        $this->newLine();
        $this->info('🎉 Setup complete!');
        $this->newLine();
        $this->warn('⚠️  IMPORTANT: Restart Apache in Laragon for changes to take effect!');
        $this->info('   Right-click Laragon icon -> Stop All -> Start All');
        $this->newLine();
        $this->info("📋 Then visit: http://{$domain}/");

        return Command::SUCCESS;
    }

    /**
     * Add domain to Windows hosts file.
     *
     * @return bool|string Returns true on success, 'exists' if already exists, false on failure
     */
    private function addToHostsFile(string $domain): bool|string
    {
        $hostsPath = 'C:\Windows\System32\drivers\etc\hosts';
        $entry = "127.0.0.1      {$domain}    #laragon magic!";

        // Check if already exists
        if (file_exists($hostsPath)) {
            $content = file_get_contents($hostsPath);
            if (strpos($content, $domain) !== false) {
                return 'exists';
            }
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
     * Create virtual host configuration for Laragon.
     */
    private function createVirtualHost(string $domain): bool
    {
        $sitesEnabledPath = 'C:\laragon\etc\apache2\sites-enabled';
        $configFile = "{$sitesEnabledPath}\\{$domain}.conf";

        // Check if already exists
        if (file_exists($configFile)) {
            $this->warn('   Virtual host config already exists');

            return true;
        }

        // Create directory if it doesn't exist
        if (! is_dir($sitesEnabledPath)) {
            if (! mkdir($sitesEnabledPath, 0755, true)) {
                $this->error('   Failed to create sites-enabled directory');

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
     * Generate a name from domain.
     */
    private function generateNameFromDomain(string $domain): string
    {
        // Remove .test or .local
        $name = str_replace(['.test', '.local', '.com', '.org'], '', $domain);
        // Convert to title case
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);

        return $name.' Hospital';
    }

    /**
     * Generate a database name from domain.
     */
    private function generateDatabaseName(string $domain): string
    {
        $name = str_replace('.', '_', $domain);
        $name = preg_replace('/[^a-z0-9_]/', '', strtolower($name));

        return $name;
    }
}
