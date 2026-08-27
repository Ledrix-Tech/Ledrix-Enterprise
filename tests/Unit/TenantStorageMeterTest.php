<?php

namespace Tests\Unit;

use App\Services\Tenant\ProcessTenantStorageAlertsService;
use App\Services\Tenant\TenantStorageMeter;
use App\Services\Tenant\TenantUsageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantStorageMeterTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        Storage::fake('public');
    }

    public function test_meter_returns_zero_when_nothing_found(): void
    {
        $mb = app(TenantStorageMeter::class)->usedMb(42);

        $this->assertSame(0, $mb);
    }

    public function test_meter_sums_public_disk_paths_containing_tenant_id(): void
    {
        $tenantId = 7;
        // ~1.5 MiB → ceil to 2 MB
        Storage::disk('public')->put(
            "tenant-logos/{$tenantId}/logo.png",
            str_repeat('a', (int) (1.5 * 1048576))
        );
        // Other tenant — ignored
        Storage::disk('public')->put('tenant-logos/99/other.png', str_repeat('b', 1048576));

        $mb = app(TenantStorageMeter::class)->usedMb($tenantId);

        $this->assertSame(2, $mb);
    }

    public function test_meter_counts_brief_attachments_from_meta(): void
    {
        $this->bootSqliteDefault();

        Schema::create('questionnairs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        $tenantId = 3;
        Storage::disk('public')->put('uploads/brief-attachments/doc.pdf', str_repeat('x', 1048576));

        DB::table('questionnairs')->insert([
            'tenant_id'  => $tenantId,
            'meta'       => json_encode(['attachments' => ['uploads/brief-attachments/doc.pdf']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mb = app(TenantStorageMeter::class)->usedMb($tenantId);

        $this->assertSame(1, $mb);
    }

    public function test_usage_service_uses_meter_for_max_storage_mb(): void
    {
        $tenantId = 11;
        Storage::disk('public')->put(
            "tenant-logos/{$tenantId}/a.bin",
            str_repeat('z', 3 * 1048576)
        );

        $count = app(TenantUsageService::class)->countForLimit('max_storage_mb', $tenantId);

        $this->assertSame(3, $count);
    }

    public function test_alert_decide_thresholds_and_clear_below_70(): void
    {
        $service = app(ProcessTenantStorageAlertsService::class);

        $this->assertTrue($service->decide(0, -1, false, false)['skip']);
        $this->assertTrue($service->decide(10, 0, false, false)['skip']);

        $at80 = $service->decide(80, 100, false, false);
        $this->assertTrue($at80['send_80']);
        $this->assertFalse($at80['send_100']);
        $this->assertFalse($at80['clear']);

        $at100 = $service->decide(100, 100, true, false);
        $this->assertFalse($at100['send_80']);
        $this->assertTrue($at100['send_100']);

        $alreadySent = $service->decide(95, 100, true, false);
        $this->assertFalse($alreadySent['send_80']);
        $this->assertFalse($alreadySent['send_100']);

        $below70 = $service->decide(69, 100, true, true);
        $this->assertTrue($below70['clear']);
        $this->assertFalse($below70['send_80']);
        $this->assertFalse($below70['send_100']);

        $between70And80 = $service->decide(75, 100, true, false);
        $this->assertFalse($between70And80['clear']);
        $this->assertFalse($between70And80['send_80']);
    }

    private function bootSqliteDefault(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');
        Schema::connection('sqlite')->dropAllTables();
    }
}
