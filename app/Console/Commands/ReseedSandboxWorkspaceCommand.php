<?php

namespace App\Console\Commands;

use App\Services\Sandbox\EnsureSandboxTenantService;
use App\Services\Sandbox\SeedDemoWorkspaceService;
use Illuminate\Console\Command;

class ReseedSandboxWorkspaceCommand extends Command
{
    protected $signature = 'sandbox:reseed {--force : Recreate sample CRM rows even if they already exist}';

    protected $description = 'Ensure the shared sandbox tenant exists and refresh its demo CRM data';

    public function handle(
        EnsureSandboxTenantService $ensure,
        SeedDemoWorkspaceService $seed,
    ): int {
        $this->call('migrate', [
            '--database' => (string) config('sandbox.connection', 'demos_db'),
            '--path'     => 'database/migrations/demos',
            '--force'    => true,
        ]);

        $tenant = $ensure->ensure();
        $seed->seed($tenant, (bool) $this->option('force'));

        $this->info("Sandbox tenant #{$tenant->id} ({$tenant->slug}) is ready on ".config('sandbox.database').'. Trial was not used.');

        return self::SUCCESS;
    }
}
