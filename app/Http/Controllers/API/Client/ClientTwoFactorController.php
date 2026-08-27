<?php

namespace App\Http\Controllers\API\Client;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Client;
use App\Services\Security\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientTwoFactorController extends Controller
{
    public function showSetup(TotpService $totp)
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        if ($client->two_factor_secret) {
            return view('clients.pages.auth.two-factor-manage');
        }

        $secret = $totp->generateSecret();
        session(['client_2fa_setup_secret' => $secret]);

        return view('clients.pages.auth.two-factor-setup', [
            'secret' => $secret,
            'uri'    => $totp->provisioningUri($secret, $client->email, 'Ledrix Client'),
        ]);
    }

    public function enable(Request $request, TotpService $totp)
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = (string) session('client_2fa_setup_secret', '');
        if ($secret === '' || ! $totp->verify($secret, $validated['code'])) {
            return back()->with('error', 'Invalid authenticator code. Try again.');
        }

        $recovery = $totp->generateRecoveryCodes();

        $client->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes($recovery)),
        ])->save();

        session()->forget('client_2fa_setup_secret');

        AuditLog::record(
            'client.2fa_enabled',
            $client->tenant_id ? (int) $client->tenant_id : null,
            'client',
            $client->id,
            $client->name,
        );

        return view('clients.pages.auth.two-factor-recovery', [
            'codes' => $recovery,
        ]);
    }

    public function disable(Request $request, TotpService $totp)
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $client->two_factor_secret || ! $this->passesTwoFactor($client, $validated['code'], $totp)) {
            return back()->with('error', 'Invalid code.');
        }

        $client->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        AuditLog::record(
            'client.2fa_disabled',
            $client->tenant_id ? (int) $client->tenant_id : null,
            'client',
            $client->id,
            $client->name,
        );

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    public function challenge()
    {
        if (! session('client_2fa_pending_id')) {
            return redirect()->route('client.login.get');
        }

        return view('clients.pages.auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $clientId = (int) session('client_2fa_pending_id', 0);
        $client = Client::withoutGlobalScopes()->find($clientId);

        if (! $client) {
            session()->forget(['client_2fa_pending_id', 'client_2fa_remember']);

            return redirect()->route('client.login.get')->with('error', 'Session expired. Sign in again.');
        }

        if (! $this->passesTwoFactor($client, $validated['code'], $totp)) {
            return back()->with('error', 'Invalid authentication code.');
        }

        Auth::guard('client')->login($client, (bool) session('client_2fa_remember', false));
        session()->forget(['client_2fa_pending_id', 'client_2fa_remember']);
        $request->session()->regenerate();

        session([
            'role'      => 'client',
            'tenant_id' => $client->tenant_id,
        ]);

        AuditLog::record(
            'client.login',
            $client->tenant_id ? (int) $client->tenant_id : null,
            'client',
            $client->id,
            $client->name,
            ['description' => 'Login with 2FA'],
        );

        if (session()->has('redirect_to_brief')) {
            $redirectUrl = session('redirect_to_brief');
            session()->forget('redirect_to_brief');

            return redirect($redirectUrl)->with('success', 'Login Successfully! You are now redirected to the brief form.');
        }

        return redirect()
            ->intended(route('client.index.get'))
            ->with('success', 'Login Successfully!');
    }

    private function passesTwoFactor(Client $client, string $code, TotpService $totp): bool
    {
        if ($client->two_factor_secret && $totp->verify((string) $client->two_factor_secret, $code)) {
            return true;
        }

        $hashed = json_decode((string) $client->two_factor_recovery_codes, true);
        if (! is_array($hashed)) {
            return false;
        }

        $remaining = $totp->consumeRecoveryCode($hashed, $code);
        if ($remaining === null) {
            return false;
        }

        $client->forceFill([
            'two_factor_recovery_codes' => json_encode($remaining),
        ])->save();

        return true;
    }
}
