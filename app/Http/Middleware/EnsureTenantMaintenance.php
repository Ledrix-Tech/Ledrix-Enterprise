<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantMaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * F-16: When a system announcement has blocks_crm, pause Admin/Seller CRM
 * (distinct from artisan down / section maintenance flags).
 */
class EnsureTenantMaintenance
{
    public function __construct(
        private readonly TenantMaintenanceService $maintenance,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        $announcement = $this->maintenance->blockingAnnouncement();
        if ($announcement === null) {
            return $next($request);
        }

        return response()->view('errors.tenant-maintenance', [
            'title'   => $announcement->title,
            'message' => $announcement->message,
        ], 503);
    }

    private function isExempt(Request $request): bool
    {
        $name = (string) $request->route()?->getName();

        if ($name === '') {
            return false;
        }

        return in_array($name, [
            'admin.logout',
            'seller.logout',
            'admin.impersonation.stop',
            'super-admin.impersonation.stop',
        ], true);
    }
}
