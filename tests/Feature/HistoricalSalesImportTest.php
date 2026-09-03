<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Services\Admin\HistoricalSalesImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPortalUsers;
use Tests\TestCase;

class HistoricalSalesImportTest extends TestCase
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

    public function test_admin_can_download_sample_csv(): void
    {
        $admin = $this->createAdmin(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.import.sample'))
            ->assertOk()
            ->assertHeader('content-disposition')
            ->assertSee('alice.contact@example.com')
            ->assertSee('cara.cash@example.com');
    }

    public function test_seller_cannot_open_import(): void
    {
        $seller = $this->createSellerUser();

        $this->actingAs($seller, 'seller')
            ->get(route('admin.import.index'))
            ->assertRedirect(route('admin.login.get'));
    }

    public function test_finance_cannot_open_import(): void
    {
        $finance = $this->createAdmin(['role' => 'finance']);

        $this->actingAs($finance, 'admin')
            ->get(route('admin.import.index'))
            ->assertRedirect(route('admin.brand-payments.get'));
    }

    public function test_names_and_emails_create_leads_only(): void
    {
        [$admin, $brand, $seller] = $this->workspace();

        $this->commitCsv($admin, $brand, $seller, [
            ['name', 'email', 'phone'],
            ['Alice Contact', 'alice.contact@example.com', '5551112222'],
        ]);

        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, PaymentLink::query()->count());
        $this->assertSame(0, Payment::query()->count());

        $lead = Lead::query()->first();
        $this->assertSame('Alice Contact', $lead->name);
        $this->assertSame('alice.contact@example.com', $lead->email);
        $this->assertSame('import', $lead->source);
        $this->assertNotNull($lead->import_batch_id);
    }

    public function test_amount_without_payment_creates_lead_and_order_only(): void
    {
        [$admin, $brand, $seller] = $this->workspace();

        $this->commitCsv($admin, $brand, $seller, [
            ['name', 'email', 'order_amount'],
            ['Bob Invoice', 'bob.invoice@example.com', '1500.00'],
        ]);

        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(0, PaymentLink::query()->count());
        $this->assertSame(150000, (int) Order::query()->first()->unit_amount);
        $this->assertSame(0, (int) Order::query()->first()->amount_paid);
    }

    public function test_cash_payment_has_null_provider_ids_and_no_pay_link(): void
    {
        [$admin, $brand, $seller] = $this->workspace();

        $this->commitCsv($admin, $brand, $seller, [
            ['name', 'email', 'order_amount', 'pay_link_provider', 'amount_paid', 'paid_at'],
            ['Cara Cash', 'cara.cash@example.com', '800.00', 'cash', '800.00', '2024-03-15'],
        ]);

        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(0, PaymentLink::query()->count());
        $this->assertSame(1, Payment::query()->count());

        $payment = Payment::query()->first();
        $this->assertNull($payment->payment_link_id);
        $this->assertNull($payment->provider);
        $this->assertNull($payment->provider_payment_intent_id);
        $this->assertSame(80000, (int) $payment->amount);
        $this->assertFalse((bool) $payment->needs_review);
    }

    public function test_does_not_fabricate_provider_ids(): void
    {
        [$admin, $brand, $seller] = $this->workspace();

        $this->commitCsv($admin, $brand, $seller, [
            ['name', 'email', 'order_amount', 'pay_link_provider', 'provider_link_id', 'amount_paid', 'provider_payment_id'],
            ['Dan Stripe', 'dan.stripe@example.com', '2000.00', 'stripe', 'cs_test_real_from_sheet', '2000.00', 'pi_test_real_from_sheet'],
            ['Eve Unknown', 'eve.unknown@example.com', '500.00', 'stripe', '', '500.00', ''],
        ]);

        $links = PaymentLink::query()->get();
        $this->assertCount(1, $links);
        $this->assertSame('cs_test_real_from_sheet', $links->first()->provider_session_id);
        $this->assertStringStartsWith('imp-', $links->first()->token);

        $payments = Payment::query()->orderBy('id')->get();
        $this->assertCount(2, $payments);
        $this->assertSame('pi_test_real_from_sheet', $payments[0]->provider_payment_intent_id);
        $this->assertNull($payments[1]->provider_payment_intent_id);
        $this->assertNull($payments[1]->payment_link_id);

        $this->assertSame(0, PaymentLink::query()->where('provider_session_id', 'like', 'cs_test_%')->where('provider_session_id', '!=', 'cs_test_real_from_sheet')->count());
        $this->assertFalse(
            Payment::query()->whereNotNull('provider_payment_intent_id')
                ->where('provider_payment_intent_id', '!=', 'pi_test_real_from_sheet')
                ->exists()
        );
    }

    public function test_duplicates_are_not_silent_and_skip_does_not_create_a_second_lead(): void
    {
        [$admin, $brand, $seller] = $this->workspace();

        Lead::factory()->create([
            'tenant_id' => 1,
            'brand_id'  => $brand->id,
            'seller_id' => $seller->id,
            'email'     => 'dup@example.com',
            'name'      => 'Existing',
        ]);

        $this->commitCsv($admin, $brand, $seller, [
            ['name', 'email', 'order_amount'],
            ['Dup Person', 'dup@example.com', '100.00'],
        ], 'skip');

        $this->assertSame(1, Lead::query()->where('email', 'dup@example.com')->count());
        $this->assertSame(0, Order::query()->count());

        $batch = ImportBatch::query()->latest('id')->first();
        $this->assertGreaterThan(0, $batch->summary['duplicates'] ?? 0);
        $this->assertSame('skip', $batch->duplicate_strategy);
    }

    public function test_unmatched_brand_rows_are_reported_not_imported(): void
    {
        [$admin, $brand, $seller] = $this->workspace();

        $this->commitCsv($admin, $brand, $seller, [
            ['name', 'email', 'brand_name', 'order_amount'],
            ['Known', 'known@example.com', $brand->brand_name, '10.00'],
            ['Ghost', 'ghost@example.com', 'No Such LLC', '99.00'],
        ], 'skip', multiBrand: true);

        $this->assertTrue(Lead::query()->where('email', 'known@example.com')->exists());
        $this->assertFalse(Lead::query()->where('email', 'ghost@example.com')->exists());

        $batch = ImportBatch::query()->latest('id')->first();
        $this->assertSame(1, $batch->summary['unmatched_brands'] ?? 0);
        $this->assertSame(1, $batch->summary['rejected'] ?? 0);
    }

    public function test_preview_counts_match_commit_for_new_rows(): void
    {
        [$admin, $brand, $seller] = $this->workspace();
        $service = app(HistoricalSalesImportService::class);

        $rows = [
            ['name', 'email', 'order_amount', 'amount_paid'],
            ['Only Lead', 'only.lead@example.com', '', ''],
            ['With Order', 'with.order@example.com', '50.00', ''],
            ['Cash', 'cash.row@example.com', '80.00', '80.00'],
        ];

        $batch = $this->uploadAndMap($admin, $brand, $seller, $rows);
        $preview = $service->preview($batch, $batch->fresh()->mapping, 'skip');
        $commit = $service->commit($batch->fresh(), $batch->fresh()->mapping, 'skip');

        $this->assertSame($preview['summary'], $commit['summary']);
        $this->assertSame(3, Lead::query()->count());
        $this->assertSame(2, Order::query()->count());
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(0, PaymentLink::query()->count());
    }

    public function test_rollback_removes_imported_rows(): void
    {
        [$admin, $brand, $seller] = $this->workspace();

        $this->commitCsv($admin, $brand, $seller, [
            ['name', 'email', 'order_amount', 'amount_paid'],
            ['Cara Cash', 'cara.rollback@example.com', '800.00', '800.00'],
        ]);

        $batch = ImportBatch::query()->latest('id')->first();
        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(1, Payment::query()->count());

        $this->actingAs($admin, 'admin')
            ->post(route('admin.import.rollback', $batch))
            ->assertRedirect(route('admin.import.show', $batch));

        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame('rolled_back', $batch->fresh()->status);
    }

    /**
     * @return array{0: \App\Models\Admin, 1: \App\Models\Brand, 2: \App\Models\Seller}
     */
    private function workspace(): array
    {
        $admin = $this->createAdmin(['role' => 'admin']);
        $seller = $this->createSellerUser();

        return [$admin, $seller->brand, $seller];
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function commitCsv($admin, $brand, $seller, array $rows, string $strategy = 'skip', bool $multiBrand = false): void
    {
        $batch = $this->uploadAndMap($admin, $brand, $seller, $rows, $multiBrand);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.import.commit', $batch), [
                'duplicate_strategy' => $strategy,
            ])
            ->assertRedirect(route('admin.import.show', $batch));
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function uploadAndMap($admin, $brand, $seller, array $rows, bool $multiBrand = false): ImportBatch
    {
        $csv = $this->toCsv($rows);
        $file = UploadedFile::fake()->createWithContent('sales.csv', $csv);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.import.store'), [
                'brand_id'    => $brand->id,
                'seller_id'   => $seller->id,
                'multi_brand' => $multiBrand ? '1' : '0',
                'file'        => $file,
            ])
            ->assertRedirect();

        $batch = ImportBatch::query()->latest('id')->first();
        $this->assertNotNull($batch);

        $service = app(HistoricalSalesImportService::class);
        $parsed = $service->parseStoredCsv($batch);
        $mapping = $service->suggestMapping($parsed['headers']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.import.map.save', $batch), [
                'mapping' => $mapping,
            ])
            ->assertRedirect(route('admin.import.preview', $batch));

        return $batch->fresh();
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
