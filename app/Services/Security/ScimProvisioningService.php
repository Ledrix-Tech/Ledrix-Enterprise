<?php

namespace App\Services\Security;

use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SCIM 2.0 Users provisioning for CRM Admin accounts (and optional Super Admin).
 */
class ScimProvisioningService
{
    private const SCHEMA_USER = 'urn:ietf:params:scim:schemas:core:2.0:User';

    private const SCHEMA_LIST = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createUser(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['userName'] ?? $payload['emails'][0]['value'] ?? '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Valid userName or emails[0].value is required.');
        }

        $active = (bool) ($payload['active'] ?? true);
        $displayName = (string) ($payload['displayName'] ?? $payload['name']['formatted'] ?? Str::before($email, '@'));
        $tenantId = $this->resolveTenantIdFromPayload($payload);

        if ($this->isSuperAdminScope($payload)) {
            $user = $this->upsertSuperAdmin($email, $displayName, $active);

            return $this->formatSuperAdmin($user);
        }

        if (! $tenantId) {
            throw new RuntimeException('tenantId extension or externalId (tenant id) is required for CRM admin provisioning.');
        }

        $admin = $this->upsertCrmAdmin($tenantId, $email, $displayName, $active);

        return $this->formatCrmAdmin($admin);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null Null when user was deactivated/deleted.
     */
    public function updateUser(string $id, array $payload): ?array
    {
        if (isset($payload['active']) && ! $payload['active']) {
            $this->deactivateUser($id);

            return null;
        }

        if (str_starts_with($id, 'sa-')) {
            $user = SuperAdmin::query()->findOrFail((int) substr($id, 3));
            $this->applySuperAdminPatch($user, $payload);

            return $this->formatSuperAdmin($user->fresh());
        }

        $admin = Admin::withoutGlobalScopes()->findOrFail((int) $id);
        $this->applyCrmAdminPatch($admin, $payload);

        return $this->formatCrmAdmin($admin->fresh());
    }

    public function deactivateUser(string $id): void
    {
        if (str_starts_with($id, 'sa-')) {
            SuperAdmin::query()->where('id', (int) substr($id, 3))->update(['status' => 'inactive']);

            return;
        }

        Admin::withoutGlobalScopes()->where('id', (int) $id)->delete();
    }

    public function findUser(string $id): ?array
    {
        if (str_starts_with($id, 'sa-')) {
            $user = SuperAdmin::query()->find((int) substr($id, 3));

            return $user ? $this->formatSuperAdmin($user) : null;
        }

        $admin = Admin::withoutGlobalScopes()->find((int) $id);

        return $admin ? $this->formatCrmAdmin($admin) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function listUsers(int $startIndex = 1, int $count = 100): array
    {
        $admins = Admin::withoutGlobalScopes()
            ->orderBy('id')
            ->skip(max(0, $startIndex - 1))
            ->take($count)
            ->get();

        $resources = $admins->map(fn (Admin $admin) => $this->formatCrmAdmin($admin))->values()->all();

        return [
            'schemas'      => [self::SCHEMA_LIST],
            'totalResults' => Admin::withoutGlobalScopes()->count(),
            'startIndex'   => $startIndex,
            'itemsPerPage' => count($resources),
            'Resources'    => $resources,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveTenantIdFromPayload(array $payload): ?int
    {
        $extensions = $payload['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User'] ?? [];
        $tenantId = $extensions['tenantId'] ?? $payload['externalId'] ?? null;

        if ($tenantId !== null && $tenantId !== '') {
            return (int) $tenantId;
        }

        return TenantContext::resolve();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isSuperAdminScope(array $payload): bool
    {
        $extensions = $payload['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User'] ?? [];

        return ($extensions['scope'] ?? '') === 'super_admin';
    }

    private function upsertSuperAdmin(string $email, string $name, bool $active): SuperAdmin
    {
        $user = SuperAdmin::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->role = $user->role ?: 'support';
        $user->status = $active ? 'active' : 'inactive';

        if (! $user->exists) {
            $user->password = Hash::make(Str::random(32));
        }

        $user->save();

        AuditLog::record(
            action: 'scim.super_admin_provisioned',
            tenantId: null,
            actorType: 'scim',
            actorId: null,
            actorName: 'SCIM',
            context: [
                'description' => "Super Admin provisioned via SCIM: {$email}",
                'after'       => ['email' => $email, 'status' => $user->status],
            ]
        );

        return $user;
    }

    private function upsertCrmAdmin(int $tenantId, string $email, string $name, bool $active): Admin
    {
        Tenant::on('central')->findOrFail($tenantId);

        $admin = Admin::withoutGlobalScopes()->firstOrNew([
            'email'     => $email,
            'tenant_id' => $tenantId,
        ]);

        $admin->name = $name;
        $admin->role = $admin->role ?: (string) config('scim.default_user_role', 'admin');

        if (! $admin->exists) {
            $admin->password = Hash::make(Str::random(32));
        }

        if (! $active) {
            throw new RuntimeException('Create inactive users via PATCH/DELETE after provisioning.');
        }

        $admin->save();

        AuditLog::record(
            action: 'scim.admin_provisioned',
            tenantId: $tenantId,
            actorType: 'scim',
            actorId: null,
            actorName: 'SCIM',
            context: [
                'description' => "CRM admin provisioned via SCIM: {$email}",
                'after'       => ['email' => $email, 'tenant_id' => $tenantId],
            ]
        );

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applySuperAdminPatch(SuperAdmin $user, array $payload): void
    {
        if (isset($payload['active'])) {
            $user->status = $payload['active'] ? 'active' : 'inactive';
        }

        if (isset($payload['displayName'])) {
            $user->name = (string) $payload['displayName'];
        }

        $user->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyCrmAdminPatch(Admin $admin, array $payload): void
    {
        if (isset($payload['displayName'])) {
            $admin->name = (string) $payload['displayName'];
        }

        $admin->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCrmAdmin(Admin $admin): array
    {
        return [
            'schemas'    => [self::SCHEMA_USER],
            'id'         => (string) $admin->id,
            'userName'   => $admin->email,
            'displayName'=> $admin->name,
            'active'     => true,
            'emails'     => [['value' => $admin->email, 'primary' => true]],
            'meta'       => [
                'resourceType' => 'User',
                'created'      => optional($admin->created_at)->toIso8601String(),
                'lastModified' => optional($admin->updated_at)->toIso8601String(),
            ],
            'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User' => [
                'tenantId' => (int) $admin->tenant_id,
                'scope'    => 'crm_admin',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSuperAdmin(SuperAdmin $user): array
    {
        return [
            'schemas'    => [self::SCHEMA_USER],
            'id'         => 'sa-'.$user->id,
            'userName'   => $user->email,
            'displayName'=> $user->name,
            'active'     => $user->status === 'active',
            'emails'     => [['value' => $user->email, 'primary' => true]],
            'meta'       => [
                'resourceType' => 'User',
                'created'      => optional($user->created_at)->toIso8601String(),
                'lastModified' => optional($user->updated_at)->toIso8601String(),
            ],
            'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User' => [
                'scope' => 'super_admin',
            ],
        ];
    }
}
