<?php

namespace App\Services\Billing;

use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles Stripe Subscription lifecycle events after the first Checkout.
 */
class StripeSubscriptionLifecycleService
{
    public function __construct(
        private readonly ActivateTenantSubscriptionService $activation,
        private readonly CreateSubscriptionInvoiceService $invoices,
    ) {}

    public function bindCheckoutSession(TenantPayment $payment, object $session): void
    {
        $subscriptionId = is_string($session->subscription ?? null)
            ? $session->subscription
            : ($session->subscription->id ?? null);
        $customerId = is_string($session->customer ?? null)
            ? $session->customer
            : ($session->customer->id ?? null);

        $payment->loadMissing(['tenant', 'membership']);

        if ($customerId && $payment->tenant) {
            $payment->tenant->forceFill([
                'stripe_customer_id' => $customerId,
                'auto_renew'         => true,
            ])->save();
        }

        if ($subscriptionId && $payment->membership) {
            $payment->membership->forceFill([
                'stripe_subscription_id' => $subscriptionId,
                'cancelled_at'           => null,
                'cancel_reason'          => null,
            ])->save();
        }

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], array_filter([
                'stripe_subscription_id' => $subscriptionId,
                'stripe_customer_id'     => $customerId,
                'stripe_session_id'      => $session->id ?? null,
            ])),
        ]);
    }

    /**
     * Recurring Stripe invoice.paid → extend Ledrix membership.
     */
    public function handleInvoicePaid(object $stripeInvoice): void
    {
        $billingReason = (string) ($stripeInvoice->billing_reason ?? '');
        // First period is activated via checkout.session.completed.
        if ($billingReason === 'subscription_create') {
            return;
        }

        $subscriptionId = is_string($stripeInvoice->subscription ?? null)
            ? $stripeInvoice->subscription
            : ($stripeInvoice->subscription->id ?? null);

        if (! $subscriptionId) {
            return;
        }

        $membership = TenantMembership::query()
            ->with(['tenant.plan'])
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if (! $membership?->tenant) {
            Log::warning('Stripe invoice.paid: membership not found', ['subscription' => $subscriptionId]);

            return;
        }

        $amountPaid = isset($stripeInvoice->amount_paid)
            ? round(((int) $stripeInvoice->amount_paid) / 100, 2)
            : (float) $membership->amount;

        if ($amountPaid <= 0) {
            return;
        }

        $existing = TenantPayment::query()
            ->where('gateway', 'stripe')
            ->where('payload->stripe_invoice_id', $stripeInvoice->id ?? '')
            ->first();

        if ($existing?->status === 'paid') {
            return;
        }

        $tenant = $membership->tenant;
        $currency = strtoupper((string) ($stripeInvoice->currency ?? $membership->currency ?? 'usd'));

        $payment = TenantPayment::query()->create([
            'tenant_id'      => $tenant->id,
            'membership_id'  => $membership->id,
            'plan_id'        => $membership->plan_id ?? $tenant->plan_id,
            'transaction_id' => 'STRIPE-'.Str::upper(Str::random(10)),
            'gateway'        => 'stripe',
            'order_type'     => 'renewal',
            'renewed_by'     => 'stripe_subscription',
            'billing_cycle'  => $membership->billing_cycle ?? 'monthly',
            'amount'         => $amountPaid,
            'currency'       => $currency,
            'status'         => 'pending',
            'payload'        => [
                'stripe_invoice_id'      => $stripeInvoice->id ?? null,
                'stripe_subscription_id' => $subscriptionId,
                'billing_reason'         => $billingReason,
            ],
        ]);

        TenantInvoice::query()->create([
            'tenant_id'      => $tenant->id,
            'membership_id'  => $membership->id,
            'payment_id'     => $payment->id,
            'invoice_number' => TenantInvoice::nextNumber(),
            'plan_name'      => $tenant->plan?->name,
            'billing_cycle'  => $membership->billing_cycle ?? 'monthly',
            'amount'         => $amountPaid,
            'currency'       => $currency,
            'tax_amount'     => 0,
            'total_amount'   => $amountPaid,
            'status'         => 'issued',
            'issued_at'      => now(),
            'due_at'         => now(),
            'notes'          => 'Stripe subscription renewal',
        ]);

        $this->activation->activate(
            $payment->fresh(['tenant.plan', 'membership', 'invoice', 'plan']),
            renewedBy: 'stripe_subscription',
            payloadMerge: ['stripe_invoice_id' => $stripeInvoice->id ?? null],
        );
    }

    public function handleSubscriptionUpdated(object $subscription): void
    {
        $membership = TenantMembership::query()
            ->where('stripe_subscription_id', $subscription->id)
            ->first();

        if (! $membership) {
            return;
        }

        $cancelAtPeriodEnd = (bool) ($subscription->cancel_at_period_end ?? false);
        $status = (string) ($subscription->status ?? '');

        if ($cancelAtPeriodEnd || $status === 'canceled') {
            $membership->forceFill([
                'cancelled_at'  => $membership->cancelled_at ?? now(),
                'cancel_reason' => $membership->cancel_reason ?? 'Cancelled via Stripe',
            ])->save();
            $membership->tenant?->forceFill(['auto_renew' => false])->save();
        } elseif ($status === 'active') {
            $membership->forceFill([
                'cancelled_at'  => null,
                'cancel_reason' => null,
            ])->save();
            $membership->tenant?->forceFill(['auto_renew' => true])->save();
        }
    }

    public function handleSubscriptionDeleted(object $subscription): void
    {
        $membership = TenantMembership::query()
            ->where('stripe_subscription_id', $subscription->id)
            ->first();

        if (! $membership) {
            return;
        }

        $membership->forceFill([
            'cancelled_at'  => $membership->cancelled_at ?? now(),
            'cancel_reason' => 'Stripe subscription deleted',
        ])->save();

        $membership->tenant?->forceFill(['auto_renew' => false])->save();
    }

    public function handleInvoicePaymentFailed(object $stripeInvoice): void
    {
        $subscriptionId = is_string($stripeInvoice->subscription ?? null)
            ? $stripeInvoice->subscription
            : ($stripeInvoice->subscription->id ?? null);

        if (! $subscriptionId) {
            return;
        }

        $membership = TenantMembership::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if (! $membership || $membership->status === 'past_due') {
            return;
        }

        if ($membership->status === 'active') {
            $membership->forceFill(['status' => 'past_due'])->save();
        }
    }
}
