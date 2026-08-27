<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use App\Models\Central\PlatformStatusSubscriber;
use App\Services\Platform\PlatformStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StatusPageController extends Controller
{
    public function show(PlatformStatusService $status)
    {
        return view('front.pages.status', [
            'ready'         => $status->tableReady(),
            'overall'       => $status->overallStatus(),
            'overallLabel'  => $status->overallLabel(),
            'components'    => $status->components(),
            'openIncidents' => $status->openIncidents(),
            'incidents'     => $status->recentIncidents(12),
            'sla'           => [
                'target'  => '99.9%',
                'window'  => 'Monthly (excluding scheduled maintenance)',
                'support' => config('seo.organization.email', 'hello@ledrix.co'),
            ],
        ]);
    }

    public function subscribe(Request $request)
    {
        if (! Schema::connection('central')->hasTable('platform_status_subscribers')) {
            return back()->with('error', 'Status subscriptions are not available yet.');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $subscriber = PlatformStatusSubscriber::issue($data['email']);
        $subscriber->forceFill(['confirmed_at' => now()])->save();

        return back()->with('success', 'You are subscribed to status updates for '.$subscriber->email.'.');
    }

    public function confirm(string $token)
    {
        if (! Schema::connection('central')->hasTable('platform_status_subscribers')) {
            abort(404);
        }

        $subscriber = PlatformStatusSubscriber::query()->where('token', $token)->firstOrFail();
        if (! $subscriber->isConfirmed()) {
            $subscriber->forceFill(['confirmed_at' => now()])->save();
        }

        return redirect()
            ->route('status.get')
            ->with('success', 'Subscription confirmed for '.$subscriber->email.'.');
    }
}
