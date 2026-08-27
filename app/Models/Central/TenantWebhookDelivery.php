<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantWebhookDelivery extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_webhook_deliveries';

    protected $fillable = [
        'tenant_id',
        'endpoint_id',
        'event',
        'payload',
        'status',
        'attempts',
        'response_code',
        'response_body',
        'next_retry_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload'       => 'array',
        'next_retry_at' => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(TenantWebhookEndpoint::class, 'endpoint_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'retrying'], true);
    }
}
