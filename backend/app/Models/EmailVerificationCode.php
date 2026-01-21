<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerificationCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'name',
        'password',
        'expires_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * 認証コードが有効期限内かどうか
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * 6桁の認証コードを生成
     */
    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
