<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\PlatformStatusComponent;
use App\Models\Central\PlatformStatusIncident;
use App\Services\Platform\PlatformStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlatformStatusController extends Controller
{
    public function index(PlatformStatusService $status)
    {
        if (! $status->tableReady()) {
            return view('central.pages.status-page', [
                'migrationRequired' => true,
                'components'        => collect(),
                'incidents'         => collect(),
                'overallLabel'      => 'Unavailable',
            ]);
        }

        $status->ensureSeeded();

        return view('central.pages.status-page', [
            'migrationRequired' => false,
            'components'        => $status->components(),
            'incidents'         => PlatformStatusIncident::query()->orderByDesc('id')->paginate(20),
            'overallLabel'      => $status->overallLabel(),
        ]);
    }

    public function updateComponent(Request $request, int $id, PlatformStatusService $status)
    {
        if (! $status->tableReady()) {
            return back()->with('error', 'Run central migrations for platform status tables first.');
        }

        $component = PlatformStatusComponent::query()->findOrFail($id);
        $data = $request->validate([
            'status'      => ['required', Rule::in(PlatformStatusComponent::STATUSES)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $component->update($data);

        return back()->with('success', $component->name.' status updated.');
    }

    public function storeIncident(Request $request, PlatformStatusService $status)
    {
        if (! $status->tableReady()) {
            return back()->with('error', 'Run central migrations for platform status tables first.');
        }

        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['nullable', 'string', 'max:5000'],
            'severity'   => ['required', Rule::in(['minor', 'major', 'critical'])],
            'status'     => ['required', Rule::in(['investigating', 'identified', 'monitoring', 'resolved'])],
            'started_at' => ['nullable', 'date'],
        ]);

        $data['created_by'] = Auth::guard('super_admin')->id();
        $data['started_at'] = $data['started_at'] ?? now();
        if (($data['status'] ?? '') === 'resolved') {
            $data['resolved_at'] = now();
        }

        PlatformStatusIncident::query()->create($data);

        return back()->with('success', 'Incident published on the public status page.');
    }

    public function updateIncident(Request $request, int $id, PlatformStatusService $status)
    {
        if (! $status->tableReady()) {
            return back()->with('error', 'Run central migrations for platform status tables first.');
        }

        $incident = PlatformStatusIncident::query()->findOrFail($id);
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'body'     => ['nullable', 'string', 'max:5000'],
            'severity' => ['required', Rule::in(['minor', 'major', 'critical'])],
            'status'   => ['required', Rule::in(['investigating', 'identified', 'monitoring', 'resolved'])],
        ]);

        if ($data['status'] === 'resolved' && ! $incident->resolved_at) {
            $data['resolved_at'] = now();
        }
        if ($data['status'] !== 'resolved') {
            $data['resolved_at'] = null;
        }

        $incident->update($data);

        return back()->with('success', 'Incident updated.');
    }

    public function destroyIncident(int $id, PlatformStatusService $status)
    {
        if (! $status->tableReady()) {
            return back()->with('error', 'Run central migrations for platform status tables first.');
        }

        PlatformStatusIncident::query()->findOrFail($id)->delete();

        return back()->with('success', 'Incident removed.');
    }
}
