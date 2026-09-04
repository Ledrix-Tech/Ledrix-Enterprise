<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

final class SafeMail
{
    /**
     * Send immediately. Never throws. Invalid addresses are skipped.
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

            Mail::to($to)->sendNow($mailable);

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
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $logExtra
     */
    public static function sendView(
        ?string $to,
        string $view,
        array $data,
        string $subject,
        string $logContext = 'mail',
        array $logExtra = [],
    ): bool {
        $to = trim((string) $to);
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            Mail::send($view, $data, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

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
     * Send a notification immediately. Accepts a notifiable, email string, or list.
     *
     * @param  mixed  $notifiable
     * @param  array<string, mixed>  $logExtra
     */
    public static function notify(mixed $notifiable, Notification $notification, string $logContext = 'notification', array $logExtra = []): bool
    {
        if ($notifiable instanceof \Illuminate\Support\Collection || is_array($notifiable)) {
            $sent = false;
            foreach ($notifiable as $one) {
                if (self::notify($one, $notification, $logContext, $logExtra)) {
                    $sent = true;
                }
            }

            return $sent;
        }

        if (is_string($notifiable)) {
            $email = trim($notifiable);
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            try {
                NotificationFacade::route('mail', $email)->notifyNow($notification);

                return true;
            } catch (Throwable $e) {
                Log::warning($logContext.' failed', array_merge($logExtra, [
                    'to'    => $email,
                    'error' => $e->getMessage(),
                ]));

                return false;
            }
        }

        if (! is_object($notifiable) || ! method_exists($notifiable, 'notifyNow')) {
            return false;
        }

        try {
            $notifiable->notifyNow($notification);

            return true;
        } catch (Throwable $e) {
            Log::warning($logContext.' failed', array_merge($logExtra, [
                'to'    => $notifiable->email ?? $notifiable->id ?? null,
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
