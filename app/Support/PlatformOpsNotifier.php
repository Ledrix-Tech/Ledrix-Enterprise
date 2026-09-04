<?php

namespace App\Support;

use App\Mail\PlatformOpsAlertMail;

class PlatformOpsNotifier
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function alert(string $type, string $headline, array $context = []): void
    {
        $to = config('services.bank_transfer.notify_email')
            ?: config('mail.from.address');

        if (! $to) {
            return;
        }

        SafeMail::send(
            $to,
            new PlatformOpsAlertMail($type, $headline, $context),
            'platform ops alert',
            ['type' => $type],
        );
    }
}
