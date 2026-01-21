<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\QuizSession;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserBadge;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * 指定月のバッジを更新
     */
    public function updateMonthlyBadges(User $user, int $year, int $month): array
    {
        $newBadges = [];
        $badges = Badge::orderBy('sort_order')->get();

        // 月の統計を計算
        $monthStats = $this->calculateMonthlyStats($user, $year, $month);

        foreach ($badges as $badge) {
            // 既に獲得済みならスキップ
            if ($this->hasEarnedBadge($user, $badge, $year, $month)) {
                continue;
            }

            // バッジ条件をチェック
            if ($this->checkMonthlyBadgeCondition($badge, $monthStats, $year, $month)) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'year' => $year,
                    'month' => $month,
                    'earned_at' => now(),
                ]);
                $newBadges[] = [
                    'badge' => $badge,
                    'year' => $year,
                    'month' => $month,
                ];
            }
        }

        return $newBadges;
    }

    /**
     * バッジを既に獲得しているかチェック
     */
    private function hasEarnedBadge(User $user, Badge $badge, int $year, int $month): bool
    {
        return UserBadge::where('user_id', $user->id)
            ->where('badge_id', $badge->id)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();
    }

    /**
     * 月間バッジの条件をチェック
     */
    private function checkMonthlyBadgeCondition(Badge $badge, array $stats, int $year, int $month): bool
    {
        return match ($badge->category) {
            'monthly_days' => $this->checkMonthlyDaysBadge($badge, $stats, $year, $month),
            'monthly_sessions' => $stats['sessions'] >= $badge->threshold,
            'monthly_accuracy' => $stats['sessions'] > 0 && $stats['accuracy'] >= $badge->threshold,
            'monthly_perfect' => $stats['perfect'] >= $badge->threshold,
            'monthly_hard' => $stats['hard_sessions'] >= $badge->threshold,
            'monthly_streak' => $stats['max_streak'] >= $badge->threshold,
            default => false,
        };
    }

    /**
     * 月間学習日数バッジをチェック
     */
    private function checkMonthlyDaysBadge(Badge $badge, array $stats, int $year, int $month): bool
    {
        if ($badge->tier === 'gold') {
            // 皆勤賞：月の日数と学習日数が一致
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            return $stats['days'] >= $daysInMonth;
        }
        return $stats['days'] >= $badge->threshold;
    }

    /**
     * 月間統計を計算
     */
    private function calculateMonthlyStats(User $user, int $year, int $month): array
    {
        $startDate = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // セッション一覧
        $sessions = QuizSession::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereBetween('quiz_date', [$startDate, $endDate])
            ->get();

        // 学習日数（ユニークな日付数）
        $days = $sessions->pluck('quiz_date')->map(fn($d) => $d->format('Y-m-d'))->unique()->count();

        // セッション数
        $sessionCount = $sessions->count();

        // 正答率
        $totalQuestions = $sessions->sum('total_questions');
        $totalCorrect = $sessions->sum('correct_count');
        $accuracy = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 1) : 0;

        // パーフェクト回数
        $perfect = $sessions->filter(fn($s) => $s->correct_count === $s->total_questions)->count();

        // ハードモード回数
        $hardSessions = $sessions->filter(fn($s) => $s->difficulty === 2)->count();

        // 月内最大連続日数
        $maxStreak = $this->calculateMonthlyMaxStreak($sessions);

        return [
            'days' => $days,
            'sessions' => $sessionCount,
            'accuracy' => $accuracy,
            'perfect' => $perfect,
            'hard_sessions' => $hardSessions,
            'max_streak' => $maxStreak,
            'total_questions' => $totalQuestions,
            'total_correct' => $totalCorrect,
        ];
    }

    /**
     * 月内の最大連続学習日数を計算
     */
    private function calculateMonthlyMaxStreak($sessions): int
    {
        $dates = $sessions->pluck('quiz_date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        if (empty($dates)) {
            return 0;
        }

        $maxStreak = 1;
        $currentStreak = 1;

        for ($i = 1; $i < count($dates); $i++) {
            $prevDate = new \DateTime($dates[$i - 1]);
            $currDate = new \DateTime($dates[$i]);
            $diff = $prevDate->diff($currDate)->days;

            if ($diff === 1) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 1;
            }
        }

        return $maxStreak;
    }

    /**
     * 指定月のバッジ一覧を取得（進捗含む）
     */
    public function getMonthlyBadgesWithProgress(User $user, int $year, int $month): array
    {
        $badges = Badge::orderBy('sort_order')->get();
        $earnedBadges = UserBadge::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->pluck('badge_id')
            ->toArray();

        // 月間統計を計算
        $stats = $this->calculateMonthlyStats($user, $year, $month);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $result = [];
        $categories = $badges->groupBy('category');

        foreach ($categories as $category => $categoryBadges) {
            $currentValue = $this->getCategoryCurrentValue($category, $stats);
            $categoryData = [
                'category' => $category,
                'categoryLabel' => $this->getCategoryLabel($category),
                'currentValue' => $currentValue,
                'badges' => [],
            ];

            foreach ($categoryBadges as $badge) {
                $isEarned = in_array($badge->id, $earnedBadges);
                $earnedAt = null;

                if ($isEarned) {
                    $userBadge = UserBadge::where('user_id', $user->id)
                        ->where('badge_id', $badge->id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->first();
                    $earnedAt = $userBadge?->earned_at?->toISOString();
                }

                // 閾値（皆勤賞の場合は月の日数）
                $threshold = $badge->tier === 'gold' && $badge->category === 'monthly_days'
                    ? $daysInMonth
                    : $badge->threshold;

                $categoryData['badges'][] = [
                    'id' => $badge->id,
                    'key' => $badge->key,
                    'tier' => $badge->tier,
                    'name' => $badge->name,
                    'description' => $badge->description,
                    'icon' => $badge->icon,
                    'threshold' => $threshold,
                    'isEarned' => $isEarned,
                    'earnedAt' => $earnedAt,
                    'progress' => $threshold > 0 ? min(100, round($currentValue / $threshold * 100)) : 0,
                ];
            }

            $result[] = $categoryData;
        }

        return [
            'categories' => $result,
            'stats' => $stats,
            'year' => $year,
            'month' => $month,
        ];
    }

    /**
     * カテゴリの現在値を取得
     */
    private function getCategoryCurrentValue(string $category, array $stats): int|float
    {
        return match ($category) {
            'monthly_days' => $stats['days'],
            'monthly_sessions' => $stats['sessions'],
            'monthly_accuracy' => $stats['accuracy'],
            'monthly_perfect' => $stats['perfect'],
            'monthly_hard' => $stats['hard_sessions'],
            'monthly_streak' => $stats['max_streak'],
            default => 0,
        };
    }

    /**
     * カテゴリラベルを取得
     */
    private function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'monthly_days' => '学習日数',
            'monthly_sessions' => 'セッション数',
            'monthly_accuracy' => '正答率',
            'monthly_perfect' => 'パーフェクト',
            'monthly_hard' => 'ハードモード',
            'monthly_streak' => '連続学習',
            default => $category,
        };
    }

    /**
     * 最近獲得したバッジを取得（同カテゴリ・同月で上位バッジがある場合は下位を除外）
     */
    public function getRecentBadges(User $user, int $limit = 5): array
    {
        $userBadges = UserBadge::with('badge')
            ->where('user_id', $user->id)
            ->orderBy('earned_at', 'desc')
            ->get();

        // ティアの優先度（高い方が優先）
        $tierPriority = ['gold' => 3, 'silver' => 2, 'bronze' => 1];

        // 同じカテゴリ・年・月でグループ化し、最高ティアのみを保持
        $filtered = [];
        $seenCategoryMonths = [];

        foreach ($userBadges as $ub) {
            $key = "{$ub->badge->category}-{$ub->year}-{$ub->month}";

            if (!isset($seenCategoryMonths[$key])) {
                // まだこのカテゴリ・月の組み合わせを見ていない
                $seenCategoryMonths[$key] = [
                    'tier' => $ub->badge->tier,
                    'index' => count($filtered),
                ];
                $filtered[] = $ub;
            } else {
                // 既にこのカテゴリ・月の組み合わせがある場合、ティアを比較
                $existingTier = $seenCategoryMonths[$key]['tier'];
                $currentTier = $ub->badge->tier;

                if ($tierPriority[$currentTier] > $tierPriority[$existingTier]) {
                    // 現在のバッジの方が上位なので置き換え
                    $index = $seenCategoryMonths[$key]['index'];
                    $filtered[$index] = $ub;
                    $seenCategoryMonths[$key]['tier'] = $currentTier;
                }
                // 下位または同じティアの場合はスキップ
            }
        }

        // 獲得日時で再ソートしてlimit件取得
        usort($filtered, fn($a, $b) => $b->earned_at <=> $a->earned_at);
        $filtered = array_slice($filtered, 0, $limit);

        return array_map(function ($ub) {
            return [
                'id' => $ub->badge->id,
                'key' => $ub->badge->key,
                'tier' => $ub->badge->tier,
                'name' => $ub->badge->name,
                'description' => $ub->badge->description,
                'icon' => $ub->badge->icon,
                'year' => $ub->year,
                'month' => $ub->month,
                'earnedAt' => $ub->earned_at->toISOString(),
            ];
        }, $filtered);
    }

    /**
     * バッジ獲得サマリーを取得
     */
    public function getBadgeSummary(User $user): array
    {
        $totalBadgeTypes = Badge::count();
        $earnedCount = UserBadge::where('user_id', $user->id)->count();
        $uniqueEarned = UserBadge::where('user_id', $user->id)
            ->distinct('badge_id')
            ->count('badge_id');

        return [
            'totalTypes' => $totalBadgeTypes,
            'uniqueEarned' => $uniqueEarned,
            'totalEarned' => $earnedCount,
        ];
    }

    /**
     * 過去の月別バッジ獲得一覧を取得
     */
    public function getMonthlyBadgeHistory(User $user): array
    {
        $history = UserBadge::with('badge')
            ->where('user_id', $user->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(fn($ub) => "{$ub->year}-{$ub->month}");

        $result = [];
        foreach ($history as $key => $badges) {
            [$year, $month] = explode('-', $key);
            $result[] = [
                'year' => (int) $year,
                'month' => (int) $month,
                'badges' => $badges->map(fn($ub) => [
                    'id' => $ub->badge->id,
                    'key' => $ub->badge->key,
                    'tier' => $ub->badge->tier,
                    'name' => $ub->badge->name,
                    'icon' => $ub->badge->icon,
                    'earnedAt' => $ub->earned_at->toISOString(),
                ])->toArray(),
            ];
        }

        return $result;
    }
}
