<?php

namespace App\Services\Tenant;

use App\Models\AccountKey;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Central\Tenant;
use App\Models\Central\TenantUsageSnapshot;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Order;
use App\Models\PaymentLink;
use App\Models\Project;
use App\Models\Seller;
use Illuminate\Support\Facades\Schema;

class TenantUsageService
{
    public function __construct(
        private TenantStorageMeter $storageMeter,
        private HistoricalImportLimitService $importLimits,
    ) {}

    /** @var array<string, string> Plan limit key → snapshot column */
    public const LIMIT_USAGE_MAP = [
        'max_brands'          => 'total_brands',
        'max_sellers'         => 'total_sellers',
        'max_admins'          => 'total_admins',
        'max_clients'         => 'total_clients',
        'max_orders'          => 'total_orders',
        'max_payment_links'   => 'total_payment_links',
        'max_account_keys'    => 'total_account_keys',
        'max_projects'        => 'total_projects',
        'max_leads_per_month' => 'leads_this_month',
        'max_storage_mb'      => 'storage_used_mb',
        'import_max_uploads_per_month' => 'imports_this_month',
    ];

    public function countForLimit(string $limitKey, int $tenantId): int
    {
        return match ($limitKey) {
            'max_brands' => Brand::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'max_sellers' => Seller::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'max_admins' => Admin::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'max_clients' => Client::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'max_orders' => Order::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'max_payment_links' => PaymentLink::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'max_account_keys' => AccountKey::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('brand_id')
                ->count(),
            'max_projects' => Project::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'max_leads_per_month' => Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'max_storage_mb' => $this->storageMeter->usedMb($tenantId),
            'import_max_uploads_per_month' => $this->safeImportUploads($tenantId),
            default => 0,
        };
    }

    public function hasHitLimit(Tenant $tenant, string $limitKey): bool
    {
        if ($tenant->isUnlimited($limitKey)) {
            return false;
        }

        $limit = $tenant->limit($limitKey);

        if ($limit <= 0) {
            return true;
        }

        return $this->countForLimit($limitKey, (int) $tenant->id) >= $limit;
    }

    public function remaining(Tenant $tenant, string $limitKey): int
    {
        if ($tenant->isUnlimited($limitKey)) {
            return PHP_INT_MAX;
        }

        $limit = $tenant->limit($limitKey);

        if ($limit <= 0) {
            return 0;
        }

        return max(0, $limit - $this->countForLimit($limitKey, (int) $tenant->id));
    }

    public function syncSnapshot(int $tenantId): TenantUsageSnapshot
    {
        $counts = [];

        foreach (self::LIMIT_USAGE_MAP as $limitKey => $usageKey) {
            if ($usageKey === 'imports_this_month' && ! $this->hasImportSnapshotColumn()) {
                continue;
            }
            $counts[$usageKey] = $this->countForLimit($limitKey, $tenantId);
        }

        $counts['month_reset_at'] = now()->startOfMonth();
        $counts['last_synced_at'] = now();

        return TenantUsageSnapshot::query()->updateOrCreate(
            ['tenant_id' => $tenantId],
            $counts,
        );
    }

    /** @return array<string, int> Snapshot column => live count */
    public function liveCounts(int $tenantId): array
    {
        $counts = [];

        foreach (self::LIMIT_USAGE_MAP as $limitKey => $usageKey) {
            if ($usageKey === 'imports_this_month' && ! $this->hasImportSnapshotColumn()) {
                continue;
            }
            $counts[$usageKey] = $this->countForLimit($limitKey, $tenantId);
        }

        return $counts;
    }

    private function hasImportSnapshotColumn(): bool
    {
        try {
            return Schema::connection('central')->hasColumn('tenant_usage_snapshots', 'imports_this_month');
        } catch (\Throwable) {
            return false;
        }
    }

    private function safeImportUploads(int $tenantId): int
    {
        try {
            return $this->importLimits->uploadsThisCycle($tenantId);
        } catch (\Throwable) {
            return 0;
        }
    }
}
