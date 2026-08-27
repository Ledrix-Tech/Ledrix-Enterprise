<?php

namespace App\Console\Commands;

use App\Services\Tenant\TenantBackupService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class TenantRestoreCommand extends Command
{
    protected $signature = 'tenants:restore
                            {exportId : Ready tenant data export ID}
                            {--dry-run : Count CRM rows without writing (default when --force is omitted)}
                            {--force : Write CRM rows into the primary database}';

    protected $description = 'Restore CRM CSV tables from a tenant export ZIP (dry-run by default; --force to write)';

    public function handle(TenantBackupService $backups): int
    {
        $exportId = (int) $this->argument('exportId');
        $force = (bool) $this->option('force');

        if ($force && $this->option('dry-run')) {
            $this->error('Use either --dry-run or --force, not both.');

            return self::FAILURE;
        }

        try {
            $result = $backups->restoreFromExport($exportId, [
                'force'      => $force,
                'dry_run'    => ! $force,
                'actor_type' => 'system',
                'actor_name' => 'artisan tenants:restore',
            ]);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $mode = $result['dry_run'] ? 'DRY-RUN' : 'WRITE';
        $this->info("[{$mode}] Export #{$result['export_id']} → tenant #{$result['tenant_id']}");
        $this->line('Total rows: '.$result['total_rows']);

        foreach ($result['tables'] as $table => $count) {
            $this->line("  {$table}: {$count}");
        }

        if ($result['dry_run']) {
            $this->comment('No rows written. Re-run with --force to apply.');
        } else {
            $this->info('CRM restore written to primary DB.');
        }

        return self::SUCCESS;
    }
}
