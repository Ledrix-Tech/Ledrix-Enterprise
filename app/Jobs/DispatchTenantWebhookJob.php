<?php

namespace App\Jobs;

use App\Models\Central\TenantWebhookDelivery;
use App\Models\Central\TenantWebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchTenantWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $deliveryId) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(): void
    {
        $delivery = TenantWebhookDelivery::query()->with('endpoint')->find($this->deliveryId);
        if (! $delivery || ! $delivery->endpoint) {
            return;
        }

        if ($delivery->status === 'delivered') {
            return;
        }

        /** @var TenantWebhookEndpoint $endpoint */
        $endpoint = $delivery->endpoint;
        if (! $endpoint->enabled) {
            $delivery->forceFill([
                'status'        => 'cancelled',
                'response_body' => 'Endpoint disabled',
            ])->save();

            return;
        }

        $body = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            $delivery->forceFill([
                'status'        => 'failed',
                'response_body' => 'Invalid JSON payload',
            ])->save();

            return;
        }

        $signature = hash_hmac('sha256', $body, (string) $endpoint->secret);

        $delivery->forceFill([
            'attempts' => $delivery->attempts + 1,
            'status'   => 'retrying',
        ])->save();

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type'       => 'application/json',
                    'User-Agent'         => 'Ledrix-Webhooks/1.0',
                    'X-Ledrix-Event'     => $delivery->event,
                    'X-Ledrix-Delivery'  => (string) $delivery->id,
                    'X-Ledrix-Signature' => 'sha256='.$signature,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $ok = $response->successful();
            $delivery->forceFill([
                'response_code' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 2000),
                'status'        => $ok ? 'delivered' : 'retrying',
                'delivered_at'  => $ok ? now() : null,
                'next_retry_at' => $ok ? null : now()->addMinutes(min(60, 2 ** $delivery->attempts)),
            ])->save();

            if (! $ok) {
                throw new \RuntimeException('Webhook endpoint returned HTTP '.$response->status());
            }
        } catch (Throwable $e) {
            Log::warning('Outbound webhook delivery failed', [
                'delivery_id' => $delivery->id,
                'error'       => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $delivery->forceFill([
                    'status'        => 'failed',
                    'response_body' => mb_substr($e->getMessage(), 0, 2000),
                ])->save();
            }

            throw $e;
        }
    }
}
