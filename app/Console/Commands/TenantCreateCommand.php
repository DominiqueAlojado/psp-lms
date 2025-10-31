<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {name : The name of the hospital/tenant}
                            {domain : The domain name (e.g., hospital.example.com)}
                            {database? : The database name (defaults to slugified domain)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant (hospital) with domain and default roles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $database = $this->argument('database') ?? $this->generateDatabaseName($domain);

        // Validate domain format
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i', $domain)) {
            $this->error('Invalid domain format. Use format like: hospital.example.com');

            return Command::FAILURE;
        }

        // Validate database name format
        if (! preg_match('/^[a-z0-9_]+$/', $database)) {
            $this->error('Invalid database name. Must contain only lowercase letters, numbers, and underscores.');

            return Command::FAILURE;
        }

        // Check if domain already exists
        if (Tenant::where('domain', $domain)->exists()) {
            $this->error("Tenant with domain '{$domain}' already exists.");

            return Command::FAILURE;
        }

        // Check if database name already exists
        if (Tenant::where('database', $database)->exists()) {
            $this->error("Tenant with database '{$database}' already exists.");

            return Command::FAILURE;
        }

        // Create tenant
        $tenant = Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'database' => $database,
        ]);

        // Create default roles
        $tenant->createDefaultRoles();

        $this->info('✅ Tenant created successfully!');
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $tenant->id],
                ['Name', $tenant->name],
                ['Domain', $tenant->domain],
                ['Database', $tenant->database],
                ['Created', $tenant->created_at->format('Y-m-d H:i:s')],
            ]
        );

        $this->newLine();
        $this->info('📋 Next steps for Coolify:');
        $this->line("   1. Add domain '{$domain}' to your Coolify application");
        $this->line('   2. Coolify will automatically handle DNS and SSL');
        $this->line("   3. Visit https://{$domain} to access the tenant");

        return Command::SUCCESS;
    }

    /**
     * Generate a database name from domain.
     */
    private function generateDatabaseName(string $domain): string
    {
        // Convert domain to database-friendly name
        // e.g., "hospital.example.com" -> "hospital_example_com"
        $name = str_replace('.', '_', $domain);
        $name = preg_replace('/[^a-z0-9_]/', '', strtolower($name));

        return $name;
    }
}
