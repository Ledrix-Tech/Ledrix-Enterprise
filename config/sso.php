<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform SSO (OIDC) defaults — F-20 foundation
    |--------------------------------------------------------------------------
    |
    | Runtime config is stored in central `platform_sso_settings` (single row).
    | Env keys seed defaults when the table is empty / for local overrides.
    | SCIM provisioning at /api/scim/v2 when SCIM_ENABLED=true.
    |
    */

    'enabled' => (bool) env('SSO_ENABLED', false),

    'provider_name' => env('SSO_PROVIDER_NAME', 'OIDC'),

    'issuer_url' => env('SSO_ISSUER_URL'),

    'client_id' => env('SSO_CLIENT_ID'),

    'client_secret' => env('SSO_CLIENT_SECRET'),

    'redirect_uri' => env('SSO_REDIRECT_URI'),

    'scopes' => env('SSO_SCOPES', 'openid profile email'),

    'audience' => env('SSO_AUDIENCE'),

    /*
    | Optional static endpoints (skip discovery). Useful in tests / private IdPs.
    */
    'authorization_endpoint' => env('SSO_AUTHORIZATION_ENDPOINT'),
    'token_endpoint' => env('SSO_TOKEN_ENDPOINT'),
    'userinfo_endpoint' => env('SSO_USERINFO_ENDPOINT'),

    'discovery_path' => '/.well-known/openid-configuration',

    'http_timeout' => (int) env('SSO_HTTP_TIMEOUT', 15),

    'session_state_key' => 'sso_oauth_state',
    'session_flow_key' => 'sso_oauth_flow',
];
