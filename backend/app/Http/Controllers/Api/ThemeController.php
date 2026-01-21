<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * 今日のテーマを取得
     */
    public function today()
    {
        $theme = Theme::getTodayTheme();

        if (!$theme) {
            return response()->json([
                'success' => false,
                'message' => '本日のテーマが見つかりません',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '本日のテーマを取得しました',
            'data' => [
                'theme' => [
                    'title_en' => $theme->title_en,
                    'title_ja' => $theme->title_ja,
                    'description' => $theme->description,
                    'date' => $theme->date->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * 指定日のテーマを取得
     */
    public function getByDate(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $theme = Theme::getThemeForDate($date);

        if (!$theme) {
            return response()->json([
                'success' => false,
                'message' => "{$date} のテーマが見つかりません",
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'テーマを取得しました',
            'data' => [
                'theme' => [
                    'title_en' => $theme->title_en,
                    'title_ja' => $theme->title_ja,
                    'description' => $theme->description,
                    'date' => $theme->date->format('Y-m-d'),
                ]
            ]
        ]);
    }
}
