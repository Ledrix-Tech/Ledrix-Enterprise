<?php

namespace App\Http\Middleware;

use App\Services\Central\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows routes that exit impersonation without requiring the super_admin guard.
 */
class EnsureActiveImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(ImpersonationService::class)->isActive()) {
            abort(403, 'No active impersonation session.');
        }

        return $next($request);
    }
}
