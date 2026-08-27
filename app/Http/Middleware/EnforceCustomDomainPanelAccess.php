<?php

namespace App\Http\Middleware;

use App\Support\TenantHostResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * F-27: On a tenant custom domain, block platform-only routes and send / to CRM login.
 */
class EnforceCustomDomainPanelAccess
{
    /** @var list<string> */
    private const PLATFORM_ONLY_PREFIXES = [
        'super-admin',
        'register',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! TenantHostResolver::isCustomDomainHost($request)) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        foreach (self::PLATFORM_ONLY_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                abort(404);
            }
        }

        if ($path === '' && ! $request->is('admin/*', 'seller/*', 'client/*', 'tenant-profile/*', 'sign-in', 'sign-in/*')) {
            return redirect()->route('admin.login.get');
        }

        return $next($request);
    }
}
