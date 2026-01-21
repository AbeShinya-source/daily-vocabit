<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_session_id',
        'question_id',
        'selected_index',
        'is_correct',
        'answered_at',
    ];

    protected $casts = [
        'selected_index' => 'integer',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    /**
     * 回答したユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 回答した問題
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * クイズセッション
     */
    public function quizSession()
    {
        return $this->belongsTo(QuizSession::class);
    }
}
