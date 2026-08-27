<?php

namespace App\Console\Commands;

use App\Services\Tenant\TenantBackupService;
use Illuminate\Console\Command;
use Throwable;

class TenantBackupCommand extends Command
{
    protected $signature = 'tenants:backup {tenantId : Tenant ID to back up}';

    protected $description = 'Create a synchronous CRM backup ZIP for a tenant (export with meta.purpose=backup)';

    public function handle(TenantBackupService $backups): int
    {
        $tenantId = (int) $this->argument('tenantId');

        try {
            $export = $backups->backupNow($tenantId, [
                'type' => 'system',
                'name' => 'artisan tenants:backup',
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup ready: export #{$export->id}");
        $this->line("Path: {$export->file_path}");
        $this->line('Size: '.number_format((int) $export->file_size).' bytes');

        return self::SUCCESS;
    }
}
