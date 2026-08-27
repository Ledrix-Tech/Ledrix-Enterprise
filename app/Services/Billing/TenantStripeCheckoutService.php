<?php

namespace App\Services\Billing;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Subscription;

class TenantStripeCheckoutService
{
    public function isConfigured(): bool
    {
        return app(PlatformBillingSettingsService::class)->isReady('stripe');
    }

    public function createCheckoutUrl(
        Tenant $tenant,
        TenantPayment $payment,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
    ): string {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe is not configured.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $plan = $payment->plan ?? $tenant->plan;
        $currency = strtolower($payment->currency ?: 'usd');
        $amountMinor = BillingMoney::toMinorUnits((float) $payment->amount, $currency);
        $cycle = strtolower((string) ($payment->billing_cycle ?? 'monthly'));
        $interval = $cycle === 'yearly' ? 'year' : 'month';
        $isUpgrade = ($payment->order_type ?? '') === 'upgrade';
        $mode = $isUpgrade ? 'payment' : 'subscription';

        // Stripe minimum ~0.50 in major currencies (skip strict check for zero-decimal).
        $minMinor = in_array(strtoupper($currency), config('billing.zero_decimal_currencies', []), true) ? 1 : 50;
        if ($amountMinor < $minMinor) {
            throw new RuntimeException('Payment amount is too low for Stripe checkout.');
        }

        $priceData = [
            'currency'     => $currency,
            'unit_amount'  => $amountMinor,
            'product_data' => [
                'name'        => 'Ledrix CRM — '.($plan?->name ?? 'Subscription'),
                'description' => $isUpgrade
                    ? 'Plan upgrade (prorated)'
                    : (ucfirst($cycle).' subscription'),
            ],
        ];

        if (! $isUpgrade) {
            $priceData['recurring'] = ['interval' => $interval];
        }

        $params = [
            'mode'                => $mode,
            'client_reference_id' => (string) $payment->id,
            'line_items'          => [[
                'price_data' => $priceData,
                'quantity'   => 1,
            ]],
            'metadata' => [
                'tenant_id'         => (string) $tenant->id,
                'tenant_payment_id' => (string) $payment->id,
                'reference'         => $payment->transaction_id,
                'membership_id'     => (string) $payment->membership_id,
                'order_type'        => (string) ($payment->order_type ?? 'renewal'),
            ],
            'success_url' => $successUrl ?: (route('tenant.billing.stripe.success').'?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'  => $cancelUrl ?: (route('tenant.billing').'?cancelled=1'),
        ];

        if (! $isUpgrade) {
            $params['subscription_data'] = [
                'metadata' => [
                    'tenant_id'         => (string) $tenant->id,
                    'tenant_payment_id' => (string) $payment->id,
                    'membership_id'     => (string) $payment->membership_id,
                ],
            ];
        }

        if ($tenant->stripe_customer_id) {
            $params['customer'] = $tenant->stripe_customer_id;
        } else {
            $params['customer_email'] = $tenant->email;
        }

        $session = Session::create($params);

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], [
                'stripe_checkout_session_id' => $session->id,
                'stripe_mode'                => $mode,
            ]),
        ]);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return $session->url;
    }

    /**
     * @return array{payment_id: ?int, subscription_id: ?string, customer_id: ?string, paid: bool}
     */
    public function inspectSession(string $sessionId): array
    {
        if (! $this->isConfigured()) {
            return ['payment_id' => null, 'subscription_id' => null, 'customer_id' => null, 'paid' => false];
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $session = Session::retrieve($sessionId);

        $paid = in_array($session->payment_status, ['paid', 'no_payment_required'], true)
            || ($session->status === 'complete');

        return [
            'payment_id'      => isset($session->metadata['tenant_payment_id'])
                ? (int) $session->metadata['tenant_payment_id']
                : null,
            'subscription_id' => is_string($session->subscription ?? null)
                ? $session->subscription
                : ($session->subscription->id ?? null),
            'customer_id'     => is_string($session->customer ?? null)
                ? $session->customer
                : ($session->customer->id ?? null),
            'paid'            => $paid,
        ];
    }

    public function verifyAndGetPaymentId(string $sessionId): ?int
    {
        $info = $this->inspectSession($sessionId);

        return $info['paid'] ? $info['payment_id'] : null;
    }

    public function syncSubscriptionIdsFromSession(TenantPayment $payment, string $sessionId): void
    {
        $info = $this->inspectSession($sessionId);
        if (! $info['paid']) {
            return;
        }

        $payment->loadMissing(['tenant', 'membership']);

        if ($info['customer_id'] && $payment->tenant) {
            $payment->tenant->forceFill(['stripe_customer_id' => $info['customer_id']])->save();
        }

        if ($info['subscription_id'] && $payment->membership) {
            $payment->membership->forceFill([
                'stripe_subscription_id' => $info['subscription_id'],
            ])->save();

            $payment->tenant?->forceFill(['auto_renew' => true])->save();
        }

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], array_filter([
                'stripe_subscription_id' => $info['subscription_id'],
                'stripe_customer_id'     => $info['customer_id'],
            ])),
        ]);
    }

    public function cancelAtPeriodEnd(TenantMembership $membership, bool $cancel = true): void
    {
        if (! $membership->stripe_subscription_id || ! $this->isConfigured()) {
            return;
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        Subscription::update($membership->stripe_subscription_id, [
            'cancel_at_period_end' => $cancel,
        ]);
    }

    /**
     * F-26: Stripe Customer Portal (payment methods, invoices, optional cancel).
     */
    public function createBillingPortalUrl(Tenant $tenant, string $returnUrl): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe is not configured.');
        }

        if (! $tenant->stripe_customer_id) {
            throw new RuntimeException('No Stripe customer on file. Complete a Stripe checkout first.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = \Stripe\BillingPortal\Session::create([
            'customer'   => $tenant->stripe_customer_id,
            'return_url' => $returnUrl,
        ]);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a billing portal URL.');
        }

        return $session->url;
    }
}
