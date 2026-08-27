<?php

namespace App\Services\Platform;

use App\Models\Central\PlatformStatusComponent;
use App\Models\Central\PlatformStatusIncident;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PlatformStatusService
{
    public function tableReady(): bool
    {
        try {
            return Schema::connection('central')->hasTable('platform_status_components')
                && Schema::connection('central')->hasTable('platform_status_incidents');
        } catch (\Throwable) {
            return false;
        }
    }

    public function ensureSeeded(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        PlatformStatusComponent::ensureDefaults();
    }

    /** @return Collection<int, PlatformStatusComponent> */
    public function components(): Collection
    {
        $this->ensureSeeded();

        return PlatformStatusComponent::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, PlatformStatusIncident> */
    public function openIncidents(): Collection
    {
        if (! $this->tableReady()) {
            return collect();
        }

        return PlatformStatusIncident::query()
            ->where('status', '!=', 'resolved')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();
    }

    /** @return Collection<int, PlatformStatusIncident> */
    public function recentIncidents(int $limit = 10): Collection
    {
        if (! $this->tableReady()) {
            return collect();
        }

        return PlatformStatusIncident::query()
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function overallStatus(): string
    {
        $components = $this->components();

        if ($components->isEmpty()) {
            return 'operational';
        }

        $priority = [
            'major_outage'   => 5,
            'partial_outage' => 4,
            'degraded'       => 3,
            'maintenance'    => 2,
            'operational'    => 1,
        ];

        $worst = 'operational';
        $worstScore = 1;

        foreach ($components as $component) {
            $score = $priority[$component->status] ?? 1;
            if ($score > $worstScore) {
                $worstScore = $score;
                $worst = $component->status;
            }
        }

        if ($this->openIncidents()->isNotEmpty() && $worstScore < 3) {
            return 'degraded';
        }

        return $worst;
    }

    public function overallLabel(): string
    {
        return match ($this->overallStatus()) {
            'major_outage'   => 'Major outage',
            'partial_outage' => 'Partial outage',
            'degraded'       => 'Degraded performance',
            'maintenance'    => 'Scheduled maintenance',
            default          => 'All systems operational',
        };
    }
}
