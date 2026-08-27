<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DB-per-tenant isolation (F-28)
    |--------------------------------------------------------------------------
    |
    | When enabled, new tenants receive a dedicated CRM database. Existing
    | tenants keep using the shared primary DB until migrated/provisioned.
    |
    */

    'db_isolation_enabled' => (bool) env('TENANT_DB_ISOLATION', false),

    'database_prefix' => env('TENANT_DB_PREFIX', 'ledrix_tenant_'),

    'provision_on_register' => (bool) env('TENANT_DB_PROVISION_ON_REGISTER', true),

];
