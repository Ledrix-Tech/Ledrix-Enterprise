<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PerformanceBonus extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'seller_id',
        'brand_id',
        'target_revenue',
        'bonus_amount',
        'period_start',
        'period_end',
        'currency',
        'status'
    ];

    protected $casts = [
        'target_revenue' => 'decimal:2',
        'bonus_amount'   => 'decimal:2',
        'period_start'   => 'date',
        'period_end'     => 'date',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
