<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use App\Services\Billing\PlatformWebhookRecorder;
use App\Services\Billing\StripeSubscriptionLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;

class PlatformStripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PlatformWebhookRecorder $recorder,
        ActivateTenantSubscriptionService $activationService,
        StripeSubscriptionLifecycleService $subscriptions,
    ) {
        $secret = (string) config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');

        if ($secret === '' || ! $sig) {
            return response()->json(['error' => 'Webhook not configured'], 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Platform Stripe webhook signature failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventId = (string) $event->id;
        $type = (string) $event->type;
        $object = $event->data->object ?? null;
        $decoded = json_decode($payload, true) ?: [];

        $handled = [
            'checkout.session.completed',
            'invoice.paid',
            'invoice.payment_failed',
            'customer.subscription.updated',
            'customer.subscription.deleted',
        ];

        if (! in_array($type, $handled, true)) {
            $recorder->recordAndProcess(
                'stripe',
                $eventId,
                $type,
                $decoded,
                null,
                fn ($row) => $row->markIgnored(),
            );

            return response()->json(['ok' => true, 'ignored' => true]);
        }

        try {
            $recorder->recordAndProcess(
                'stripe',
                $eventId,
                $type,
                $decoded,
                null,
                function ($row) use ($type, $object, $activationService, $subscriptions) {
                    match ($type) {
                        'checkout.session.completed' => $this->handleCheckoutCompleted(
                            $row,
                            $object,
                            $activationService,
                            $subscriptions
                        ),
                        'invoice.paid' => $subscriptions->handleInvoicePaid($object),
                        'invoice.payment_failed' => $subscriptions->handleInvoicePaymentFailed($object),
                        'customer.subscription.updated' => $subscriptions->handleSubscriptionUpdated($object),
                        'customer.subscription.deleted' => $subscriptions->handleSubscriptionDeleted($object),
                        default => $row->markIgnored(),
                    };
                },
            );
        } catch (Throwable $e) {
            Log::error('Platform Stripe webhook processing failed', [
                'event_id' => $eventId,
                'type'     => $type,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }

        return response()->json(['ok' => true]);
    }

    private function handleCheckoutCompleted(
        $row,
        object $session,
        ActivateTenantSubscriptionService $activationService,
        StripeSubscriptionLifecycleService $subscriptions,
    ): void {
        $paymentId = (int) ($session->metadata->tenant_payment_id ?? 0);
        $sessionId = (string) ($session->id ?? '');

        $payment = TenantPayment::query()
            ->with(['tenant', 'membership', 'invoice', 'plan'])
            ->where('gateway', 'stripe')
            ->find($paymentId);

        if (! $payment) {
            throw new RuntimeException('Tenant payment not found for Stripe session.');
        }

        $row->update(['tenant_id' => $payment->tenant_id]);

        $subscriptions->bindCheckoutSession($payment, $session);

        if ($payment->status === 'paid') {
            return;
        }

        $activationService->activate(
            $payment->fresh(['tenant.plan', 'membership', 'invoice', 'plan']),
            renewedBy: 'stripe_webhook',
            payloadMerge: ['stripe_session_id' => $sessionId, 'via' => 'platform_webhook'],
        );
    }
}
