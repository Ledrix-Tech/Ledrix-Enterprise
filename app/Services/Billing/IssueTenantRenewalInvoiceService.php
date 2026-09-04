<?php

namespace App\Services\Billing;

use App\Mail\TenantSubscriptionDueMail;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * F-04: Issue a modern invoice + email billing link (replaces legacy off-session Stripe renewal).
 */
class IssueTenantRenewalInvoiceService
{
    public function __construct(
        private readonly CreateSubscriptionInvoiceService $invoiceService,
        private readonly SubscriptionAccessService $accessService,
        private readonly PlatformBillingSettingsService $platformBilling,
    ) {}

    /**
     * @return array{payment: \App\Models\Central\TenantPayment, invoice: \App\Models\Central\TenantInvoice, gateway: string, currency: string}
     */
    public function issueAndNotify(Tenant $tenant): array
    {
        $tenant->load(['plan', 'activeMembership']);

        if (! $tenant->plan_id) {
            throw new RuntimeException('This tenant has no plan assigned.');
        }

        $currency = TenantBillingRegion::syncPreferredCurrency($tenant);
        $isPk = $currency === TenantBillingRegion::CURRENCY_PKR;

        if ($isPk) {
            if ($this->platformBilling->isReady('meezan')) {
                $gateway = 'bank_transfer';
            } else {
                throw new RuntimeException('No PKR payment method is enabled. Enable Meezan in Payment Accounts.');
            }
        } else {
            if (! $this->platformBilling->isReady('stripe')) {
                throw new RuntimeException('Stripe is not enabled. Enable it in Payment Accounts for international renewals.');
            }
            $gateway = 'stripe';
        }

        $orderType = $this->accessService->paymentOrderType($tenant);
        if (! in_array($orderType, ['renewal', 'new'], true)) {
            $orderType = 'renewal';
        }

        $result = $this->invoiceService->createAndNotify($tenant, $gateway, $currency, $orderType);

        $actor = Auth::guard('super_admin')->user();
        AuditLog::record(
            action: 'subscription.renewal_invoice_issued',
            tenantId: (int) $tenant->id,
            actorType: $actor ? 'super_admin' : 'system',
            actorId: $actor?->id,
            actorName: $actor?->name ?? 'System',
            context: [
                'subject_type' => 'tenant_invoice',
                'subject_id'   => $result['invoice']->id,
                'description'  => 'Renewal invoice issued; tenant notified to pay via Organization Billing.',
                'after'        => [
                    'gateway'  => $gateway,
                    'currency' => $currency,
                    'invoice'  => $result['invoice']->invoice_number,
                ],
            ]
        );

        return [
            'payment'  => $result['payment'],
            'invoice'  => $result['invoice'],
            'gateway'  => $gateway,
            'currency' => $currency,
        ];
    }
}
