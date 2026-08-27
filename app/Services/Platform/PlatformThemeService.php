<?php

namespace App\Services\Platform;

use App\Models\Central\PlatformThemeSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PlatformThemeService
{
    public const CACHE_KEY = 'platform_theme_settings.v1';

    public const DEFAULT_PRIMARY = '#4338ca';

    public const DEFAULT_SECONDARY = '#8b52fe';

    public function tableReady(): bool
    {
        try {
            return Schema::connection('central')->hasTable('platform_theme_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{
     *     primary: string,
     *     secondary: string,
     *     primary_dark: string,
     *     primary_light: string,
     *     primary_rgb: string,
     *     on_primary: string
     * }
     */
    public function cssVariables(): array
    {
        $primary = $this->primary();
        $secondary = $this->secondary();

        return [
            'primary'       => $primary,
            'secondary'     => $secondary,
            'primary_dark'  => $this->adjustBrightness($primary, -0.28),
            'primary_light' => $this->adjustBrightness($primary, 0.22),
            'primary_rgb'   => implode(', ', $this->hexToRgb($primary)),
            'on_primary'    => $this->contrastText($primary),
        ];
    }

    public function primary(): string
    {
        return $this->settings()['primary_color'] ?? self::DEFAULT_PRIMARY;
    }

    public function secondary(): string
    {
        $secondary = $this->settings()['secondary_color'] ?? null;

        return $secondary ?: self::DEFAULT_SECONDARY;
    }

    /**
     * @return array{primary_color: string, secondary_color: ?string}
     */
    public function settings(): array
    {
        if (! $this->tableReady()) {
            return [
                'primary_color'   => self::DEFAULT_PRIMARY,
                'secondary_color' => self::DEFAULT_SECONDARY,
            ];
        }

        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $row = PlatformThemeSetting::query()->orderBy('id')->first();

            return [
                'primary_color'   => $this->sanitizeHex($row?->primary_color) ?? self::DEFAULT_PRIMARY,
                'secondary_color' => $this->sanitizeHex($row?->secondary_color) ?? self::DEFAULT_SECONDARY,
            ];
        });
    }

    public function update(string $primary, ?string $secondary, ?int $updatedBy = null): void
    {
        $primary = $this->sanitizeHex($primary) ?? self::DEFAULT_PRIMARY;
        $secondary = $this->sanitizeHex($secondary);

        $row = PlatformThemeSetting::query()->orderBy('id')->first();
        if (! $row) {
            $row = new PlatformThemeSetting;
        }

        $row->fill([
            'primary_color'   => $primary,
            'secondary_color' => $secondary,
            'updated_by'      => $updatedBy,
        ])->save();

        Cache::forget(self::CACHE_KEY);
    }

    public function reset(?int $updatedBy = null): void
    {
        $this->update(self::DEFAULT_PRIMARY, self::DEFAULT_SECONDARY, $updatedBy);
    }

    public function sanitizeHex(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtolower(trim($value));
        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        if (! preg_match('/^#[0-9a-f]{6}$/', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public function hexToRgb(string $hex): array
    {
        $hex = ltrim($this->sanitizeHex($hex) ?? self::DEFAULT_PRIMARY, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public function contrastText(string $hex): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        // Relative luminance (sRGB)
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        return $luminance > 0.55 ? '#111827' : '#ffffff';
    }

    public function adjustBrightness(string $hex, float $percent): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        $adjust = function (int $channel) use ($percent): int {
            if ($percent < 0) {
                return (int) max(0, round($channel * (1 + $percent)));
            }

            return (int) min(255, round($channel + (255 - $channel) * $percent));
        };

        return sprintf('#%02x%02x%02x', $adjust($r), $adjust($g), $adjust($b));
    }
}
