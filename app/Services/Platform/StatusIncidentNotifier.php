<?php

namespace App\Services\Platform;

use App\Mail\StatusIncidentMail;
use App\Models\Central\PlatformStatusIncident;
use App\Models\Central\PlatformStatusSubscriber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StatusIncidentNotifier
{
    public function notify(PlatformStatusIncident $incident, string $event = 'published'): int
    {
        if (! Schema::connection('central')->hasTable('platform_status_subscribers')) {
            return 0;
        }

        $subscribers = PlatformStatusSubscriber::query()
            ->whereNotNull('confirmed_at')
            ->get();

        $sent = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(
                    new StatusIncidentMail($incident, $subscriber, $event)
                );
                $sent++;
            } catch (Throwable $e) {
                Log::warning('Status incident email failed', [
                    'email'       => $subscriber->email,
                    'incident_id' => $incident->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
