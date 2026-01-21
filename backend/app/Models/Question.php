<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'vocabulary_id',
        'type',
        'difficulty',
        'question_text',
        'question_translation',
        'choices',
        'correct_index',
        'explanation',
        'generated_date',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'choices' => 'array',
        'difficulty' => 'integer',
        'correct_index' => 'integer',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'generated_date' => 'date',
    ];

    /**
     * 紐付けられた単語・イディオム
     */
    public function vocabulary()
    {
        return $this->belongsTo(Vocabulary::class);
    }

    /**
     * この問題への回答履歴
     */
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}
