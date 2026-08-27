<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class PlatformStatusComponent extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_status_components';

    protected $fillable = [
        'key',
        'name',
        'status',
        'description',
        'sort_order',
    ];

    public const STATUSES = [
        'operational',
        'degraded',
        'partial_outage',
        'major_outage',
        'maintenance',
    ];

    public static function ensureDefaults(): void
    {
        $defaults = [
            ['key' => 'website', 'name' => 'Marketing website', 'sort_order' => 10],
            ['key' => 'api', 'name' => 'Public API', 'sort_order' => 20],
            ['key' => 'admin', 'name' => 'Admin CRM', 'sort_order' => 30],
            ['key' => 'seller', 'name' => 'Seller portal', 'sort_order' => 40],
            ['key' => 'client', 'name' => 'Client portal', 'sort_order' => 50],
            ['key' => 'billing', 'name' => 'Billing & checkout', 'sort_order' => 60],
        ];

        foreach ($defaults as $row) {
            static::query()->firstOrCreate(
                ['key' => $row['key']],
                [
                    'name'        => $row['name'],
                    'status'      => 'operational',
                    'sort_order'  => $row['sort_order'],
                    'description' => null,
                ]
            );
        }
    }
}
