<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function __construct(
        private BadgeService $badgeService
    ) {}

    /**
     * 指定月のバッジ一覧を取得（進捗含む）
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        // バッジを更新（新規獲得チェック）
        $newBadges = $this->badgeService->updateMonthlyBadges($user, $year, $month);

        // バッジ一覧を取得（進捗含む）
        $data = $this->badgeService->getMonthlyBadgesWithProgress($user, $year, $month);

        // 獲得済みバッジの概要
        $earnedCount = 0;
        $totalCount = 0;
        foreach ($data['categories'] as $category) {
            foreach ($category['badges'] as $badge) {
                $totalCount++;
                if ($badge['isEarned']) {
                    $earnedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $data['year'],
                'month' => $data['month'],
                'categories' => $data['categories'],
                'stats' => $data['stats'],
                'summary' => [
                    'earned' => $earnedCount,
                    'total' => $totalCount,
                ],
                'newBadges' => array_map(fn($nb) => [
                    'id' => $nb['badge']->id,
                    'key' => $nb['badge']->key,
                    'name' => $nb['badge']->name,
                    'tier' => $nb['badge']->tier,
                    'icon' => $nb['badge']->icon,
                    'year' => $nb['year'],
                    'month' => $nb['month'],
                ], $newBadges),
            ],
        ]);
    }

    /**
     * 最近獲得したバッジを取得
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->query('limit', 5);

        $recentBadges = $this->badgeService->getRecentBadges($user, $limit);
        $summary = $this->badgeService->getBadgeSummary($user);

        return response()->json([
            'success' => true,
            'data' => [
                'badges' => $recentBadges,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * 過去の月別バッジ履歴を取得
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = $this->badgeService->getMonthlyBadgeHistory($user);

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $history,
            ],
        ]);
    }
}
