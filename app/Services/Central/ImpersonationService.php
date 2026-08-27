<?php

namespace App\Services\Central;

use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Services\Tenant\ProvisionTenantAdminService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ImpersonationService
{
    public const SESSION_IMPERSONATOR_ID = 'impersonator_super_admin_id';

    public const SESSION_STARTED_AT = 'impersonation_started_at';

    public const SESSION_TENANT_ID = 'impersonated_tenant_id';

    public const SESSION_ADMIN_ID = 'impersonated_admin_id';

    public function isActive(): bool
    {
        return (int) session(self::SESSION_IMPERSONATOR_ID, 0) > 0;
    }

    /**
     * Enter the tenant CRM as its owner admin on behalf of a super admin.
     */
    public function start(Tenant $tenant, SuperAdmin $superAdmin): Admin
    {
        if ($tenant->trashed()) {
            throw new InvalidArgumentException('Cannot impersonate an offboarded tenant.');
        }

        if ($tenant->isSuspended()) {
            throw new InvalidArgumentException('Cannot impersonate a suspended tenant.');
        }

        if ($this->isActive()) {
            throw new InvalidArgumentException('An impersonation session is already active. End it before starting another.');
        }

        $admin = $this->resolveTenantOwnerAdmin($tenant);

        Auth::guard('super_admin')->logout();

        session([
            self::SESSION_IMPERSONATOR_ID => (int) $superAdmin->id,
            self::SESSION_STARTED_AT      => now()->toIso8601String(),
            self::SESSION_TENANT_ID       => (int) $tenant->id,
            self::SESSION_ADMIN_ID        => (int) $admin->id,
            'tenant_id'                   => (int) $tenant->id,
        ]);

        TenantContext::set((int) $tenant->id);

        Auth::guard('admin')->login($admin);
        session()->regenerate();

        AuditLog::record(
            'tenant.impersonation_started',
            (int) $tenant->id,
            'super_admin',
            (int) $superAdmin->id,
            $superAdmin->name,
            [
                'subject_type' => 'admin',
                'subject_id'   => $admin->id,
                'description'  => 'Super admin started impersonating tenant CRM admin.',
                'after'        => [
                    'impersonated_admin_id' => $admin->id,
                    'impersonated_tenant_id'=> $tenant->id,
                ],
            ]
        );

        return $admin;
    }

    /**
     * End impersonation and restore the original super admin session.
     */
    public function stop(): SuperAdmin
    {
        if (! $this->isActive()) {
            throw new InvalidArgumentException('No active impersonation session.');
        }

        $superAdminId = (int) session(self::SESSION_IMPERSONATOR_ID);
        $tenantId = (int) session(self::SESSION_TENANT_ID);
        $adminId = (int) session(self::SESSION_ADMIN_ID);
        $startedAt = session(self::SESSION_STARTED_AT);

        $superAdmin = SuperAdmin::query()->find($superAdminId);

        Auth::guard('admin')->logout();
        $this->forgetImpersonationSession();
        TenantContext::clear();

        if (! $superAdmin) {
            throw new RuntimeException('The impersonating super admin account no longer exists.');
        }

        Auth::guard('super_admin')->login($superAdmin);
        session()->regenerate();

        AuditLog::record(
            'tenant.impersonation_ended',
            $tenantId > 0 ? $tenantId : null,
            'super_admin',
            (int) $superAdmin->id,
            $superAdmin->name,
            [
                'subject_type' => 'admin',
                'subject_id'   => $adminId > 0 ? $adminId : null,
                'description'  => 'Super admin ended tenant CRM impersonation.',
                'before'       => [
                    'impersonation_started_at' => $startedAt,
                    'impersonated_admin_id'    => $adminId,
                    'impersonated_tenant_id'   => $tenantId,
                ],
            ]
        );

        return $superAdmin;
    }

    public function forgetImpersonationSession(): void
    {
        session()->forget([
            self::SESSION_IMPERSONATOR_ID,
            self::SESSION_STARTED_AT,
            self::SESSION_TENANT_ID,
            self::SESSION_ADMIN_ID,
            'tenant_id',
        ]);
    }

    private function resolveTenantOwnerAdmin(Tenant $tenant): Admin
    {
        $admin = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'admin')
            ->orderByRaw('CASE WHEN email = ? THEN 0 ELSE 1 END', [$tenant->email])
            ->orderBy('id')
            ->first();

        if ($admin) {
            return $admin;
        }

        if (! class_exists(ProvisionTenantAdminService::class)) {
            throw new InvalidArgumentException(
                'No CRM admin exists for this tenant and admin provisioning is unavailable.'
            );
        }

        try {
            return app(ProvisionTenantAdminService::class)->provision($tenant);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'No CRM admin exists for this tenant and provisioning failed: '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
