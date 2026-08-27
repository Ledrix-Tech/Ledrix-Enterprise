<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\Central\ImpersonationService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class ImpersonationController extends Controller
{
    public function start(int $id, ImpersonationService $impersonation)
    {
        $tenant = Tenant::query()->findOrFail($id);
        $actor = Auth::guard('super_admin')->user();

        if (! $actor) {
            return redirect()
                ->route('super-admin.login.get')
                ->with('error', 'You must be signed in as a super admin to impersonate.');
        }

        try {
            $impersonation->start($tenant, $actor);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.index.get')
            ->with('success', 'You are now logged in as the tenant CRM admin. All actions are audited.');
    }

    public function stop(ImpersonationService $impersonation)
    {
        $tenantId = (int) session(ImpersonationService::SESSION_TENANT_ID, 0);

        try {
            $impersonation->stop();
        } catch (InvalidArgumentException $e) {
            return $this->fallbackAfterFailedStop($e->getMessage());
        } catch (RuntimeException $e) {
            return redirect()
                ->route('super-admin.login.get')
                ->with('error', $e->getMessage());
        }

        if ($tenantId > 0) {
            return redirect()
                ->route('super-admin.tenant.show', $tenantId)
                ->with('success', 'Impersonation ended. You are back in the super admin panel.');
        }

        return redirect()
            ->route('super-admin.company-profile.get')
            ->with('success', 'Impersonation ended. You are back in the super admin panel.');
    }

    private function fallbackAfterFailedStop(string $message)
    {
        if (Auth::guard('super_admin')->check()) {
            return redirect()
                ->route('super-admin.company-profile.get')
                ->with('error', $message);
        }

        return redirect()
            ->route('super-admin.login.get')
            ->with('error', $message);
    }
}
