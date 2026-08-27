<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantFeatureOverride;
use App\Services\Tenant\CustomDomainVerificationService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * F-14: Super Admin set / verify / clear / unverify custom domain for a tenant.
 * Reuses CustomDomainVerificationService (same DNS rules as org self-serve).
 */
class SuperAdminTenantDomainController extends Controller
{
    public function update(
        Request $request,
        int $id,
        CustomDomainVerificationService $domains,
        TenantFeatureService $features,
    ) {
        $tenant = Tenant::query()->findOrFail($id);

        $validated = $request->validate([
            'custom_domain' => ['nullable', 'string', 'max:253'],
        ]);

        $raw = trim((string) ($validated['custom_domain'] ?? ''));

        if ($raw === '') {
            $tenant->forceFill([
                'custom_domain'          => null,
                'custom_domain_verified' => false,
            ])->save();

            $this->audit($tenant, 'tenant.custom_domain_cleared', 'Custom domain cleared by super admin.');

            return back()->with('success', 'Custom domain removed.');
        }

        $domain = $domains->normalize($raw);
        if (! $domains->isValidHostname($domain)) {
            throw ValidationException::withMessages([
                'custom_domain' => 'Enter a valid hostname (e.g. crm.youragency.com).',
            ]);
        }

        if (\App\Support\TenantHostResolver::isPlatformWorkspaceHost($domain)) {
            throw ValidationException::withMessages([
                'custom_domain' => 'Do not enter a ledrix.co subdomain here — use Connect your own domain only for external hostnames (e.g. crm.clientagency.com). Default workspace: '.\App\Support\TenantHostResolver::workspaceHostForSlug($tenant->slug),
            ]);
        }

        $taken = Tenant::query()
            ->where('custom_domain', $domain)
            ->where('id', '!=', $tenant->id)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'custom_domain' => 'That domain is already in use by another tenant.',
            ]);
        }

        // SA provisioning: enable feature override without wiping other overrides.
        if (! $features->enabled('custom_domain', (int) $tenant->id)) {
            $override = TenantFeatureOverride::query()->firstOrNew(['tenant_id' => $tenant->id]);
            $override->feature_custom_domain = true;
            if (! filled($override->override_reason)) {
                $override->override_reason = 'Enabled by SA when provisioning custom domain';
            }
            $override->overridden_by = Auth::guard('super_admin')->id();
            $override->save();
            $tenant->unsetRelation('featureOverride');
        }

        $changed = $tenant->custom_domain !== $domain;

        // Bypass model gate: assertEnabled can still fail if plan/override resolution is stale.
        Tenant::withoutEvents(function () use ($tenant, $domain, $changed) {
            $tenant->forceFill([
                'custom_domain'          => $domain,
                'custom_domain_verified' => $changed ? false : (bool) $tenant->custom_domain_verified,
            ])->save();
        });

        $domains->ensureVerificationToken($tenant->fresh());

        $this->audit($tenant, 'tenant.custom_domain_updated', 'Custom domain set to '.$domain.' by super admin.', [
            'custom_domain' => $domain,
        ]);

        return back()->with(
            'success',
            $changed
                ? 'Domain saved. Share DNS instructions with the tenant, then Verify DNS.'
                : 'Domain unchanged.'
        );
    }

    public function verify(int $id, CustomDomainVerificationService $domains)
    {
        $tenant = Tenant::query()->findOrFail($id);
        $result = $domains->verify($tenant->fresh());

        if ($result['verified']) {
            $this->audit($tenant, 'tenant.custom_domain_verified', $result['message']);
        }

        return back()->with($result['verified'] ? 'success' : 'error', $result['message']);
    }

    public function unverify(int $id)
    {
        $tenant = Tenant::query()->findOrFail($id);

        if (! $tenant->custom_domain) {
            return back()->with('error', 'No custom domain is set.');
        }

        $tenant->forceFill(['custom_domain_verified' => false])->save();

        $this->audit($tenant, 'tenant.custom_domain_unverified', 'Custom domain marked unverified by super admin.');

        return back()->with('success', 'Domain marked unverified. Tenant must re-verify DNS.');
    }

    /** @param array<string, mixed> $after */
    private function audit(Tenant $tenant, string $action, string $description, array $after = []): void
    {
        $actor = Auth::guard('super_admin')->user();

        AuditLog::record(
            $action,
            (int) $tenant->id,
            'super_admin',
            $actor?->id,
            $actor?->name ?? 'Super Admin',
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => $description,
                'after'        => $after ?: null,
            ]
        );
    }
}
