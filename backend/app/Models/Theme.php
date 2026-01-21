<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'title_en',
        'title_ja',
        'description',
        'date',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * 指定日のテーマを取得
     */
    public static function getThemeForDate(string $date): ?Theme
    {
        return static::whereDate('date', $date)
            ->where('is_active', true)
            ->first();
    }

    /**
     * 今日のテーマを取得
     */
    public static function getTodayTheme(): ?Theme
    {
        return static::getThemeForDate(now()->format('Y-m-d'));
    }
}
