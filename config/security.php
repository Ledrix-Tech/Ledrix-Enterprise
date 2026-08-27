<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Force two-factor authentication (F-13)
    |--------------------------------------------------------------------------
    |
    | When true, owners must enable TOTP before using the product surface.
    | Defaults to enforced in production; disable locally via .env if needed.
    |
    */
    'force_super_admin_owner_2fa' => env(
        'FORCE_SUPER_ADMIN_OWNER_2FA',
        env('APP_ENV') === 'production'
    ),

    'force_tenant_admin_2fa' => env(
        'FORCE_TENANT_ADMIN_2FA',
        env('APP_ENV') === 'production'
    ),
];
