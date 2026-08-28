<?php

namespace Tests\Unit;

use App\Mail\TenantSuspendedMail;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Services\Tenant\TenantLifecycleService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantLifecycleServiceTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureMembershipsTable();
        Mail::fake();
    }

    public function test_suspend_sets_metadata_revokes_tokens_and_audits(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Acme',
            'slug'     => 'acme-life',
            'email'    => 'acme-life@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        [$plain, $token] = TenantApiToken::generate((int) $tenant->id, 'CI');
        $this->assertNotEmpty($plain);
        $this->assertSame('active', $token->status);

        app(TenantLifecycleService::class)->suspend(
            $tenant,
            'Policy violation',
            'super_admin',
            1,
            'Owner'
        );

        $tenant->refresh();
        $this->assertSame('suspended', $tenant->status);
        $this->assertSame('Policy violation', $tenant->suspended_reason);
        $this->assertNotNull($tenant->suspended_at);
        $this->assertSame('revoked', $token->fresh()->status);
        $this->assertTrue(
            AuditLog::query()->where('action', 'tenant.suspended')->where('tenant_id', $tenant->id)->exists()
        );

        Mail::assertSent(TenantSuspendedMail::class, function (TenantSuspendedMail $mail) use ($tenant) {
            return $mail->hasTo($tenant->email)
                && $mail->tenant->id === $tenant->id
                && $mail->reason === 'Policy violation';
        });
    }

    public function test_activate_clears_suspend_metadata(): void
    {
        $tenant = Tenant::query()->create([
            'name'             => 'Beta',
            'slug'             => 'beta-life',
            'email'            => 'beta-life@example.com',
            'password'         => Hash::make('password'),
            'status'           => 'suspended',
            'suspended_reason' => 'temp',
            'suspended_at'     => now(),
        ]);

        app(TenantLifecycleService::class)->activate($tenant, 'super_admin', 1, 'Owner');

        $tenant->refresh();
        $this->assertSame('active', $tenant->status);
        $this->assertNull($tenant->suspended_reason);
        $this->assertNull($tenant->suspended_at);
    }

    public function test_offboard_soft_deletes_and_cancels(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Gamma',
            'slug'     => 'gamma-life',
            'email'    => 'gamma-life@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        app(TenantLifecycleService::class)->offboard($tenant, 'Customer churned', 'super_admin', 1, 'Owner');

        $this->assertTrue(Tenant::withTrashed()->find($tenant->id)?->trashed());
        $this->assertSame('cancelled', Tenant::withTrashed()->find($tenant->id)?->status);
        $this->assertTrue(
            AuditLog::query()->where('action', 'tenant.offboarded')->exists()
        );
    }

    private function ensureMembershipsTable(): void
    {
        if (Schema::connection('central')->hasTable('tenant_memberships')) {
            return;
        }

        Schema::connection('central')->create('tenant_memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('api_key', 64)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
}
