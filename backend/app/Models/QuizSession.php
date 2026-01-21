<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSession extends Model
{
    protected $fillable = [
        'user_id',
        'difficulty',
        'quiz_date',
        'total_questions',
        'correct_count',
        'is_completed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'difficulty' => 'integer',
        'total_questions' => 'integer',
        'correct_count' => 'integer',
        'is_completed' => 'boolean',
        'quiz_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function getAccuracyAttribute(): float
    {
        if ($this->total_questions === 0) {
            return 0;
        }
        return round(($this->correct_count / $this->total_questions) * 100, 1);
    }

    public function getDifficultyLabelAttribute(): string
    {
        return $this->difficulty === 1 ? 'Standard' : 'Hard';
    }
}
