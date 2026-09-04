<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentDisputeNotification;
use App\Notifications\PaymentRefundedNotification;
use App\Support\SafeMail;

class NotifyStakeholders
{
    public static function refund(Payment $payment, Order $order, string $provider, ?string $reason = null): void
    {
        $client  = $order->client;
        $fs      = $order->frontSeller ?? $order->seller;
        $pm      = $order->ownerSeller ?? null;
        $admins  = Admin::where('role', 'admin')->get();
        $finance = Admin::where('role', 'finance')->get();
        $mail    = new PaymentRefundedNotification($payment, $order, $provider, $reason);
        $extra   = ['payment_id' => $payment->id, 'order_id' => $order->id];

        if ($client?->email) {
            SafeMail::notify($client->email, $mail, 'payment refunded', $extra);
        }

        if ($fs?->email) {
            SafeMail::notify($fs, $mail, 'payment refunded', $extra);
        }

        if ($pm?->email && (! $fs || $pm->id !== $fs->id)) {
            SafeMail::notify($pm, $mail, 'payment refunded', $extra);
        }

        SafeMail::notify($admins, $mail, 'payment refunded', $extra);
        SafeMail::notify($finance, $mail, 'payment refunded', $extra);
    }

    public static function dispute(Payment $payment, Order $order, string $provider, string $stage, ?string $reason = null): void
    {
        $client  = $order->client;
        $fs      = $order->frontSeller ?? $order->seller;
        $pm      = $order->ownerSeller ?? null;
        $admins  = Admin::where('role', 'admin')->get();
        $finance = Admin::where('role', 'finance')->get();
        $mail    = new PaymentDisputeNotification($payment, $order, $provider, $stage, $reason);
        $extra   = ['payment_id' => $payment->id, 'order_id' => $order->id, 'stage' => $stage];

        if ($client?->email) {
            SafeMail::notify($client->email, $mail, 'payment dispute', $extra);
        }

        if ($fs?->email) {
            SafeMail::notify($fs, $mail, 'payment dispute', $extra);
        }

        if ($pm?->email && (! $fs || $pm->id !== $fs->id)) {
            SafeMail::notify($pm, $mail, 'payment dispute', $extra);
        }

        SafeMail::notify($admins, $mail, 'payment dispute', $extra);
        SafeMail::notify($finance, $mail, 'payment dispute', $extra);
    }
}
