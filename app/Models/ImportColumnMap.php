<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ImportColumnMap extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'admin_id',
        'mapping',
    ];

    protected $casts = [
        'mapping' => 'array',
    ];
}
