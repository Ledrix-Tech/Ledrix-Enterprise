<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlatformStatusSubscriber extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_status_subscribers';

    protected $fillable = [
        'email',
        'token',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public static function issue(string $email): self
    {
        return static::query()->updateOrCreate(
            ['email' => strtolower(trim($email))],
            [
                'token'        => Str::random(48),
                'confirmed_at' => null,
            ]
        );
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
