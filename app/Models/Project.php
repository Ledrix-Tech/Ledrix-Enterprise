<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Notifications\Notifiable;

class Project extends Model
{
    use BelongsToTenant, Notifiable;

    protected $fillable = [
        'tenant_id',
        'title',
        'lead_id',
        'order_id',
        'front_seller_id',
        'owner_seller_id',
        'status',
        'start_date',
        'due_date',
        'description',
        'meta',
        'pm_assigned_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'start_date' => 'date',
        'due_date' => 'date',
        'pm_assigned_at' => 'datetime',
    ];

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function projectManager()
    {
        return $this->belongsTo(Seller::class, 'owner_seller_id');
    }

    public function frontSeller()
    {
        return $this->belongsTo(Seller::class, 'front_seller_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function completedTasksCount(): int
    {
        return $this->tasks->where('status', 'completed')->count();
    }

    public function progressPercent(): int
    {
        $total = $this->tasks->count();
        if ($total > 0) {
            return (int) round(($this->completedTasksCount() / $total) * 100);
        }

        return match ($this->status) {
            'completed' => 100,
            'in_progress' => 50,
            'cancelled' => 0,
            default => 10,
        };
    }
}
