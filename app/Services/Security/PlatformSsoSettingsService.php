<?php

namespace App\Services\Security;

use App\Models\Central\PlatformSsoSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PlatformSsoSettingsService
{
    public const CACHE_KEY = 'platform_sso_settings.v1';

    public function tableReady(): bool
    {
        try {
            return Schema::connection('central')->hasTable('platform_sso_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    public function isEnabled(): bool
    {
        $settings = $this->settings();

        if (! ($settings['enabled'] ?? false)) {
            return false;
        }

        return filled($settings['issuer_url'] ?? null)
            && filled($settings['client_id'] ?? null)
            && filled($settings['client_secret'] ?? null);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     provider_name: ?string,
     *     issuer_url: ?string,
     *     client_id: ?string,
     *     client_secret: ?string,
     *     redirect_uri: ?string,
     *     scopes: string,
     *     audience: ?string,
     *     has_client_secret: bool
     * }
     */
    public function settings(): array
    {
        if (! $this->tableReady()) {
            return $this->envDefaults();
        }

        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $row = PlatformSsoSetting::query()->orderBy('id')->first();
            if (! $row) {
                return $this->envDefaults();
            }

            return [
                'enabled' => (bool) $row->enabled,
                'provider_name' => $row->provider_name,
                'issuer_url' => $row->issuer_url,
                'client_id' => $row->client_id,
                'client_secret' => $row->client_secret,
                'redirect_uri' => $row->redirect_uri,
                'scopes' => $row->scopes ?: (string) config('sso.scopes', 'openid profile email'),
                'audience' => $row->audience,
                'has_client_secret' => filled($row->client_secret),
            ];
        });
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     provider_name?: ?string,
     *     issuer_url?: ?string,
     *     client_id?: ?string,
     *     client_secret?: ?string,
     *     redirect_uri?: ?string,
     *     scopes?: ?string,
     *     audience?: ?string
     * }  $data
     */
    public function update(array $data, ?int $updatedBy = null): void
    {
        $row = PlatformSsoSetting::query()->orderBy('id')->first() ?? new PlatformSsoSetting;

        $secret = $data['client_secret'] ?? null;
        if ($secret === null || $secret === '') {
            // Keep existing secret when the form leaves the field blank.
            unset($data['client_secret']);
        }

        $row->fill([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'provider_name' => $data['provider_name'] ?? $row->provider_name,
            'issuer_url' => $this->normalizeIssuer($data['issuer_url'] ?? $row->issuer_url),
            'client_id' => $data['client_id'] ?? $row->client_id,
            'redirect_uri' => $data['redirect_uri'] ?? $row->redirect_uri,
            'scopes' => trim((string) ($data['scopes'] ?? $row->scopes ?? config('sso.scopes'))) ?: 'openid profile email',
            'audience' => $data['audience'] ?? $row->audience,
            'updated_by' => $updatedBy,
        ]);

        if (array_key_exists('client_secret', $data) && filled($data['client_secret'])) {
            $row->client_secret = $data['client_secret'];
        }

        $row->save();
        Cache::forget(self::CACHE_KEY);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function normalizeIssuer(?string $issuer): ?string
    {
        if ($issuer === null || trim($issuer) === '') {
            return null;
        }

        return rtrim(trim($issuer), '/');
    }

    /**
     * @return array{
     *     enabled: bool,
     *     provider_name: ?string,
     *     issuer_url: ?string,
     *     client_id: ?string,
     *     client_secret: ?string,
     *     redirect_uri: ?string,
     *     scopes: string,
     *     audience: ?string,
     *     has_client_secret: bool
     * }
     */
    private function envDefaults(): array
    {
        $secret = config('sso.client_secret');

        return [
            'enabled' => (bool) config('sso.enabled', false),
            'provider_name' => config('sso.provider_name'),
            'issuer_url' => $this->normalizeIssuer(config('sso.issuer_url')),
            'client_id' => config('sso.client_id'),
            'client_secret' => $secret,
            'redirect_uri' => config('sso.redirect_uri'),
            'scopes' => (string) config('sso.scopes', 'openid profile email'),
            'audience' => config('sso.audience'),
            'has_client_secret' => filled($secret),
        ];
    }
}
