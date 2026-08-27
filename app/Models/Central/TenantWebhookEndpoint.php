<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TenantWebhookEndpoint extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_webhook_endpoints';

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'secret',
        'events',
        'enabled',
    ];

    protected $hidden = ['secret'];

    protected $casts = [
        'events'  => 'array',
        'enabled' => 'boolean',
    ];

    public const AVAILABLE_EVENTS = [
        'invoice.paid',
        'membership.activated',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(TenantWebhookDelivery::class, 'endpoint_id');
    }

    public function listensFor(string $event): bool
    {
        $events = $this->events ?? [];

        return in_array('*', $events, true) || in_array($event, $events, true);
    }

    public static function generateSecret(): string
    {
        return 'whsec_'.Str::random(40);
    }
}
