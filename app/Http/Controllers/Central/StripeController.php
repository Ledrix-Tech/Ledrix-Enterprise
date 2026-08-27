<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\Billing\IssueTenantRenewalInvoiceService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * F-04: Legacy off-session Stripe renewal replaced by invoice + Organization Billing.
 */
class StripeController extends Controller
{
    public function sendRenewalApproval(Tenant $tenant, IssueTenantRenewalInvoiceService $issuer)
    {
        try {
            $result = $issuer->issueAndNotify($tenant);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Renewal invoice failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Could not issue renewal invoice. Try again or confirm a pending payment manually.');
        }

        return back()->with(
            'success',
            'Renewal invoice '.$result['invoice']->invoice_number.' issued ('.$result['currency'].' via '.$result['gateway'].'). Tenant emailed to pay in Organization Billing.'
        );
    }

    /**
     * Old email links land here — send tenants to the modern billing portal.
     */
    public function approveRenewal(string $token)
    {
        return view('renewal-approved', [
            'company' => null,
            'status'  => 'expired',
            'message' => 'This renewal link is no longer used. Sign in to your organization portal and open Billing to pay your invoice.',
        ]);
    }
}
