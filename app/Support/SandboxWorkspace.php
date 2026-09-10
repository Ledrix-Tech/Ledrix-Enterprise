<?php

namespace App\Support;

use App\Models\Central\Tenant;
use Illuminate\Http\Request;

final class SandboxWorkspace
{
    public static function slug(): string
    {
        return (string) config('sandbox.tenant_slug', 'ledrix-demo');
    }

    public static function tenantEmail(): string
    {
        return strtolower((string) config('sandbox.tenant_email', 'sandbox-workspace@ledrix.local'));
    }

    public static function isSandboxTenant(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        if (strtolower((string) $tenant->slug) === self::slug()) {
            return true;
        }

        $meta = is_array($tenant->meta) ? $tenant->meta : [];

        return ($meta['is_sandbox'] ?? false) === true;
    }

    public static function currentIsSandbox(): bool
    {
        $tenantId = TenantContext::resolve();
        if (! $tenantId) {
            return false;
        }

        $tenant = Tenant::query()->find($tenantId);

        return self::isSandboxTenant($tenant);
    }

    public static function sessionIsDemo(Request $request): bool
    {
        return $request->session()->has('demo_account_id');
    }

    public static function crmEmails(): array
    {
        return array_values(array_filter(array_map(
            'strtolower',
            [
                (string) config('sandbox.admin_email'),
                (string) config('sandbox.seller_email'),
                (string) config('sandbox.pm_email'),
                (string) config('sandbox.client_email'),
                self::tenantEmail(),
            ]
        )));
    }

    public static function isProtectedCrmEmail(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return in_array(strtolower(trim($email)), self::crmEmails(), true);
    }

    /**
     * Keep writes compatible with sqlite test schemas and older central tables.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function fillableOn(string $table, array $attributes, string $connection = 'central'): array
    {
        try {
            $columns = \Illuminate\Support\Facades\Schema::connection($connection)->getColumnListing($table);
        } catch (\Throwable) {
            return $attributes;
        }

        if ($columns === []) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_flip($columns));
    }
}
