{{-- Injected on front layouts so marketing/auth CSS can use CSS variables --}}
@php
    try {
        $ledrixTheme = app(\App\Services\Platform\PlatformThemeService::class)->cssVariables();
    } catch (\Throwable) {
        $ledrixTheme = [
            'primary' => '#4338ca',
            'secondary' => '#8b52fe',
            'primary_dark' => '#312e81',
            'primary_light' => '#6366f1',
            'primary_rgb' => '67, 56, 202',
            'on_primary' => '#ffffff',
        ];
    }
@endphp
<style id="ledrix-theme-vars">
:root {
    --ledrix-primary: {{ $ledrixTheme['primary'] }};
    --ledrix-primary-dark: {{ $ledrixTheme['primary_dark'] }};
    --ledrix-primary-light: {{ $ledrixTheme['primary_light'] }};
    --ledrix-secondary: {{ $ledrixTheme['secondary'] }};
    --ledrix-primary-rgb: {{ $ledrixTheme['primary_rgb'] }};
    --ledrix-on-primary: {{ $ledrixTheme['on_primary'] }};
}
</style>
