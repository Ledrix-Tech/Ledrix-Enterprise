<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared sandbox workspace
    |--------------------------------------------------------------------------
    |
    | Demo visitors get a DemoAccount on the central DB and are signed into
    | this one shared CRM tenant. They are not tenants and do not consume
    | a trial. The sandbox tenant is never used as an organization portal.
    |
    */

    'tenant_slug'  => env('SANDBOX_TENANT_SLUG', 'ledrix-demo'),
    'tenant_email' => env('SANDBOX_TENANT_EMAIL', 'sandbox-workspace@ledrix.local'),
    'tenant_name'  => env('SANDBOX_TENANT_NAME', 'Ledrix Demo Workspace'),

    'plan_slug' => env('SANDBOX_PLAN_SLUG', 'ledrix-sandbox-tour'),

    'connection' => env('SANDBOX_DB_CONNECTION', 'demos_db'),
    'database'   => env('DB_DEMOS_DATABASE', 'ledrix_demos'),

    'admin_email'  => env('SANDBOX_ADMIN_EMAIL', 'demo.admin@sandbox.ledrix.local'),
    'seller_email' => env('SANDBOX_SELLER_EMAIL', 'demo.seller@sandbox.ledrix.local'),
    'pm_email'     => env('SANDBOX_PM_EMAIL', 'demo.pm@sandbox.ledrix.local'),
    'client_email' => env('SANDBOX_CLIENT_EMAIL', 'demo.client@sandbox.ledrix.local'),

    'brands' => [
        'atlas'     => 'Demo Atlas',
        'northstar' => 'Demo Northstar',
    ],

];
