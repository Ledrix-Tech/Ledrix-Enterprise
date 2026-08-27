<?php

namespace App\Services\Tenant;

use App\Models\Central\SystemAnnouncement;
use App\Models\Central\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;

class TenantMaintenanceService
{
    public function columnReady(): bool
    {
        try {
            return Schema::connection('central')->hasTable('system_announcements')
                && Schema::connection('central')->hasColumn('system_announcements', 'blocks_crm');
        } catch (\Throwable) {
            return false;
        }
    }

    public function blockingAnnouncement(?Tenant $tenant = null): ?SystemAnnouncement
    {
        if (! $this->columnReady()) {
            return null;
        }

        $tenant ??= Tenant::query()->find(TenantContext::id());
        if (! $tenant) {
            return null;
        }

        return SystemAnnouncement::query()
            ->visible()
            ->where('blocks_crm', true)
            ->orderByDesc('id')
            ->get()
            ->first(fn (SystemAnnouncement $a) => $a->isVisibleToTenant($tenant));
    }

    public function isCrmBlocked(?Tenant $tenant = null): bool
    {
        return $this->blockingAnnouncement($tenant) !== null;
    }
}
