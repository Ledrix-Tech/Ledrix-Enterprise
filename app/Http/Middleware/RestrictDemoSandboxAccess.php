<?php

namespace App\Http\Middleware;

use App\Support\SandboxWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps sandbox visitors inside the CRM tour. Does not change Admin/Seller/Client
 * controllers — it only intercepts organization, billing, export, and live-key routes.
 */
class RestrictDemoSandboxAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('tenant.login.post') && SandboxWorkspace::isProtectedCrmEmail($request->input('email'))) {
            return back()->with('error', 'The sandbox workspace is not an organization login. Use /sandbox to tour the CRM.');
        }

        if ($request->routeIs('admin.login.post', 'seller.login.post', 'client.login.post')
            && SandboxWorkspace::isProtectedCrmEmail($request->input('email'))) {
            return redirect()
                ->route('sandbox.login')
                ->with('error', 'Use the sandbox sign-in to tour that workspace. Demo CRM logins are not used from this page.');
        }

        if (! $this->shouldRestrict($request)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($this->isBlocked($routeName)) {
            if ($request->expectsJson()) {
                abort(403, 'Sandbox accounts cannot use organization billing, exports, API keys, or live workspace settings.');
            }

            return redirect()
                ->route($this->fallbackRoute($request))
                ->with(
                    'error',
                    'Sandbox accounts cannot open organization billing, team, domain, export, or API settings. Start a real trial for a private workspace — demo data will not come with you.'
                );
        }

        return $next($request);
    }

    private function shouldRestrict(Request $request): bool
    {
        return SandboxWorkspace::currentIsSandbox();
    }

    private function fallbackRoute(Request $request): string
    {
        $role = (string) $request->session()->get('demo_sandbox_role', 'admin');

        return match ($role) {
            'seller' => 'seller.index.get',
            'client' => 'client.index.get',
            default  => 'admin.index.get',
        };
    }

    private function isBlocked(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        if (str_starts_with($routeName, 'admin.org.')) {
            return true;
        }

        if (str_starts_with($routeName, 'tenant.')) {
            $allowed = [
                'tenant.register.form',
                'tenant.register.store',
                'tenant.register.success',
                'tenant.login',
                'tenant.login.post',
                'tenant.logout',
                'tenant.verify-email',
                'tenant.verify-email.resend',
            ];

            return ! in_array($routeName, $allowed, true);
        }

        $blocked = [
            'admin.account-keys.post',
            'admin.account-keys-update',
            'admin.import.index',
            'admin.import.sample',
            'admin.import.guide',
            'admin.import.store',
            'admin.import.map',
            'admin.import.map.save',
            'admin.import.preview',
            'admin.import.commit',
            'admin.import.show',
            'admin.import.rollback',
            'admin.domain-scripts.post',
            'admin.domain-scripts-update',
            'admin.domain-scripts.test-lead',
            'seller.domain-script.get',
        ];

        if (str_starts_with($routeName, 'admin.import.')) {
            return true;
        }

        return in_array($routeName, $blocked, true);
    }
}
