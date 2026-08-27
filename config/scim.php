<?php

return [

    'enabled' => (bool) env('SCIM_ENABLED', false),

    'bearer_token' => env('SCIM_BEARER_TOKEN'),

    'default_user_role' => env('SCIM_DEFAULT_ADMIN_ROLE', 'admin'),

];
