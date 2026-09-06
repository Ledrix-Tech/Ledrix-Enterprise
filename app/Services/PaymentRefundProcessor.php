<?php

namespace App\Services;

use App\Models\AccountKey;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Support\PpcWebhookVerifier;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentRefundProcessor
{
    public function __construct(
        private PpcWebhookVerifier $webhookVerifier,
        private TenantFeatureService $tenantFeatures,
    ) {}

    public function processStripeRefundEvent(\Stripe\Event $event): void
    {
        if ($event->type === 'charge.refunded') {
            $charge          = $event->data->object;
            $paymentIntentId = $charge->payment_intent ?? null;

            if (! $paymentIntentId) {
                return;
            }

            $payment = Payment::where('provider', 'stripe')
                ->where('provider_payment_intent_id', $paymentIntentId)
                ->first();

            if (! $payment) {
                return;
            }

            $refundAmount = (int) ($charge->amount_refunded ?? 0);
            $this->applyRefund($payment, $refundAmount, 'stripe', $event->toArray());

            return;
        }

        if ($event->type === 'charge.refund.updated') {
            $refund   = $event->data->object;
            $chargeId = $refund->charge ?? null;

            if (! $chargeId) {
                return;
            }

            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $charge          = \Stripe\Charge::retrieve($chargeId);
            $paymentIntentId = $charge->payment_intent ?? null;

            if (! $paymentIntentId) {
                return;
            }

            $payment = Payment::where('provider', 'stripe')
                ->where('provider_payment_intent_id', $paymentIntentId)
                ->first();

            if (! $payment) {
                return;
            }

            $refundAmount = (int) ($charge->amount_refunded ?? $refund->amount ?? 0);
            $this->applyRefund($payment, $refundAmount, 'stripe', $event->toArray());
        }
    }

    public function processStripeDisputeEvent(\Stripe\Event $event): void
    {
        $type = (string) ($event->type ?? '');

        if (in_array($type, ['radar.early_fraud_warning', 'charge.dispute.funds_withdrawn', 'charge.dispute.funds_reinstated'], true)) {
            Log::info('Stripe dispute-related event logged without seller clawback', [
                'event_id' => $event->id ?? null,
                'type'     => $type,
            ]);

            return;
        }

        $dispute = $event->data->object;
        $payment = $this->findStripePaymentForDispute($dispute);

        if (! $payment) {
            Log::warning('Stripe dispute event: payment not found for tenant/brand', [
                'event_id' => $event->id ?? null,
                'type'     => $type,
                'charge'   => $dispute->charge ?? null,
            ]);

            return;
        }

        if (! $this->paymentMatchesTenantBrand($payment, 'stripe', isset($event->account) ? (string) $event->account : null)) {
            return;
        }

        if (! $this->chargebackTrackingAllowed($payment)) {
            return;
        }

        $amount = (int) ($dispute->amount ?? 0);
        $status = (string) ($dispute->status ?? '');
        $disputeId = (string) ($dispute->id ?? '');
        $payload = $event->toArray();

        if ($type === 'charge.dispute.closed') {
            if ($status === 'lost') {
                $this->applyChargebackLost($payment, $amount, 'stripe', $payload, $disputeId);
            } elseif ($status === 'won') {
                $this->markDisputeWon($payment, 'stripe', $payload, $disputeId);
            } else {
                Log::info('Stripe dispute closed without a final lost/won ruling — no seller clawback', [
                    'status'     => $status,
                    'payment_id' => $payment->id,
                    'dispute_id' => $disputeId,
                ]);
            }

            return;
        }

        if (in_array($type, ['charge.dispute.created', 'charge.dispute.updated'], true)) {
            $stage = $type === 'charge.dispute.created' ? 'created' : 'updated';
            $this->markDisputeOpen($payment, $amount, 'stripe', $payload, $stage, $disputeId);
        }
    }

    /** @deprecated Use processStripeRefundEvent */
    public function processStripeRefund(\Stripe\Event $event): void
    {
        $this->processStripeRefundEvent($event);
    }

    /** @deprecated Use processStripeDisputeEvent */
    public function processStripeChargeback(\Stripe\Event $event): void
    {
        $this->processStripeDisputeEvent($event);
    }

    public function processPaypalRefund(array $webhook): void
    {
        $resource = $webhook['resource'] ?? [];
        $captureId = $this->webhookVerifier->extractPaypalCaptureIdFromRefund($resource);
        $refundValue = $resource['amount']['value'] ?? null;

        if (! $captureId || $refundValue === null) {
            Log::warning('PayPal refund webhook missing capture id or amount', [
                'event_type' => $webhook['event_type'] ?? null,
                'resource'   => $resource,
            ]);

            return;
        }

        $refundCents = (int) round((float) $refundValue * 100);

        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'paypal')
            ->where('provider_payment_intent_id', $captureId)
            ->first();

        if (! $payment) {
            Log::warning('PayPal refund webhook: payment not found', ['capture_id' => $captureId]);

            return;
        }

        // Dispute-driven refunds are handled by CUSTOMER.DISPUTE.RESOLVED.
        // Do not also run the voluntary-refund path (would overwrite chargeback
        // status or deduct seller revenue a second time).
        if (! empty($resource['dispute_id']) || $this->alreadyClawedBack($payment, (string) ($resource['dispute_id'] ?? ''))) {
            Log::info('PayPal refund skipped — dispute loss is handled by dispute.resolved, not the refund webhook', [
                'payment_id' => $payment->id,
                'dispute_id' => $resource['dispute_id'] ?? $payment->provider_dispute_id,
            ]);

            return;
        }

        $this->applyRefund($payment, $refundCents, 'paypal', $webhook);
    }

    public function processPaypalDisputeEvent(array $webhook, string $label): void
    {
        $resource = $webhook['resource'] ?? [];
        $txn = $this->paypalDisputedTransactionId($resource);

        if (! $txn) {
            return;
        }

        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'paypal')
            ->where('provider_payment_intent_id', $txn)
            ->first();

        if (! $payment) {
            Log::warning('PayPal dispute event: payment not found for tenant/brand', [
                'event_type' => $label,
                'txn'        => $txn,
            ]);

            return;
        }

        if (! $this->paymentMatchesTenantBrand($payment, 'paypal')) {
            return;
        }

        if (! $this->chargebackTrackingAllowed($payment)) {
            return;
        }

        $disputeId = (string) ($resource['dispute_id'] ?? '');
        $amount = $this->paypalDisputeAmountCents($resource, (int) $payment->amount);

        if ($label === 'CUSTOMER.DISPUTE.RESOLVED') {
            $outcome = $this->paypalDisputeOutcome($resource);

            if (in_array($outcome, ['RESOLVED_BUYER_FAVOUR', 'RESOLVED_BUYER_FAVOR'], true)) {
                $this->applyChargebackLost($payment, $amount, 'paypal', $webhook, $disputeId);
            } elseif (in_array($outcome, ['RESOLVED_SELLER_FAVOUR', 'RESOLVED_SELLER_FAVOR'], true)) {
                $this->markDisputeWon($payment, 'paypal', $webhook, $disputeId);
            } else {
                Log::info('PayPal dispute resolved without buyer/seller favour — no seller clawback', [
                    'outcome'    => $outcome,
                    'payment_id' => $payment->id,
                    'dispute_id' => $disputeId,
                ]);
            }

            return;
        }

        $stage = $label === 'CUSTOMER.DISPUTE.CREATED' ? 'created' : 'updated';
        $this->markDisputeOpen($payment, $amount, 'paypal', $webhook, $stage, $disputeId);
    }

    /** @deprecated Use processPaypalDisputeEvent */
    public function processPaypalChargeback(array $webhook): void
    {
        $this->processPaypalDisputeEvent($webhook, (string) ($webhook['event_type'] ?? 'CUSTOMER.DISPUTE.CREATED'));
    }

    private function applyRefund(Payment $payment, int $refundCents, string $provider, array $raw): void
    {
        DB::transaction(function () use ($payment, $refundCents, $raw, $provider) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            $delta = max(0, $refundCents - (int) $payment->refunded_amount);

            if ($delta <= 0) {
                return;
            }

            $isFull = $refundCents >= (int) $payment->amount;

            $payment->refunded_amount = $refundCents;
            $payment->refund_status   = $isFull ? 'full' : 'partial';
            $payment->status          = $isFull ? 'refunded' : 'partially_refunded';
            $payment->refund_payload  = $raw;
            $payment->save();

            $order->refunded_amount += $delta;
            $order->refund_status = $order->refunded_amount >= (int) $order->unit_amount ? 'full' : 'partial';

            if ($order->refund_status === 'full') {
                $order->status = 'refunded';
            }

            $order->save();

            PaymentLink::where('order_id', $order->id)
                ->update(['is_active_link' => false]);

            NotifyStakeholders::refund($payment, $order, $provider, null);

            Log::info('Refund applied safely', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'delta'      => $delta,
                'full'       => $isFull,
            ]);
        });
    }

    private function markDisputeOpen(Payment $payment, int $disputeAmount, string $provider, array $raw, string $stage, string $disputeId): void
    {
        DB::transaction(function () use ($payment, $disputeAmount, $provider, $raw, $stage, $disputeId) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            if ($payment->dispute_status === 'lost' && $payment->refund_status === 'chargeback') {
                return;
            }

            $payment->provider_dispute_id = $disputeId !== '' ? $disputeId : $payment->provider_dispute_id;
            $payment->dispute_status = 'open';
            $payment->refund_payload = $raw;
            $payment->needs_review = true;
            $payment->save();

            NotifyStakeholders::dispute(
                payment: $payment,
                order: $order,
                provider: $provider,
                stage: $stage,
                reason: $stage === 'created'
                    ? 'A dispute was filed. No seller commission has been deducted, and none will be until the case is finally lost and funds are withdrawn.'
                    : 'The dispute is still open. No seller commission has been deducted.'
            );

            Log::warning('Dispute opened/updated — seller revenue unchanged', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'stage'      => $stage,
                'amount'     => $disputeAmount,
                'dispute_id' => $disputeId,
                'refund_status' => $payment->refund_status,
            ]);
        });
    }

    private function applyChargebackLost(Payment $payment, int $disputeAmount, string $provider, array $raw, string $disputeId): void
    {
        DB::transaction(function () use ($payment, $disputeAmount, $provider, $raw, $disputeId) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            if ($this->alreadyClawedBack($payment, $disputeId)) {
                Log::info('Seller clawback skipped — dispute already processed', [
                    'payment_id' => $payment->id,
                    'dispute_id' => $disputeId,
                ]);

                return;
            }

            $payment->provider_dispute_id = $disputeId !== '' ? $disputeId : $payment->provider_dispute_id;
            $payment->dispute_status = 'lost';
            $payment->refund_status = 'chargeback';
            $payment->status = 'refunded';
            $payment->refund_payload = $raw;
            $payment->save();

            $order->refund_status = 'chargeback';
            $order->status = 'refunded';
            $order->save();

            PaymentLink::where('order_id', $order->id)
                ->update(['is_active_link' => false]);

            NotifyStakeholders::dispute(
                payment: $payment,
                order: $order,
                provider: $provider,
                stage: 'lost',
                reason: 'The dispute was resolved against the agency and funds were withdrawn. Seller commission for this payment has been deducted once.'
            );

            Log::warning('Chargeback applied after final loss', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'stage'      => 'lost',
                'amount'     => $disputeAmount,
                'dispute_id' => $disputeId,
            ]);
        });
    }

    private function markDisputeWon(Payment $payment, string $provider, array $raw, string $disputeId): void
    {
        DB::transaction(function () use ($payment, $provider, $raw, $disputeId) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            $payment->provider_dispute_id = $disputeId !== '' ? $disputeId : $payment->provider_dispute_id;
            $payment->dispute_status = 'won';
            $payment->refund_payload = $raw;
            $payment->needs_review = false;

            if ($payment->refund_status === 'chargeback' && $payment->status === 'succeeded') {
                $payment->refund_status = 'none';
            }

            $payment->save();

            if ($order && $order->refund_status === 'chargeback' && $order->status !== 'refunded') {
                $order->refund_status = 'none';
                $order->save();
            }

            if ($order) {
                NotifyStakeholders::dispute(
                    payment: $payment,
                    order: $order,
                    provider: $provider,
                    stage: 'won',
                    reason: 'The dispute was resolved in the agency’s favor. No seller commission was deducted.'
                );
            }

            Log::info('Dispute won — no seller clawback', [
                'payment_id' => $payment->id,
                'dispute_id' => $disputeId,
            ]);
        });
    }

    private function alreadyClawedBack(Payment $payment, string $disputeId): bool
    {
        if ($payment->refund_status !== 'chargeback' || $payment->dispute_status !== 'lost') {
            return false;
        }

        if ($disputeId === '') {
            return true;
        }

        return (string) $payment->provider_dispute_id === $disputeId;
    }

    private function findStripePaymentForDispute(object $dispute): ?Payment
    {
        $paymentIntentId = isset($dispute->payment_intent) ? (string) $dispute->payment_intent : '';
        $chargeId = isset($dispute->charge) ? (string) $dispute->charge : '';

        if ($paymentIntentId !== '') {
            $payment = Payment::withoutGlobalScopes()
                ->where('provider', 'stripe')
                ->where('provider_payment_intent_id', $paymentIntentId)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        if ($chargeId !== '') {
            $payment = Payment::withoutGlobalScopes()
                ->where('provider', 'stripe')
                ->where('payload', 'like', '%'.$chargeId.'%')
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function paypalDisputedTransactionId(array $resource): ?string
    {
        $first = $resource['disputed_transactions'][0] ?? [];

        foreach (['seller_transaction_id', 'seller_transaction', 'original_transaction_id'] as $key) {
            $value = $first[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function paypalDisputeOutcome(array $resource): string
    {
        $code = $resource['dispute_outcome']['outcome_code']
            ?? $resource['outcome']
            ?? '';

        return strtoupper((string) $code);
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function paypalDisputeAmountCents(array $resource, int $fallback): int
    {
        $value = $resource['dispute_amount']['value'] ?? null;

        if ($value === null || $value === '') {
            return $fallback;
        }

        return (int) round((float) $value * 100);
    }

    /**
     * Confirm the payment belongs to a real tenant/brand merchant account
     * before any seller balance is touched. Ledrix brands use per-brand
     * AccountKey rows (not Stripe Connect acct_ IDs); if a Connect account
     * is present on the event it is logged for ops, not used as a standalone match.
     */
    private function paymentMatchesTenantBrand(Payment $payment, string $provider, ?string $connectedAccountId = null): bool
    {
        $payment->loadMissing('order');

        $tenantId = (int) ($payment->tenant_id ?? 0);
        $brandId = (int) ($payment->order?->brand_id ?? 0);

        if ($tenantId <= 0 || $brandId <= 0) {
            Log::warning('Dispute webhook rejected — payment is not bound to a tenant/brand', [
                'payment_id' => $payment->id,
                'provider'   => $provider,
            ]);

            return false;
        }

        $keys = AccountKey::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('brand_id', $brandId)
            ->where('module', 'ppc')
            ->where('status', 'active')
            ->first();

        if (! $keys) {
            Log::warning('Dispute webhook rejected — brand merchant account keys not found', [
                'payment_id' => $payment->id,
                'tenant_id'  => $tenantId,
                'brand_id'   => $brandId,
                'provider'   => $provider,
            ]);

            return false;
        }

        $hasKeys = $provider === 'paypal'
            ? $keys->hasPaypalSecret()
            : $keys->hasStripeSecret();

        if (! $hasKeys) {
            Log::warning('Dispute webhook rejected — brand keys do not match provider', [
                'payment_id' => $payment->id,
                'provider'   => $provider,
                'brand_id'   => $brandId,
            ]);

            return false;
        }

        if ($connectedAccountId) {
            Log::info('Stripe dispute event connected account noted', [
                'payment_id'            => $payment->id,
                'connected_account'     => $connectedAccountId,
                'tenant_id'             => $tenantId,
                'brand_id'              => $brandId,
            ]);
        }

        return true;
    }

    private function chargebackTrackingAllowed(Payment $payment): bool
    {
        $tenantId = (int) ($payment->tenant_id ?? 0);

        if (! $tenantId) {
            return true;
        }

        if ($this->tenantFeatures->enabled('chargeback_tracking', $tenantId)) {
            return true;
        }

        Log::info('Chargeback/dispute ignored — plan excludes chargeback tracking', [
            'payment_id' => $payment->id,
            'tenant_id'  => $tenantId,
        ]);

        return false;
    }
}
