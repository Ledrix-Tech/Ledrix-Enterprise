<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\Tenant\TenantDataErasureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class TenantDataErasureController extends Controller
{
    /**
     * SA-initiated irreversible GDPR erasure (confirm phrase required).
     */
    public function eraseNow(Request $request, int $tenantId, TenantDataErasureService $erasure)
    {
        $tenant = Tenant::withTrashed()->findOrFail($tenantId);

        $validated = $request->validate([
            'reason'       => ['required', 'string', 'min:4', 'max:1000'],
            'confirm_text' => ['required', 'string'],
        ]);

        if (! TenantDataErasureService::confirmPhraseMatches($validated['confirm_text'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'confirm_text' => 'Type '.TenantDataErasureService::CONFIRM_PHRASE.' exactly to confirm erasure.',
                ]);
        }

        $sa = Auth::guard('super_admin')->user();

        try {
            $erasure->erase(
                $tenant,
                $validated['reason'],
                'super_admin',
                $sa?->id,
                $sa?->name,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('super-admin.company-profile.get')
            ->with('success', 'Tenant GDPR erasure completed. PII anonymized; billing history retained.');
    }
}
