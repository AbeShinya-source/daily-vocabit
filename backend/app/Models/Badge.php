<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'key',
        'category',
        'tier',
        'name',
        'description',
        'icon',
        'threshold',
        'sort_order',
    ];

    protected $casts = [
        'threshold' => 'integer',
        'sort_order' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function getTierOrderAttribute(): int
    {
        return match ($this->tier) {
            'bronze' => 1,
            'silver' => 2,
            'gold' => 3,
            default => 0,
        };
    }
}
