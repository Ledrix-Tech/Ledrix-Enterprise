<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class PlatformThemeSetting extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_theme_settings';

    protected $fillable = [
        'primary_color',
        'secondary_color',
        'updated_by',
    ];
}
