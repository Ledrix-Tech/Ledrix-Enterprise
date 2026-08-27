<?php

namespace App\Console\Commands;

use App\Services\Tenant\ProcessTenantStorageAlertsService;
use Illuminate\Console\Command;

class ProcessTenantStorageAlertsCommand extends Command
{
    protected $signature = 'tenants:process-storage-alerts';

    protected $description = 'Email tenants when storage usage crosses 80% / 100% of their plan limit';

    public function handle(ProcessTenantStorageAlertsService $service): int
    {
        $stats = $service->run();

        $this->info('80% storage alerts sent: '.$stats['alerted_80']);
        $this->info('100% storage alerts sent: '.$stats['alerted_100']);
        $this->info('Alert flags cleared (<70%): '.$stats['cleared']);
        $this->info('Skipped (unlimited/no quota/errors): '.$stats['skipped']);

        return self::SUCCESS;
    }
}
