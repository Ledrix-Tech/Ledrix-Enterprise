<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFxRate extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_fx_rates';

    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'effective_at',
        'source',
        'updated_by',
    ];

    protected $casts = [
        'rate'         => 'float',
        'effective_at' => 'datetime',
    ];

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'updated_by');
    }
}
