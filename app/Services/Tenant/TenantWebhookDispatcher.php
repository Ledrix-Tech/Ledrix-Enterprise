<?php

namespace App\Services\Tenant;

use App\Jobs\DispatchTenantWebhookJob;
use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;
use App\Models\Central\TenantWebhookDelivery;
use App\Models\Central\TenantWebhookEndpoint;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantWebhookDispatcher
{
    /**
     * Queue outbound deliveries for all matching enabled endpoints.
     *
     * @param  array<string, mixed>  $payload
     * @return list<TenantWebhookDelivery>
     */
    public function dispatch(int $tenantId, string $event, array $payload): array
    {
        $endpoints = TenantWebhookEndpoint::query()
            ->where('tenant_id', $tenantId)
            ->where('enabled', true)
            ->get()
            ->filter(fn (TenantWebhookEndpoint $endpoint) => $endpoint->listensFor($event));

        $deliveries = [];

        foreach ($endpoints as $endpoint) {
            $delivery = TenantWebhookDelivery::query()->create([
                'tenant_id'   => $tenantId,
                'endpoint_id' => $endpoint->id,
                'event'       => $event,
                'payload'     => array_merge([
                    'id'        => (string) \Illuminate\Support\Str::uuid(),
                    'event'     => $event,
                    'created'   => now()->toIso8601String(),
                    'tenant_id' => $tenantId,
                ], $payload),
                'status'      => 'pending',
                'attempts'    => 0,
            ]);

            DispatchTenantWebhookJob::dispatch($delivery->id);
            $deliveries[] = $delivery;
        }

        return $deliveries;
    }

    public function dispatchSubscriptionActivated(TenantPayment $payment): void
    {
        try {
            $payment->loadMissing(['tenant', 'membership', 'invoice']);
            $tenant = $payment->tenant;
            if (! $tenant) {
                return;
            }

            $membership = $payment->membership;
            $invoice = $payment->invoice;

            if ($invoice) {
                $this->dispatch((int) $tenant->id, 'invoice.paid', [
                    'data' => [
                        'invoice' => $this->invoicePayload($invoice),
                        'payment' => $this->paymentPayload($payment),
                    ],
                ]);
            }

            if ($membership) {
                $this->dispatch((int) $tenant->id, 'membership.activated', [
                    'data' => [
                        'membership' => $this->membershipPayload($membership),
                        'payment'    => $this->paymentPayload($payment),
                        'tenant'     => [
                            'id'   => $tenant->id,
                            'name' => $tenant->name,
                            'slug' => $tenant->slug,
                        ],
                    ],
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Outbound tenant webhook dispatch failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(TenantInvoice $invoice): array
    {
        return [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status'         => $invoice->status,
            'amount'         => (string) $invoice->amount,
            'tax_amount'     => (string) $invoice->tax_amount,
            'total_amount'   => (string) $invoice->total_amount,
            'currency'       => $invoice->currency,
            'billing_cycle'  => $invoice->billing_cycle,
            'paid_at'        => $invoice->paid_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(TenantPayment $payment): array
    {
        return [
            'id'             => $payment->id,
            'gateway'        => $payment->gateway,
            'status'         => $payment->status,
            'amount'         => (string) $payment->amount,
            'currency'       => $payment->currency,
            'transaction_id' => $payment->transaction_id,
            'paid_at'        => $payment->paid_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function membershipPayload(TenantMembership $membership): array
    {
        return [
            'id'            => $membership->id,
            'status'        => $membership->status,
            'billing_cycle' => $membership->billing_cycle,
            'start_date'    => $membership->start_date?->toDateString(),
            'end_date'      => $membership->end_date?->toDateString(),
            'amount'        => (string) $membership->amount,
            'currency'      => $membership->currency,
        ];
    }
}
