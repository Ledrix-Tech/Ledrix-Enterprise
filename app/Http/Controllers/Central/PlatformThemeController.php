<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformThemeController extends Controller
{
    public function edit(PlatformThemeService $theme)
    {
        if (! $theme->tableReady()) {
            return view('central.pages.theme-settings', [
                'migrationRequired' => true,
                'settings'          => $theme->settings(),
                'preview'           => $theme->cssVariables(),
            ]);
        }

        return view('central.pages.theme-settings', [
            'migrationRequired' => false,
            'settings'          => $theme->settings(),
            'preview'           => $theme->cssVariables(),
        ]);
    }

    public function update(Request $request, PlatformThemeService $theme)
    {
        if (! $theme->tableReady()) {
            return back()->with(
                'error',
                'Run central migrations first: php artisan migrate --database=central --path=database/migrations/central --force'
            );
        }

        $validated = $request->validate([
            'primary_color'   => ['required', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ]);

        $theme->update(
            primary: $validated['primary_color'],
            secondary: $validated['secondary_color'] ?? null,
            updatedBy: Auth::guard('super_admin')->id(),
        );

        return back()->with('success', 'Front website theme colors saved. Refresh marketing pages to see the change.');
    }

    public function reset(PlatformThemeService $theme)
    {
        if (! $theme->tableReady()) {
            return back()->with('error', 'Theme settings table is missing. Run central migrations first.');
        }

        $theme->reset(Auth::guard('super_admin')->id());

        return back()->with('success', 'Theme reset to Ledrix default indigo.');
    }
}
