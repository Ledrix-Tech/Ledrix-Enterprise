<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'admin_id',
        'plan_id_at_import',
        'brand_id',
        'seller_id',
        'multi_brand',
        'enter_live_pipeline',
        'original_filename',
        'stored_path',
        'status',
        'row_count',
        'summary',
        'mapping',
        'decisions',
        'duplicate_strategy',
        'committed_at',
        'rolled_back_at',
    ];

    protected $casts = [
        'multi_brand'          => 'boolean',
        'enter_live_pipeline'  => 'boolean',
        'summary'              => 'array',
        'mapping'              => 'array',
        'decisions'            => 'array',
        'committed_at'         => 'datetime',
        'rolled_back_at'       => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'import_batch_id');
    }
}
