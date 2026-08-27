<?php

namespace Tests\Unit;

use App\Services\Security\OidcClientService;
use App\Services\Security\PlatformSsoSettingsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OidcClientServiceTest extends TestCase
{
    private OidcClientService $oidc;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sso.enabled' => true,
            'sso.issuer_url' => 'https://idp.example.com',
            'sso.client_id' => 'ledrix-client',
            'sso.client_secret' => 'secret-value',
            'sso.scopes' => 'openid profile email',
            'sso.audience' => 'api://ledrix',
            'sso.authorization_endpoint' => 'https://idp.example.com/oauth2/v1/authorize',
            'sso.token_endpoint' => 'https://idp.example.com/oauth2/v1/token',
            'sso.userinfo_endpoint' => 'https://idp.example.com/oauth2/v1/userinfo',
        ]);

        // Force env-backed settings (no DB row required for unit URL tests).
        $this->app->instance(PlatformSsoSettingsService::class, new class extends PlatformSsoSettingsService
        {
            public function tableReady(): bool
            {
                return false;
            }
        });

        $this->oidc = $this->app->make(OidcClientService::class);
    }

    public function test_generate_state_is_random_and_long_enough(): void
    {
        $a = $this->oidc->generateState();
        $b = $this->oidc->generateState();

        $this->assertNotSame($a, $b);
        $this->assertGreaterThanOrEqual(32, strlen($a));
    }

    public function test_validate_state_uses_constant_time_equality(): void
    {
        $this->assertTrue($this->oidc->validateState('abc123', 'abc123'));
        $this->assertFalse($this->oidc->validateState('abc123', 'abc124'));
        $this->assertFalse($this->oidc->validateState(null, 'abc123'));
        $this->assertFalse($this->oidc->validateState('abc123', null));
        $this->assertFalse($this->oidc->validateState('', ''));
    }

    public function test_build_authorize_url_includes_state_and_scopes(): void
    {
        $state = 'csrf-state-token-001';
        $redirect = 'https://app.example.com/super-admin/sso/callback';

        $url = $this->oidc->buildAuthorizeUrl($state, $redirect, 'nonce-1');

        $this->assertStringStartsWith('https://idp.example.com/oauth2/v1/authorize?', $url);

        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        $this->assertSame('code', $query['response_type']);
        $this->assertSame('ledrix-client', $query['client_id']);
        $this->assertSame($redirect, $query['redirect_uri']);
        $this->assertSame('openid profile email', $query['scope']);
        $this->assertSame($state, $query['state']);
        $this->assertSame('nonce-1', $query['nonce']);
        $this->assertSame('api://ledrix', $query['audience']);
    }

    public function test_extract_email_requires_valid_address(): void
    {
        $this->assertSame('user@example.com', $this->oidc->extractEmail(['email' => 'User@Example.com']));
        $this->assertNull($this->oidc->extractEmail(['email' => 'not-an-email']));
        $this->assertNull($this->oidc->extractEmail([]));
    }

    public function test_decode_id_token_payload_without_verification(): void
    {
        $payload = $this->base64UrlEncode(json_encode(['email' => 'a@b.co', 'sub' => '1']));
        $token = 'header.'.$payload.'.sig';

        $claims = $this->oidc->decodeIdTokenPayload($token);

        $this->assertSame('a@b.co', $claims['email']);
        $this->assertSame('1', $claims['sub']);
    }

    public function test_authenticate_with_code_uses_userinfo_email(): void
    {
        Http::fake([
            'https://idp.example.com/oauth2/v1/token' => Http::response([
                'access_token' => 'access-xyz',
                'id_token' => 'ignored',
                'token_type' => 'Bearer',
            ], 200),
            'https://idp.example.com/oauth2/v1/userinfo' => Http::response([
                'sub' => 'oidc-sub-9',
                'email' => 'Owner@Ledrix.app',
                'name' => 'Owner',
            ], 200),
        ]);

        $identity = $this->oidc->authenticateWithCode('auth-code', 'https://app.example.com/callback');

        $this->assertSame('owner@ledrix.app', $identity['email']);
        $this->assertSame('oidc-sub-9', $identity['sub']);
        $this->assertSame('Owner', $identity['name']);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
