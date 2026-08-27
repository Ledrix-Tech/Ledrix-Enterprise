<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Seller;
use App\Services\Security\TotpService;
use App\Services\Tenant\SubscriptionAccessService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerTwoFactorController extends Controller
{
    public function showSetup(TotpService $totp)
    {
        /** @var Seller $seller */
        $seller = Auth::guard('seller')->user();

        if ($seller->two_factor_secret) {
            return view('sellers.pages.auth.two-factor-manage');
        }

        $secret = $totp->generateSecret();
        session(['seller_2fa_setup_secret' => $secret]);

        return view('sellers.pages.auth.two-factor-setup', [
            'secret' => $secret,
            'uri'    => $totp->provisioningUri($secret, $seller->email, 'Ledrix Seller'),
        ]);
    }

    public function enable(Request $request, TotpService $totp)
    {
        /** @var Seller $seller */
        $seller = Auth::guard('seller')->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = (string) session('seller_2fa_setup_secret', '');
        if ($secret === '' || ! $totp->verify($secret, $validated['code'])) {
            return back()->with('error', 'Invalid authenticator code. Try again.');
        }

        $recovery = $totp->generateRecoveryCodes();

        $seller->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes($recovery)),
        ])->save();

        session()->forget('seller_2fa_setup_secret');

        AuditLog::record(
            'seller.2fa_enabled',
            $seller->tenant_id ? (int) $seller->tenant_id : null,
            'seller',
            $seller->id,
            $seller->name,
        );

        return view('sellers.pages.auth.two-factor-recovery', [
            'codes' => $recovery,
        ]);
    }

    public function disable(Request $request, TotpService $totp)
    {
        /** @var Seller $seller */
        $seller = Auth::guard('seller')->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $seller->two_factor_secret || ! $this->passesTwoFactor($seller, $validated['code'], $totp)) {
            return back()->with('error', 'Invalid code.');
        }

        $seller->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        AuditLog::record(
            'seller.2fa_disabled',
            $seller->tenant_id ? (int) $seller->tenant_id : null,
            'seller',
            $seller->id,
            $seller->name,
        );

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    public function challenge()
    {
        if (! session('seller_2fa_pending_id')) {
            return redirect()->route('seller.login.get');
        }

        return view('sellers.pages.auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $sellerId = (int) session('seller_2fa_pending_id', 0);
        $seller = Seller::withoutGlobalScopes()->find($sellerId);

        if (! $seller) {
            session()->forget(['seller_2fa_pending_id', 'seller_2fa_remember']);

            return redirect()->route('seller.login.get')->with('error', 'Session expired. Sign in again.');
        }

        if (! $this->passesTwoFactor($seller, $validated['code'], $totp)) {
            return back()->with('error', 'Invalid authentication code.');
        }

        Auth::guard('seller')->login($seller, (bool) session('seller_2fa_remember', false));
        session()->forget(['seller_2fa_pending_id', 'seller_2fa_remember']);
        $request->session()->regenerate();

        if ($seller->tenant_id) {
            session(['tenant_id' => $seller->tenant_id]);
            TenantContext::set($seller->tenant_id);

            $tenant = Tenant::query()->find($seller->tenant_id);
            if ($tenant && ! app(SubscriptionAccessService::class)->canUseCrm($tenant)) {
                Auth::guard('seller')->logout();
                session()->forget(['tenant_id', 'role']);

                return redirect()
                    ->route('seller.login.get')
                    ->with(
                        'error',
                        'Your organization subscription is not active. Ask your administrator to renew billing in Admin → Organization → Billing.'
                    );
            }
        }

        session(['role' => $seller->is_seller]);

        AuditLog::record(
            'seller.login',
            $seller->tenant_id ? (int) $seller->tenant_id : null,
            'seller',
            $seller->id,
            $seller->name,
            ['description' => 'Login with 2FA'],
        );

        return redirect()
            ->intended(route('seller.index.get'))
            ->with('success', 'Welcome ' . $seller->name);
    }

    private function passesTwoFactor(Seller $seller, string $code, TotpService $totp): bool
    {
        if ($seller->two_factor_secret && $totp->verify((string) $seller->two_factor_secret, $code)) {
            return true;
        }

        $hashed = json_decode((string) $seller->two_factor_recovery_codes, true);
        if (! is_array($hashed)) {
            return false;
        }

        $remaining = $totp->consumeRecoveryCode($hashed, $code);
        if ($remaining === null) {
            return false;
        }

        $seller->forceFill([
            'two_factor_recovery_codes' => json_encode($remaining),
        ])->save();

        return true;
    }
}
