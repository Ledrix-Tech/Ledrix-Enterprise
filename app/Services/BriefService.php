<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Questionnair;
use App\Notifications\SendBriefLinkMail;
use App\Support\SafeMail;
use Illuminate\Support\Str;

class BriefService
{
    public function ensureBriefForOrder(Order $order): Questionnair
    {
        $brief = Questionnair::firstOrCreate(
            ['order_id' => $order->id],
            [
                'client_id' => $order->client_id,
                'service_name' => $order->service_name ?? 'Unknown Service',
                'meta' => [],
                'status' => 'pending',
            ]
        );

        if (! $brief->brief_token || ($brief->brief_token_expires_at && $brief->brief_token_expires_at->isPast())) {
            $brief->brief_token = (string) Str::uuid();
            $brief->brief_token_expires_at = now()->addDays(14);
            $brief->save();
        }

        return $brief;
    }

    public function publicBriefUrl(Questionnair $brief): string
    {
        return route('brief.show', ['token' => $brief->brief_token]);
    }

    public function clientPortalBriefUrl(): string
    {
        return route('client.brief.get');
    }

    public function dispatchBriefEmail(int $orderId): void
    {
        $this->sendBriefLinkIfNeeded($orderId);
    }

    public function sendBriefLinkIfNeeded(int $orderId): void
    {
        $order = Order::with(['client', 'brand'])->find($orderId);
        if (!$order || !$order->client) return;

        // send only for originals (or include renewals if you want)
        if ($order->order_type !== 'original') return;

        // prevent duplicates
        // if ($order->brief_sent_at) return;

        $brief = $this->ensureBriefForOrder($order);

        $briefUrl  = $this->publicBriefUrl($brief);
        $brandName = $order->brand->brand_name ?? config('app.name');

        SafeMail::notify(
            $order->client,
            new SendBriefLinkMail($order->client, $order, $brandName, $briefUrl),
            'brief link',
            ['order_id' => $order->id],
        );
        $order->save();
    }
}
