<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentRefundProcessor;
use App\Services\SellerPerformance;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Stripe\Event;
use Tests\Support\CreatesPaymentFlow;
use Tests\TestCase;

class DisputeClawbackTimingTest extends TestCase
{
    use CreatesPaymentFlow;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TenantFeatureService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('assertEnabled')->andReturnNull();
        });
    }

    public function test_stripe_dispute_created_does_not_clawback_seller_revenue(): void
    {
        Notification::fake();
        ['payment' => $payment, 'seller' => $seller] = $this->makeSucceededPayment('pi_dispute_open');

        app(PaymentRefundProcessor::class)->processStripeDisputeEvent($this->stripeDisputeEvent(
            type: 'charge.dispute.created',
            paymentIntent: 'pi_dispute_open',
            status: 'needs_response',
            disputeId: 'dp_open_1',
        ));

        $payment->refresh();
        $this->assertSame('none', $payment->refund_status);
        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('open', $payment->dispute_status);
        $this->assertSame('dp_open_1', $payment->provider_dispute_id);

        $performance = SellerPerformance::build($seller->id);
        $this->assertSame(0.0, (float) $performance['performance']['chargebacks']);
        $this->assertEqualsWithDelta(50.0, (float) $performance['performance']['net_revenue'], 0.01);
    }

    public function test_stripe_dispute_closed_lost_claws_back_once(): void
    {
        Notification::fake();
        ['payment' => $payment, 'seller' => $seller] = $this->makeSucceededPayment('pi_dispute_lost');

        $processor = app(PaymentRefundProcessor::class);
        $lost = $this->stripeDisputeEvent(
            type: 'charge.dispute.closed',
            paymentIntent: 'pi_dispute_lost',
            status: 'lost',
            disputeId: 'dp_lost_1',
        );

        $processor->processStripeDisputeEvent($lost);
        $processor->processStripeDisputeEvent($lost);

        $payment->refresh();
        $this->assertSame('chargeback', $payment->refund_status);
        $this->assertSame('lost', $payment->dispute_status);

        $performance = SellerPerformance::build($seller->id);
        $this->assertEqualsWithDelta(50.0, (float) $performance['performance']['chargebacks'], 0.01);
    }

    public function test_stripe_dispute_closed_won_does_not_clawback(): void
    {
        Notification::fake();
        ['payment' => $payment, 'seller' => $seller] = $this->makeSucceededPayment('pi_dispute_won');

        app(PaymentRefundProcessor::class)->processStripeDisputeEvent($this->stripeDisputeEvent(
            type: 'charge.dispute.created',
            paymentIntent: 'pi_dispute_won',
            status: 'needs_response',
            disputeId: 'dp_won_1',
        ));
        app(PaymentRefundProcessor::class)->processStripeDisputeEvent($this->stripeDisputeEvent(
            type: 'charge.dispute.closed',
            paymentIntent: 'pi_dispute_won',
            status: 'won',
            disputeId: 'dp_won_1',
        ));

        $payment->refresh();
        $this->assertSame('none', $payment->refund_status);
        $this->assertSame('won', $payment->dispute_status);
        $this->assertSame('succeeded', $payment->status);

        $performance = SellerPerformance::build($seller->id);
        $this->assertSame(0.0, (float) $performance['performance']['chargebacks']);
    }

    public function test_paypal_created_does_not_clawback_resolved_buyer_favour_does(): void
    {
        Notification::fake();
        ['payment' => $payment, 'seller' => $seller] = $this->makeSucceededPayment('PAY-CAPTURE-1', 'paypal');

        $processor = app(PaymentRefundProcessor::class);
        $processor->processPaypalDisputeEvent([
            'event_type' => 'CUSTOMER.DISPUTE.CREATED',
            'resource'   => [
                'dispute_id' => 'PP-D-1',
                'disputed_transactions' => [
                    ['seller_transaction_id' => 'PAY-CAPTURE-1'],
                ],
            ],
        ], 'CUSTOMER.DISPUTE.CREATED');

        $payment->refresh();
        $this->assertSame('none', $payment->refund_status);
        $this->assertSame('open', $payment->dispute_status);

        $processor->processPaypalDisputeEvent([
            'event_type' => 'CUSTOMER.DISPUTE.RESOLVED',
            'resource'   => [
                'dispute_id' => 'PP-D-1',
                'dispute_outcome' => ['outcome_code' => 'RESOLVED_BUYER_FAVOUR'],
                'dispute_amount' => ['value' => '50.00'],
                'disputed_transactions' => [
                    ['seller_transaction_id' => 'PAY-CAPTURE-1'],
                ],
            ],
        ], 'CUSTOMER.DISPUTE.RESOLVED');

        $payment->refresh();
        $this->assertSame('chargeback', $payment->refund_status);
        $this->assertSame('lost', $payment->dispute_status);

        $performance = SellerPerformance::build($seller->id);
        $this->assertEqualsWithDelta(50.0, (float) $performance['performance']['chargebacks'], 0.01);
    }

    public function test_stripe_funds_withdrawn_and_radar_do_not_clawback(): void
    {
        Notification::fake();
        ['payment' => $payment, 'seller' => $seller] = $this->makeSucceededPayment('pi_provisional');

        $processor = app(PaymentRefundProcessor::class);
        $processor->processStripeDisputeEvent($this->stripeDisputeEvent(
            type: 'charge.dispute.funds_withdrawn',
            paymentIntent: 'pi_provisional',
            status: 'needs_response',
            disputeId: 'dp_prov_1',
        ));
        $processor->processStripeDisputeEvent($this->stripeDisputeEvent(
            type: 'radar.early_fraud_warning',
            paymentIntent: 'pi_provisional',
            status: 'warning_needs_review',
            disputeId: 'issfr_1',
        ));

        $payment->refresh();
        $this->assertSame('none', $payment->refund_status);
        $this->assertSame('succeeded', $payment->status);
        $this->assertNull($payment->dispute_status);

        $performance = SellerPerformance::build($seller->id);
        $this->assertSame(0.0, (float) $performance['performance']['chargebacks']);
        $this->assertEqualsWithDelta(50.0, (float) $performance['performance']['net_revenue'], 0.01);
    }

    public function test_paypal_dispute_refund_does_not_double_process_after_loss(): void
    {
        Notification::fake();
        ['payment' => $payment, 'seller' => $seller] = $this->makeSucceededPayment('PAY-CAPTURE-2', 'paypal');

        $processor = app(PaymentRefundProcessor::class);
        $processor->processPaypalDisputeEvent([
            'event_type' => 'CUSTOMER.DISPUTE.RESOLVED',
            'resource'   => [
                'dispute_id' => 'PP-D-2',
                'dispute_outcome' => ['outcome_code' => 'RESOLVED_BUYER_FAVOUR'],
                'dispute_amount' => ['value' => '50.00'],
                'disputed_transactions' => [
                    ['seller_transaction_id' => 'PAY-CAPTURE-2'],
                ],
            ],
        ], 'CUSTOMER.DISPUTE.RESOLVED');

        $processor->processPaypalRefund([
            'event_type' => 'PAYMENT.SALE.REFUNDED',
            'resource'   => [
                'dispute_id' => 'PP-D-2',
                'amount' => ['value' => '50.00'],
                'links' => [
                    ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/PAY-CAPTURE-2'],
                ],
            ],
        ]);

        $payment->refresh();
        $this->assertSame('chargeback', $payment->refund_status);
        $this->assertSame(0, (int) $payment->refunded_amount);

        $performance = SellerPerformance::build($seller->id);
        $this->assertEqualsWithDelta(50.0, (float) $performance['performance']['chargebacks'], 0.01);
        $this->assertSame(0.0, (float) $performance['performance']['refunds']);
    }

    /**
     * @return array{payment: Payment, seller: \App\Models\Seller}
     */
    private function makeSucceededPayment(string $txnId, string $provider = 'stripe'): array
    {
        ['brand' => $brand, 'lead' => $lead, 'seller' => $seller] = $this->createPaymentLeadGraph();

        $order = Order::query()->create([
            'tenant_id'    => $brand->tenant_id ?? 1,
            'lead_id'      => $lead->id,
            'brand_id'     => $brand->id,
            'seller_id'    => $seller->id,
            'client_id'    => $lead->client_id,
            'service_name' => 'Website',
            'currency'     => 'USD',
            'unit_amount'  => 5000,
            'amount_paid'  => 5000,
            'balance_due'  => 0,
            'status'       => 'paid',
            'refund_status'=> 'none',
        ]);

        $payment = Payment::query()->create([
            'tenant_id'                  => $brand->tenant_id ?? 1,
            'order_id'                   => $order->id,
            'credit_to_seller_id'        => $seller->id,
            'amount'                     => 5000,
            'currency'                   => 'USD',
            'status'                     => 'succeeded',
            'provider'                   => $provider,
            'provider_payment_intent_id' => $txnId,
            'refund_status'              => 'none',
            'refunded_amount'            => 0,
        ]);

        return compact('payment', 'seller');
    }

    private function stripeDisputeEvent(string $type, string $paymentIntent, string $status, string $disputeId): Event
    {
        return Event::constructFrom([
            'id'   => 'evt_'.$disputeId.'_'.$type,
            'type' => $type,
            'data' => [
                'object' => [
                    'id'              => $disputeId,
                    'object'          => 'dispute',
                    'amount'          => 5000,
                    'charge'          => 'ch_'.$disputeId,
                    'payment_intent'  => $paymentIntent,
                    'status'          => $status,
                ],
            ],
        ]);
    }
}
