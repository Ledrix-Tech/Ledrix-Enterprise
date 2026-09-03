<?php

namespace Tests\Feature;

use App\Exceptions\ImportPlanLimitException;
use App\Models\ImportBatch;
use App\Services\Tenant\HistoricalImportLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPortalUsers;
use Tests\TestCase;

class HistoricalImportPlanLimitTest extends TestCase
{
    use CreatesPortalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
        Storage::fake('local');
    }

    public function test_blocked_upload_shows_workaround_and_upgrade_not_upgrade_only(): void
    {
        $admin = $this->createAdmin(['role' => 'admin']);
        $seller = $this->createSellerUser();

        $this->mock(HistoricalImportLimitService::class, function ($mock) {
            $mock->shouldReceive('usageForUi')->andReturn([
                'uploads_used' => 0,
                'uploads_max'  => 1,
                'rows_max'     => 150,
                'multi_brand'  => false,
                'reset_on'     => now()->addMonth()->format('M j, Y'),
            ]);
            $mock->shouldReceive('assertCanUpload')->once()->andThrow(new ImportPlanLimitException(
                headline: 'Your file has 340 rows — Basic supports up to 150 per import.',
                workaround: 'Split your file into smaller batches of 150 rows or fewer.',
                upgrade: 'Or upgrade to Standard for up to 1,000 rows per import.',
                upgradeUrl: route('admin.org.plan'),
            ));
        });

        $file = UploadedFile::fake()->createWithContent('big.csv', "name,email\nA,a@example.com\n");

        $this->actingAs($admin, 'admin')
            ->from(route('admin.import.index'))
            ->post(route('admin.import.store'), [
                'brand_id'  => $seller->brand_id,
                'seller_id' => $seller->id,
                'file'      => $file,
            ])
            ->assertRedirect(route('admin.import.index'))
            ->assertSessionHas('import_limit.headline')
            ->assertSessionHas('import_limit.workaround')
            ->assertSessionHas('import_limit.upgrade');

        $this->assertSame(0, ImportBatch::query()->count());

        $flash = session('import_limit');
        $this->assertStringContainsString('340', $flash['headline']);
        $this->assertStringContainsString('150', $flash['headline']);
        $this->assertStringContainsString('Split', $flash['workaround']);
        $this->assertStringContainsString('Standard', $flash['upgrade']);
        $this->assertSame(route('admin.org.plan'), $flash['upgrade_url']);
    }

    public function test_plan_id_at_import_is_stored_from_current_plan(): void
    {
        $admin = $this->createAdmin(['role' => 'admin']);
        $seller = $this->createSellerUser();

        $this->mock(HistoricalImportLimitService::class, function ($mock) {
            $mock->shouldReceive('usageForUi')->andReturn([
                'uploads_used' => 0,
                'uploads_max'  => 5,
                'rows_max'     => 1000,
                'multi_brand'  => true,
                'reset_on'     => null,
            ]);
            $mock->shouldReceive('assertCanUpload')->once();
            $mock->shouldReceive('currentPlanId')->andReturn(42);
        });

        $file = UploadedFile::fake()->createWithContent('ok.csv', "name,email\nA,a@example.com\n");

        $this->actingAs($admin, 'admin')
            ->post(route('admin.import.store'), [
                'brand_id'  => $seller->brand_id,
                'seller_id' => $seller->id,
                'file'      => $file,
            ])
            ->assertRedirect();

        $batch = ImportBatch::query()->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertSame(42, (int) $batch->plan_id_at_import);
    }
}
