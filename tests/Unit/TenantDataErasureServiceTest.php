<?php

namespace Tests\Unit;

use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
use App\Services\Tenant\TenantDataErasureService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantDataErasureServiceTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureMembershipsAndPhoneColumns();
        $this->bootSqlitePrimary();
    }

    public function test_erasure_anonymizes_email_revokes_token_and_audits(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Acme GDPR',
            'slug'     => 'acme-gdpr',
            'email'    => 'acme-gdpr@example.com',
            'password' => Hash::make('password'),
            'phone'    => '+15551234567',
            'status'   => 'active',
            'meta'     => [
                'registered_from' => 'landing',
                'attribution'     => ['utm_source' => 'ads'],
                'keep_me'         => true,
            ],
        ]);

        [$plain, $token] = TenantApiToken::generate((int) $tenant->id, 'GDPR test');
        $this->assertNotEmpty($plain);

        DB::connection('primary')->table('leads')->insert([
            'tenant_id' => $tenant->id,
            'name'      => 'Ada Lovelace',
            'email'     => 'ada@example.com',
            'phone'     => '+10000000001',
            'password'  => Hash::make('secret'),
        ]);

        DB::connection('primary')->table('clients')->insert([
            'tenant_id' => $tenant->id,
            'name'      => 'Client Co',
            'email'     => 'client@example.com',
            'phone'     => '+10000000002',
        ]);

        app(TenantDataErasureService::class)->erase(
            $tenant,
            'Customer DSAR',
            'super_admin',
            1,
            'Owner'
        );

        $erased = Tenant::withTrashed()->findOrFail($tenant->id);

        $this->assertTrue($erased->trashed());
        $this->assertSame('Erased Tenant', $erased->name);
        $this->assertSame('erased+'.$tenant->id.'@erased.local', $erased->email);
        $this->assertNull($erased->phone);
        $this->assertSame('cancelled', $erased->status);
        $this->assertNull(data_get($erased->meta, 'registered_from'));
        $this->assertNull(data_get($erased->meta, 'attribution'));
        $this->assertTrue((bool) data_get($erased->meta, 'keep_me'));
        $this->assertNotNull(data_get($erased->meta, 'erasure_completed_at'));

        $this->assertSame('revoked', $token->fresh()->status);

        $lead = DB::connection('primary')->table('leads')->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($lead);
        $this->assertSame('Erased', $lead->name);
        $this->assertStringEndsWith('@erased.local', $lead->email);
        $this->assertNull($lead->phone);

        $client = DB::connection('primary')->table('clients')->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($client);
        $this->assertStringEndsWith('@erased.local', $client->email);

        $this->assertTrue(
            AuditLog::query()
                ->where('action', 'tenant.erasure_completed')
                ->where('tenant_id', $tenant->id)
                ->exists()
        );
    }

    public function test_double_erasure_is_rejected(): void
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Once',
            'slug'     => 'once-gdpr',
            'email'    => 'once@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        app(TenantDataErasureService::class)->erase($tenant, 'First pass', 'super_admin', 1, 'Owner');

        $this->expectException(\InvalidArgumentException::class);
        app(TenantDataErasureService::class)->erase(
            Tenant::withTrashed()->findOrFail($tenant->id),
            'Second pass',
            'super_admin',
            1,
            'Owner'
        );
    }

    private function ensureMembershipsAndPhoneColumns(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'phone')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->string('phone', 50)->nullable();
                $table->string('billing_name')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('billing_phone', 50)->nullable();
                $table->string('billing_address')->nullable();
                $table->string('registered_ip', 45)->nullable();
                $table->string('last_login_ip', 45)->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('website')->nullable();
                $table->string('address')->nullable();
                $table->string('logo')->nullable();
                $table->rememberToken();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_memberships')) {
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

    private function bootSqlitePrimary(): void
    {
        config([
            'database.connections.primary' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        app('db')->purge('primary');
        app('db')->reconnect('primary');

        Schema::connection('primary')->create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('primary')->create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
