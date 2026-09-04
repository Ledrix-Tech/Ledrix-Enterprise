<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SafeMail
{
    /**
     * Send mail without throwing. Invalid addresses are skipped; failures are logged.
     *
     * @param  Mailable|callable():mixed  $mail
     * @param  array<string, mixed>  $logExtra
     */
    public static function send(?string $to, Mailable|callable $mail, string $logContext = 'mail', array $logExtra = []): bool
    {
        $to = trim((string) $to);
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $mailable = $mail instanceof Mailable ? $mail : $mail();
            if (! $mailable instanceof Mailable) {
                return false;
            }

            Mail::to($to)->send($mailable);

            return true;
        } catch (Throwable $e) {
            Log::warning($logContext.' failed', array_merge($logExtra, [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]));

            return false;
        }
    }

    /**
     * @param  Mailable|callable():mixed  $mail
     * @param  array<string, mixed>  $logExtra
     */
    public static function toOps(Mailable|callable $mail, string $logContext = 'ops mail', array $logExtra = []): bool
    {
        $to = config('services.bank_transfer.notify_email')
            ?: config('mail.from.address');

        return self::send((string) $to, $mail, $logContext, $logExtra);
    }
}
