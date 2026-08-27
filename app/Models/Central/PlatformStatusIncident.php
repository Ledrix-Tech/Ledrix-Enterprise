<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformStatusIncident extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_status_incidents';

    protected $fillable = [
        'title',
        'body',
        'severity',
        'status',
        'started_at',
        'resolved_at',
        'created_by',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status !== 'resolved';
    }
}
