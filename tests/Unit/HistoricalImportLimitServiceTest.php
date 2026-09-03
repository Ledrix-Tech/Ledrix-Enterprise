<?php

namespace Tests\Unit;

use App\Exceptions\ImportPlanLimitException;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Services\Tenant\HistoricalImportLimitService;
use App\Services\Tenant\SubscriptionAccessService;
use Mockery;
use Tests\TestCase;

class HistoricalImportLimitServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_inspect_csv_counts_data_rows_and_detects_multi_brand(): void
    {
        $path = $this->writeCsv([
            ['name', 'email', 'brand_name'],
            ['A', 'a@example.com', 'Acme'],
            ['B', 'b@example.com', 'Ledrix'],
            ['', '', ''],
        ]);

        $inspected = $this->service()->inspectCsv($path);

        $this->assertSame(2, $inspected['rows']);
        $this->assertTrue($inspected['implies_multi_brand']);
        unlink($path);
    }

    public function test_basic_plan_blocks_oversized_file_with_exact_counts_and_workaround(): void
    {
        $tenant = $this->basicTenant();
        $service = $this->service();

        try {
            $service->assertWithinPlan($tenant, [
                'rows'                => 340,
                'headers'             => ['name', 'email'],
                'distinct_brands'     => [],
                'implies_multi_brand' => false,
            ], false);
            $this->fail('Expected ImportPlanLimitException');
        } catch (ImportPlanLimitException $e) {
            $this->assertStringContainsString('340 rows', $e->headline);
            $this->assertStringContainsString('150', $e->headline);
            $this->assertStringContainsString('Split your file', $e->workaround);
            $this->assertStringContainsString('Standard', $e->upgrade);
            $this->assertStringContainsString('1,000', $e->upgrade);
            $this->assertSame(route('admin.org.plan'), $e->upgradeUrl);
        }
    }

    public function test_basic_plan_rejects_multi_brand_sheet(): void
    {
        $this->expectException(ImportPlanLimitException::class);
        $this->expectExceptionMessage('single brand');

        $this->service()->assertWithinPlan($this->basicTenant(), [
            'rows'                => 2,
            'headers'             => ['name', 'email', 'brand_name'],
            'distinct_brands'     => ['Acme', 'Ledrix'],
            'implies_multi_brand' => true,
        ], false);
    }

    public function test_billing_cycle_uses_membership_dates_not_calendar_month(): void
    {
        $membership = new TenantMembership();
        $membership->forceFill([
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date'   => now()->addDays(20)->toDateString(),
        ]);

        $subs = Mockery::mock(SubscriptionAccessService::class);
        $subs->shouldReceive('currentMembership')->andReturn($membership);

        $window = (new HistoricalImportLimitService($subs))->billingCycleWindow($this->basicTenant());

        $this->assertTrue($window['start']->equalTo($membership->start_date->copy()->startOfDay()));
        $this->assertTrue($window['end']->equalTo($membership->end_date->copy()->endOfDay()));
    }

    public function test_basic_reimport_block_mentions_reset_date_and_upgrade(): void
    {
        $subs = Mockery::mock(SubscriptionAccessService::class);
        $subs->shouldReceive('currentMembership')->andReturn(null);
        $service = Mockery::mock(HistoricalImportLimitService::class, [$subs])->makePartial();
        $service->shouldReceive('uploadsThisCycle')->andReturn(1);

        try {
            $service->assertWithinPlan($this->basicTenant(), [
                'rows'                => 10,
                'headers'             => ['name', 'email'],
                'distinct_brands'     => [],
                'implies_multi_brand' => false,
            ], false);
            $this->fail('Expected ImportPlanLimitException');
        } catch (ImportPlanLimitException $e) {
            $this->assertStringContainsString('1 import', $e->headline);
            $this->assertStringContainsString('Resets on', $e->headline);
            $this->assertStringContainsString('Wait', $e->workaround);
            $this->assertStringContainsString('Standard', $e->upgrade);
        }
    }

    public function test_premium_unlimited_allows_large_multi_brand_file(): void
    {
        $plan = new PackagePricing([
            'name'                         => 'Premium',
            'slug'                         => 'crm-premium',
            'import_max_rows_per_upload'   => -1,
            'import_max_uploads_per_month' => -1,
            'import_multi_brand_allowed'   => true,
            'import_reimport_allowed'      => true,
        ]);
        $tenant = new Tenant();
        $tenant->forceFill(['id' => 1, 'plan_id' => 9]);
        $tenant->setRelation('plan', $plan);
        $tenant->setRelation('limitOverride', null);

        $subs = Mockery::mock(SubscriptionAccessService::class);
        $subs->shouldReceive('currentMembership')->andReturn(null);
        $service = Mockery::mock(HistoricalImportLimitService::class, [$subs])->makePartial();
        $service->shouldReceive('uploadsThisCycle')->andReturn(12);

        $service->assertWithinPlan($tenant, [
            'rows'                => 5000,
            'headers'             => ['name', 'email', 'brand_name'],
            'distinct_brands'     => ['A', 'B'],
            'implies_multi_brand' => true,
        ], true);

        $this->assertTrue(true);
    }

    public function test_sheet_import_label_uses_real_numbers(): void
    {
        $basic = new PackagePricing([
            'import_max_rows_per_upload'   => 150,
            'import_max_uploads_per_month' => 1,
            'import_multi_brand_allowed'   => false,
        ]);
        $this->assertSame('150 rows, 1/mo, single brand', $basic->sheetImportComparisonLabel());

        $premium = new PackagePricing([
            'import_max_rows_per_upload'   => -1,
            'import_max_uploads_per_month' => -1,
            'import_multi_brand_allowed'   => true,
        ]);
        $this->assertSame('Unlimited, multi-brand', $premium->sheetImportComparisonLabel());
    }

    private function basicTenant(): Tenant
    {
        $plan = new PackagePricing([
            'name'                         => 'Basic',
            'slug'                         => 'crm-basic',
            'import_max_rows_per_upload'   => 150,
            'import_max_uploads_per_month' => 1,
            'import_multi_brand_allowed'   => false,
            'import_reimport_allowed'      => false,
        ]);
        $tenant = new Tenant();
        $tenant->forceFill(['id' => 1, 'plan_id' => 3]);
        $tenant->setRelation('plan', $plan);
        $tenant->setRelation('limitOverride', null);

        return $tenant;
    }

    private function service(): HistoricalImportLimitService
    {
        $subs = Mockery::mock(SubscriptionAccessService::class);
        $subs->shouldReceive('currentMembership')->andReturn(null);

        $service = Mockery::mock(HistoricalImportLimitService::class, [$subs])->makePartial();
        $service->shouldReceive('uploadsThisCycle')->andReturn(0);

        return $service;
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function writeCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        $handle = fopen($path, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }
}
