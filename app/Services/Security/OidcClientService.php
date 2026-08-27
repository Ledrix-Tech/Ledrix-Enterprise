<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OidcClientService
{
    public function __construct(
        protected PlatformSsoSettingsService $settingsService
    ) {}

    public function isEnabled(): bool
    {
        return $this->settingsService->isEnabled();
    }

    public function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Build the IdP authorization URL for the OIDC authorization code flow.
     *
     * @throws RuntimeException
     */
    public function buildAuthorizeUrl(string $state, string $redirectUri, ?string $nonce = null): string
    {
        $settings = $this->requireConfiguredSettings();
        $endpoints = $this->discoverEndpoints($settings['issuer_url']);

        $params = [
            'response_type' => 'code',
            'client_id' => $settings['client_id'],
            'redirect_uri' => $redirectUri,
            'scope' => $settings['scopes'],
            'state' => $state,
        ];

        if ($nonce) {
            $params['nonce'] = $nonce;
        }

        if (filled($settings['audience'] ?? null)) {
            $params['audience'] = $settings['audience'];
        }

        return $endpoints['authorization_endpoint'].'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchange an authorization code for tokens, then resolve the user email.
     *
     * Prefers the userinfo endpoint; falls back to decoding the id_token payload
     * (without JWKS signature verification) when userinfo is unavailable.
     *
     * @return array{email: string, sub: ?string, name: ?string, claims: array}
     *
     * @throws RuntimeException
     */
    public function authenticateWithCode(string $code, string $redirectUri): array
    {
        $tokens = $this->exchangeCode($code, $redirectUri);
        $accessToken = $tokens['access_token'] ?? null;

        $claims = [];
        if (is_string($accessToken) && $accessToken !== '') {
            try {
                $claims = $this->fetchUserInfo($accessToken);
            } catch (RuntimeException) {
                $claims = [];
            }
        }

        if ($claims === [] && ! empty($tokens['id_token']) && is_string($tokens['id_token'])) {
            $claims = $this->decodeIdTokenPayload($tokens['id_token']);
        }

        $email = $this->extractEmail($claims);
        if ($email === null) {
            throw new RuntimeException('OIDC response did not include a verified email claim.');
        }

        return [
            'email' => $email,
            'sub' => isset($claims['sub']) ? (string) $claims['sub'] : null,
            'name' => isset($claims['name']) ? (string) $claims['name'] : null,
            'claims' => $claims,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $settings = $this->requireConfiguredSettings();
        $endpoints = $this->discoverEndpoints($settings['issuer_url']);

        $response = Http::asForm()
            ->timeout((int) config('sso.http_timeout', 15))
            ->acceptJson()
            ->post($endpoints['token_endpoint'], [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OIDC token exchange failed (HTTP '.$response->status().').');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('OIDC token endpoint returned an invalid payload.');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function fetchUserInfo(string $accessToken): array
    {
        $settings = $this->requireConfiguredSettings();
        $endpoints = $this->discoverEndpoints($settings['issuer_url']);

        if (empty($endpoints['userinfo_endpoint'])) {
            throw new RuntimeException('OIDC userinfo endpoint is not available.');
        }

        $response = Http::withToken($accessToken)
            ->timeout((int) config('sso.http_timeout', 15))
            ->acceptJson()
            ->get($endpoints['userinfo_endpoint']);

        if (! $response->successful()) {
            throw new RuntimeException('OIDC userinfo request failed (HTTP '.$response->status().').');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('OIDC userinfo returned an invalid payload.');
        }

        return $json;
    }

    /**
     * Decode JWT payload without signature verification (userinfo preferred).
     *
     * @return array<string, mixed>
     */
    public function decodeIdTokenPayload(string $idToken): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            return [];
        }

        $payload = $this->base64UrlDecode($parts[1]);
        if ($payload === null) {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function extractEmail(array $claims): ?string
    {
        $email = $claims['email'] ?? $claims['preferred_username'] ?? null;
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return strtolower(trim($email));
    }

    /**
     * Validate OAuth state against the session value (constant-time).
     */
    public function validateState(?string $incoming, ?string $expected): bool
    {
        if (! is_string($incoming) || $incoming === '' || ! is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals($expected, $incoming);
    }

    /**
     * @return array{authorization_endpoint: string, token_endpoint: string, userinfo_endpoint: ?string}
     *
     * @throws RuntimeException
     */
    public function discoverEndpoints(string $issuerUrl): array
    {
        $staticAuth = config('sso.authorization_endpoint');
        $staticToken = config('sso.token_endpoint');
        $staticUserinfo = config('sso.userinfo_endpoint');

        if (filled($staticAuth) && filled($staticToken)) {
            return [
                'authorization_endpoint' => (string) $staticAuth,
                'token_endpoint' => (string) $staticToken,
                'userinfo_endpoint' => filled($staticUserinfo) ? (string) $staticUserinfo : null,
            ];
        }

        $issuer = rtrim($issuerUrl, '/');
        $discoveryUrl = $issuer.(string) config('sso.discovery_path', '/.well-known/openid-configuration');

        $response = Http::timeout((int) config('sso.http_timeout', 15))
            ->acceptJson()
            ->get($discoveryUrl);

        if (! $response->successful()) {
            throw new RuntimeException('OIDC discovery failed (HTTP '.$response->status().').');
        }

        $json = $response->json();
        if (! is_array($json) || empty($json['authorization_endpoint']) || empty($json['token_endpoint'])) {
            throw new RuntimeException('OIDC discovery document is incomplete.');
        }

        return [
            'authorization_endpoint' => (string) $json['authorization_endpoint'],
            'token_endpoint' => (string) $json['token_endpoint'],
            'userinfo_endpoint' => isset($json['userinfo_endpoint']) ? (string) $json['userinfo_endpoint'] : null,
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     provider_name: ?string,
     *     issuer_url: string,
     *     client_id: string,
     *     client_secret: string,
     *     redirect_uri: ?string,
     *     scopes: string,
     *     audience: ?string,
     *     has_client_secret: bool
     * }
     *
     * @throws RuntimeException
     */
    protected function requireConfiguredSettings(): array
    {
        $settings = $this->settingsService->settings();

        if (! ($settings['enabled'] ?? false)) {
            throw new RuntimeException('SSO is disabled.');
        }

        if (! filled($settings['issuer_url'] ?? null)
            || ! filled($settings['client_id'] ?? null)
            || ! filled($settings['client_secret'] ?? null)) {
            throw new RuntimeException('SSO is not fully configured.');
        }

        return $settings;
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
