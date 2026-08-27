<?php

namespace App\Services\Billing;

use App\Models\Central\AuditLog;
use App\Models\Central\TenantPayment;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Stripe\Refund;
use Stripe\Stripe;

class RefundTenantPaymentService
{
    public function __construct(
        private readonly ReferralRewardService $credits,
        private readonly PlatformBillingSettingsService $platformBilling,
    ) {}

    /**
     * @return array{payment: TenantPayment, credit_applied: float, stripe_refunded: bool}
     */
    public function refund(
        TenantPayment $payment,
        ?float $amount = null,
        bool $creditBalance = true,
        bool $attemptStripe = true,
        ?string $reason = null,
    ): array {
        if ($payment->status !== 'paid') {
            throw new RuntimeException('Only paid payments can be refunded.');
        }

        $payment->loadMissing(['tenant', 'invoice']);
        $max = round((float) $payment->amount - (float) ($payment->refunded_amount ?? 0), 2);
        $refundAmount = $amount !== null ? round(min($max, max(0, $amount)), 2) : $max;

        if ($refundAmount <= 0) {
            throw new RuntimeException('Nothing left to refund on this payment.');
        }

        $stripeRefunded = false;
        if ($attemptStripe && $payment->gateway === 'stripe' && $this->platformBilling->isReady('stripe')) {
            $pi = $payment->payload['payment_intent_id']
                ?? $payment->payload['stripe_payment_intent']
                ?? null;
            if (is_string($pi) && $pi !== '') {
                Stripe::setApiKey(config('services.stripe.secret'));
                Refund::create([
                    'payment_intent' => $pi,
                    'amount'         => (int) round($refundAmount * 100),
                    'reason'         => 'requested_by_customer',
                    'metadata'       => [
                        'tenant_payment_id' => (string) $payment->id,
                        'note'              => $reason ?? '',
                    ],
                ]);
                $stripeRefunded = true;
            }
        }

        $newRefunded = round((float) ($payment->refunded_amount ?? 0) + $refundAmount, 2);
        $full = $newRefunded + 0.001 >= (float) $payment->amount;

        $payment->update([
            'refunded_amount' => $newRefunded,
            'refund_status'   => $full ? 'full' : 'partial',
            'status'          => $full ? 'refunded' : 'paid',
            'payload'         => array_merge($payment->payload ?? [], [
                'last_refund_at'     => now()->toDateTimeString(),
                'last_refund_amount' => $refundAmount,
                'last_refund_reason' => $reason,
                'stripe_refunded'    => $stripeRefunded,
            ]),
        ]);

        if ($payment->invoice) {
            $payment->invoice->update([
                'notes' => trim(($payment->invoice->notes ?? '').' Refund '.$payment->currency.' '.$refundAmount.($reason ? " ({$reason})" : '').'.'),
            ]);
        }

        $creditApplied = 0.0;
        if ($creditBalance && $payment->tenant && ! $stripeRefunded) {
            $this->credits->addBillingCredit($payment->tenant, (string) $payment->currency, $refundAmount);
            $creditApplied = $refundAmount;
        }

        $actor = Auth::guard('super_admin')->user();
        AuditLog::record(
            action: 'subscription.payment_refunded',
            tenantId: (int) $payment->tenant_id,
            actorType: $actor ? 'super_admin' : 'system',
            actorId: $actor?->id,
            actorName: $actor?->name ?? 'System',
            context: [
                'subject_type' => 'tenant_payment',
                'subject_id'   => $payment->id,
                'description'  => 'Subscription payment refund recorded',
                'after'        => [
                    'amount'          => $refundAmount,
                    'credit_applied'  => $creditApplied,
                    'stripe_refunded' => $stripeRefunded,
                ],
            ]
        );

        return [
            'payment'         => $payment->fresh(),
            'credit_applied'  => $creditApplied,
            'stripe_refunded' => $stripeRefunded,
        ];
    }
}
