<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Services\Central\ImpersonationService;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * F-08: Super Admin impersonation service (start/stop + audit).
 * Uses in-memory sqlite so it runs without a local MySQL primary DB.
 */
class ImpersonationServiceTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->bootSqliteDefaultForAdmins();
    }

    public function test_start_logs_in_admin_stores_session_and_audits(): void
    {
        [$tenant, $admin, $superAdmin] = $this->seedActors();

        $this->actingAs($superAdmin, 'super_admin');

        $result = app(ImpersonationService::class)->start($tenant, $superAdmin);

        $this->assertSame($admin->id, $result->id);
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($admin->id, Auth::guard('admin')->id());
        $this->assertFalse(Auth::guard('super_admin')->check());
        $this->assertSame($tenant->id, (int) session('tenant_id'));
        $this->assertSame($superAdmin->id, (int) session('impersonator_super_admin_id'));
        $this->assertSame($tenant->id, (int) session('impersonated_tenant_id'));
        $this->assertSame($admin->id, (int) session('impersonated_admin_id'));
        $this->assertNotEmpty(session('impersonation_started_at'));
        $this->assertSame($tenant->id, TenantContext::resolve());
        $this->assertTrue(
            AuditLog::query()
                ->where('action', 'tenant.impersonation_started')
                ->where('tenant_id', $tenant->id)
                ->where('actor_id', $superAdmin->id)
                ->exists()
        );
    }

    public function test_stop_restores_super_admin_and_audits(): void
    {
        [$tenant, $admin, $superAdmin] = $this->seedActors();

        $service = app(ImpersonationService::class);
        $service->start($tenant, $superAdmin);

        $restored = $service->stop();

        $this->assertSame($superAdmin->id, $restored->id);
        $this->assertTrue(Auth::guard('super_admin')->check());
        $this->assertSame($superAdmin->id, Auth::guard('super_admin')->id());
        $this->assertFalse(Auth::guard('admin')->check());
        $this->assertNull(session('impersonator_super_admin_id'));
        $this->assertNull(session('impersonated_tenant_id'));
        $this->assertNull(session('tenant_id'));
        $this->assertNull(TenantContext::resolve());
        $this->assertTrue(
            AuditLog::query()
                ->where('action', 'tenant.impersonation_ended')
                ->where('tenant_id', $tenant->id)
                ->where('actor_id', $superAdmin->id)
                ->exists()
        );
    }

    public function test_start_refuses_suspended_tenant(): void
    {
        [$tenant, , $superAdmin] = $this->seedActors();
        $tenant->update([
            'status'           => 'suspended',
            'suspended_reason' => 'policy',
            'suspended_at'     => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot impersonate a suspended tenant.');

        app(ImpersonationService::class)->start($tenant->fresh(), $superAdmin);
    }

    public function test_start_refuses_trashed_tenant(): void
    {
        [$tenant, , $superAdmin] = $this->seedActors();
        $tenant->delete();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot impersonate an offboarded tenant.');

        app(ImpersonationService::class)->start(Tenant::withTrashed()->findOrFail($tenant->id), $superAdmin);
    }

    public function test_stop_without_session_fails_clearly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No active impersonation session.');

        app(ImpersonationService::class)->stop();
    }

    /**
     * @return array{0: Tenant, 1: Admin, 2: SuperAdmin}
     */
    private function seedActors(): array
    {
        $tenant = Tenant::query()->create([
            'name'     => 'Acme Impersonate',
            'slug'     => 'acme-imp-'.uniqid(),
            'email'    => 'acme-imp-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $admin = Admin::withoutGlobalScopes()->create([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'email'     => 'owner-imp-'.uniqid().'@example.com',
            'name'      => 'Tenant Owner',
            'password'  => Hash::make('password'),
        ]);

        $superAdmin = SuperAdmin::query()->create([
            'name'     => 'Platform Admin',
            'email'    => 'sa-imp-'.uniqid().'@example.com',
            'password' => Hash::make('Password1!'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        return [$tenant, $admin, $superAdmin];
    }

    private function bootSqliteDefaultForAdmins(): void
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

        Schema::connection('sqlite')->dropIfExists('admins');
        Schema::connection('sqlite')->create('admins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
    }
}
