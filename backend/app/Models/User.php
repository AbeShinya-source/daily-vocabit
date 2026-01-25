<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    /**
     * 管理者かどうかを判定
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * ユーザーの回答履歴
     */
    public function answers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    /**
     * ユーザーの学習進捗
     */
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * ユーザーのクイズセッション
     */
    public function quizSessions()
    {
        return $this->hasMany(QuizSession::class);
    }

    /**
     * ユーザーの獲得バッジ
     */
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }
}
