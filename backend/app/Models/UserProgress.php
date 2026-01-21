<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;

    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'date',
        'type',
        'difficulty',
        'total_questions',
        'correct_count',
        'score_percent',
        'study_time',
    ];

    protected $casts = [
        'date' => 'date',
        'difficulty' => 'integer',
        'total_questions' => 'integer',
        'correct_count' => 'integer',
        'score_percent' => 'integer',
        'study_time' => 'integer',
    ];

    /**
     * 進捗を記録したユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
