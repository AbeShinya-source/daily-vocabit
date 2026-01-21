<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // 月間学習日数バッジ
            [
                'key' => 'monthly_days_bronze',
                'category' => 'monthly_days',
                'tier' => 'bronze',
                'name' => '月間10日学習',
                'description' => '1ヶ月で10日以上学習',
                'icon' => 'calendar',
                'threshold' => 10,
                'sort_order' => 1,
            ],
            [
                'key' => 'monthly_days_silver',
                'category' => 'monthly_days',
                'tier' => 'silver',
                'name' => '月間20日学習',
                'description' => '1ヶ月で20日以上学習',
                'icon' => 'calendar',
                'threshold' => 20,
                'sort_order' => 2,
            ],
            [
                'key' => 'monthly_days_gold',
                'category' => 'monthly_days',
                'tier' => 'gold',
                'name' => '皆勤賞',
                'description' => '1ヶ月で毎日学習',
                'icon' => 'calendar',
                'threshold' => 0, // 月の日数に応じて動的に判定
                'sort_order' => 3,
            ],

            // 月間セッション数バッジ
            [
                'key' => 'monthly_sessions_bronze',
                'category' => 'monthly_sessions',
                'tier' => 'bronze',
                'name' => '月間15セッション',
                'description' => '1ヶ月で15セッション完了',
                'icon' => 'book',
                'threshold' => 15,
                'sort_order' => 4,
            ],
            [
                'key' => 'monthly_sessions_silver',
                'category' => 'monthly_sessions',
                'tier' => 'silver',
                'name' => '月間30セッション',
                'description' => '1ヶ月で30セッション完了',
                'icon' => 'book',
                'threshold' => 30,
                'sort_order' => 5,
            ],
            [
                'key' => 'monthly_sessions_gold',
                'category' => 'monthly_sessions',
                'tier' => 'gold',
                'name' => '月間50セッション',
                'description' => '1ヶ月で50セッション完了',
                'icon' => 'book',
                'threshold' => 50,
                'sort_order' => 6,
            ],

            // 月間正答率バッジ
            [
                'key' => 'monthly_accuracy_bronze',
                'category' => 'monthly_accuracy',
                'tier' => 'bronze',
                'name' => '月間正答率70%',
                'description' => '月間平均正答率70%以上',
                'icon' => 'target',
                'threshold' => 70,
                'sort_order' => 7,
            ],
            [
                'key' => 'monthly_accuracy_silver',
                'category' => 'monthly_accuracy',
                'tier' => 'silver',
                'name' => '月間正答率80%',
                'description' => '月間平均正答率80%以上',
                'icon' => 'target',
                'threshold' => 80,
                'sort_order' => 8,
            ],
            [
                'key' => 'monthly_accuracy_gold',
                'category' => 'monthly_accuracy',
                'tier' => 'gold',
                'name' => '月間正答率90%',
                'description' => '月間平均正答率90%以上',
                'icon' => 'target',
                'threshold' => 90,
                'sort_order' => 9,
            ],

            // 月間パーフェクトバッジ
            [
                'key' => 'monthly_perfect_bronze',
                'category' => 'monthly_perfect',
                'tier' => 'bronze',
                'name' => '月間パーフェクト1回',
                'description' => '1ヶ月でパーフェクト1回達成',
                'icon' => 'star',
                'threshold' => 1,
                'sort_order' => 10,
            ],
            [
                'key' => 'monthly_perfect_silver',
                'category' => 'monthly_perfect',
                'tier' => 'silver',
                'name' => '月間パーフェクト3回',
                'description' => '1ヶ月でパーフェクト3回達成',
                'icon' => 'star',
                'threshold' => 3,
                'sort_order' => 11,
            ],
            [
                'key' => 'monthly_perfect_gold',
                'category' => 'monthly_perfect',
                'tier' => 'gold',
                'name' => '月間パーフェクト5回',
                'description' => '1ヶ月でパーフェクト5回達成',
                'icon' => 'star',
                'threshold' => 5,
                'sort_order' => 12,
            ],

            // 月間ハードモードバッジ
            [
                'key' => 'monthly_hard_bronze',
                'category' => 'monthly_hard',
                'tier' => 'bronze',
                'name' => '月間ハード5回',
                'description' => '1ヶ月でハードモード5回完了',
                'icon' => 'bolt',
                'threshold' => 5,
                'sort_order' => 13,
            ],
            [
                'key' => 'monthly_hard_silver',
                'category' => 'monthly_hard',
                'tier' => 'silver',
                'name' => '月間ハード15回',
                'description' => '1ヶ月でハードモード15回完了',
                'icon' => 'bolt',
                'threshold' => 15,
                'sort_order' => 14,
            ],
            [
                'key' => 'monthly_hard_gold',
                'category' => 'monthly_hard',
                'tier' => 'gold',
                'name' => '月間ハード25回',
                'description' => '1ヶ月でハードモード25回完了',
                'icon' => 'bolt',
                'threshold' => 25,
                'sort_order' => 15,
            ],

            // 月間連続学習バッジ
            [
                'key' => 'monthly_streak_bronze',
                'category' => 'monthly_streak',
                'tier' => 'bronze',
                'name' => '7日連続学習',
                'description' => '1ヶ月内で7日連続学習',
                'icon' => 'flame',
                'threshold' => 7,
                'sort_order' => 16,
            ],
            [
                'key' => 'monthly_streak_silver',
                'category' => 'monthly_streak',
                'tier' => 'silver',
                'name' => '14日連続学習',
                'description' => '1ヶ月内で14日連続学習',
                'icon' => 'flame',
                'threshold' => 14,
                'sort_order' => 17,
            ],
            [
                'key' => 'monthly_streak_gold',
                'category' => 'monthly_streak',
                'tier' => 'gold',
                'name' => '21日連続学習',
                'description' => '1ヶ月内で21日連続学習',
                'icon' => 'flame',
                'threshold' => 21,
                'sort_order' => 18,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['key' => $badge['key']],
                $badge
            );
        }

        // 古いバッジを削除
        Badge::whereNotIn('key', array_column($badges, 'key'))->delete();
    }
}
