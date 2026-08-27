<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Services\Billing\TenantInvoicePdfService;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantInvoicePdfServiceTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        Storage::fake('local');

        if (! \Illuminate\Support\Facades\Schema::connection('central')->hasTable('tenant_invoices')) {
            \Illuminate\Support\Facades\Schema::connection('central')->create('tenant_invoices', function ($table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('membership_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->string('invoice_number')->unique();
                $table->string('plan_name')->nullable();
                $table->string('billing_cycle')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('status')->default('issued');
                $table->string('pdf_path')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_ensure_generates_and_stores_pdf_path(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Acme',
            'slug'     => 'acme-pdf',
            'email'    => 'acme-pdf@example.com',
            'password' => bcrypt('secret'),
            'status'   => 'active',
        ]);

        $invoice = TenantInvoice::query()->create([
            'tenant_id'      => $tenant->id,
            'invoice_number' => 'LDX-2026-9999',
            'plan_name'      => 'Growth',
            'billing_cycle'  => 'monthly',
            'amount'         => 100,
            'currency'       => 'USD',
            'tax_amount'     => 10,
            'total_amount'   => 110,
            'status'         => 'issued',
            'issued_at'      => now(),
        ]);

        $service = app(TenantInvoicePdfService::class);
        $saved = $service->ensure($invoice);

        $this->assertNotNull($saved->pdf_path);
        Storage::disk('local')->assertExists($saved->pdf_path);
        $this->assertGreaterThan(100, Storage::disk('local')->size($saved->pdf_path));
    }
}
