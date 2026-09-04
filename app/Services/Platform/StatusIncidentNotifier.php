<?php

namespace App\Services\Platform;

use App\Mail\StatusIncidentMail;
use App\Models\Central\PlatformStatusIncident;
use App\Models\Central\PlatformStatusSubscriber;
use App\Support\SafeMail;
use Illuminate\Support\Facades\Schema;

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
            if (SafeMail::send(
                $subscriber->email,
                new StatusIncidentMail($incident, $subscriber, $event),
                'status incident',
                ['incident_id' => $incident->id],
            )) {
                $sent++;
            }
        }

        return $sent;
    }
}
