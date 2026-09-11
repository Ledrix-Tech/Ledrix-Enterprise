<?php

namespace App\Support;

use App\Mail\TenantGettingStartedMail;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * First-time CRM setup order, confirmed against Brand → Seller → Lead intake →
 * Client-at-intake / Order-from-payment-link → optional Project.
 */
final class GettingStartedGuide
{
    public const DISMISSED_META_KEY = 'getting_started_dismissed_at';
    public const SESSION_CLOSED_KEY = 'getting_started_closed';

    /**
     * @return list<array{title: string, body: string}>
     */
    public static function steps(): array
    {
        return [
            [
                'title' => 'Add your first Brand',
                'body'  => 'Everything in the CRM sits under a Brand. Signup does not create one — add it before Sellers, leads, or payment links.',
            ],
            [
                'title' => 'Add a Seller on that Brand',
                'body'  => 'Leads cannot be captured or imported until an Active closer (front seller) exists on the Brand. Create a Seller record even if you work alone — the admin login is not a Seller.',
            ],
            [
                'title' => 'Get leads in',
                'body'  => 'Paste the Brand lead script on your site, send form posts to the public lead API, or import a spreadsheet (import still needs a Brand and a Seller).',
            ],
            [
                'title' => 'Clients are created with each lead',
                'body'  => 'A Client is created automatically when a lead arrives. Changing lead status does not create a Client or an Order.',
            ],
            [
                'title' => 'Generate a payment link from the Lead',
                'body'  => 'Add Stripe or PayPal under Payment Accounts, then generate a link from the Lead — that creates the Order. First payment marks the lead as paid.',
            ],
            [
                'title' => 'Add a Project from the Order when you start delivery',
                'body'  => 'Projects are optional and not created automatically. Add one from the Order if you need delivery tracking.',
            ],
        ];
    }

    public static function intro(): string
    {
        return 'This is a quick reference to get you oriented — Ledrix will also guide you step-by-step inside the app as you go.';
    }

    public static function currentTenant(?Request $request = null): ?Tenant
    {
        if (Auth::guard('tenant')->check()) {
            $tenant = Auth::guard('tenant')->user();

            return $tenant instanceof Tenant ? $tenant : null;
        }

        $tenantId = TenantContext::resolve()
            ?? Auth::guard('admin')->user()?->tenant_id;

        if (! $tenantId) {
            return null;
        }

        return Tenant::query()->find($tenantId);
    }

    public static function shouldShowModal(?Request $request = null): bool
    {
        $request ??= request();

        if (SandboxWorkspace::sessionIsDemo($request) || SandboxWorkspace::currentIsSandbox()) {
            return false;
        }

        if ($request->routeIs(
            'admin.org.getting-started',
            'tenant.getting-started',
            'admin.2fa.*',
            'seller.2fa.*',
            'client.2fa.*',
            'admin.login*',
            'tenant.login*'
        )) {
            return false;
        }

        $admin = Auth::guard('admin')->user();
        $isTenant = Auth::guard('tenant')->check();

        if (! $isTenant && (! $admin || ($admin->role ?? null) !== 'admin')) {
            return false;
        }

        $tenant = self::currentTenant($request);

        if ($tenant === null || self::isDismissed($tenant)) {
            return false;
        }

        return ! (bool) $request->session()->get(self::SESSION_CLOSED_KEY, false);
    }

    public static function isDismissed(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return true;
        }

        $meta = is_array($tenant->meta) ? $tenant->meta : [];

        return filled($meta[self::DISMISSED_META_KEY] ?? null);
    }

    public static function closeForSession(?Request $request = null): void
    {
        ($request ?? request())->session()->put(self::SESSION_CLOSED_KEY, true);
    }

    public static function dismiss(Tenant $tenant): void
    {
        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        $meta[self::DISMISSED_META_KEY] = now()->toIso8601String();
        $tenant->forceFill(['meta' => $meta])->save();
    }

    public static function sendEmail(Tenant $tenant): bool
    {
        return SafeMail::send(
            $tenant->email,
            new TenantGettingStartedMail($tenant),
            'tenant getting started email',
            ['tenant_id' => $tenant->id],
        );
    }
}
