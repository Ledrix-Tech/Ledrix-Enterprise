<?php

namespace App\Models\Central;

use App\Casts\LegacyEncryptedString;
use Illuminate\Database\Eloquent\Model;

class PlatformSsoSetting extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_sso_settings';

    protected $fillable = [
        'enabled',
        'provider_name',
        'issuer_url',
        'client_id',
        'client_secret',
        'redirect_uri',
        'scopes',
        'audience',
        'updated_by',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'client_secret' => LegacyEncryptedString::class,
    ];
}
