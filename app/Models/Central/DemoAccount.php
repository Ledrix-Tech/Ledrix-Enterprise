<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DemoAccount extends Authenticatable
{
    protected $connection = 'central';

    protected $table = 'demo_accounts';

    protected $fillable = [
        'name',
        'email',
        'password',
        'company',
        'tenant_id',
        'status',
        'last_login_at',
        'last_login_ip',
        'expires_at',
        'meta',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'expires_at'    => 'datetime',
        'meta'          => 'array',
    ];

    public function sandboxTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function isActive(): bool
    {
        if (strtolower((string) $this->status) !== 'active') {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->isFuture();
    }
}
