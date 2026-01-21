<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Models\UserAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * ダッシュボード統計を取得
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // 総合統計
        $totalStats = $this->getTotalStats($user->id);

        // 今日の統計
        $todayStats = $this->getDailyStats($user->id, now()->format('Y-m-d'));

        // 週間統計（過去7日間）
        $weeklyStats = $this->getWeeklyStats($user->id);

        // 連続学習日数
        $streak = $this->getStreak($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalStats,
                'today' => $todayStats,
                'weekly' => $weeklyStats,
                'streak' => $streak,
            ]
        ]);
    }

    /**
     * 学習履歴を取得
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 20);

        $answers = UserAnswer::with(['question.vocabulary'])
            ->where('user_id', $user->id)
            ->orderBy('answered_at', 'desc')
            ->paginate($perPage);

        $formattedAnswers = $answers->getCollection()->map(function ($answer) {
            return [
                'id' => $answer->id,
                'questionId' => $answer->question_id,
                'questionText' => $answer->question->question_text,
                'vocabulary' => [
                    'word' => $answer->question->vocabulary->word,
                    'meaning' => $answer->question->vocabulary->meaning,
                ],
                'selectedIndex' => $answer->selected_index,
                'correctIndex' => $answer->question->correct_index,
                'isCorrect' => $answer->is_correct,
                'answeredAt' => $answer->answered_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'answers' => $formattedAnswers,
                'pagination' => [
                    'currentPage' => $answers->currentPage(),
                    'lastPage' => $answers->lastPage(),
                    'perPage' => $answers->perPage(),
                    'total' => $answers->total(),
                ]
            ]
        ]);
    }

    /**
     * セッション履歴を取得（10問セット単位）
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $sessions = QuizSession::where('user_id', $user->id)
            ->where('is_completed', true)
            ->orderBy('completed_at', 'desc')
            ->paginate($perPage);

        $formattedSessions = $sessions->getCollection()->map(function ($session) {
            return [
                'id' => $session->id,
                'difficulty' => $session->difficulty,
                'difficultyLabel' => $session->difficulty_label,
                'quizDate' => $session->quiz_date->format('Y-m-d'),
                'totalQuestions' => $session->total_questions,
                'correctCount' => $session->correct_count,
                'accuracy' => $session->accuracy,
                'completedAt' => $session->completed_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $formattedSessions,
                'pagination' => [
                    'currentPage' => $sessions->currentPage(),
                    'lastPage' => $sessions->lastPage(),
                    'perPage' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ]
            ]
        ]);
    }

    /**
     * セッション詳細を取得（リザルト画面用）
     */
    public function sessionDetail(Request $request, int $sessionId): JsonResponse
    {
        $user = $request->user();

        $session = QuizSession::with(['answers.question.vocabulary'])
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'セッションが見つかりません'
            ], 404);
        }

        $answers = $session->answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'questionId' => $answer->question_id,
                'questionText' => $answer->question->question_text,
                'questionTranslation' => $answer->question->question_translation,
                'choices' => $answer->question->choices,
                'selectedIndex' => $answer->selected_index,
                'correctIndex' => $answer->question->correct_index,
                'isCorrect' => $answer->is_correct,
                'explanation' => $answer->question->explanation,
                'vocabulary' => [
                    'word' => $answer->question->vocabulary->word,
                    'meaning' => $answer->question->vocabulary->meaning,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'difficulty' => $session->difficulty,
                    'difficultyLabel' => $session->difficulty_label,
                    'quizDate' => $session->quiz_date->format('Y-m-d'),
                    'totalQuestions' => $session->total_questions,
                    'correctCount' => $session->correct_count,
                    'accuracy' => $session->accuracy,
                    'completedAt' => $session->completed_at->toISOString(),
                ],
                'answers' => $answers,
            ]
        ]);
    }

    /**
     * カレンダー用データを取得
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();

        // その月の完了したセッションを取得
        $sessions = QuizSession::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereBetween('quiz_date', [$startDate, $endDate])
            ->get();

        // 日付ごとにグループ化
        $calendarData = [];
        foreach ($sessions as $session) {
            $date = $session->quiz_date->format('Y-m-d');
            if (!isset($calendarData[$date])) {
                $calendarData[$date] = [
                    'date' => $date,
                    'standard' => false,
                    'hard' => false,
                    'sessions' => [],
                ];
            }

            if ($session->difficulty === 1) {
                $calendarData[$date]['standard'] = true;
            } else {
                $calendarData[$date]['hard'] = true;
            }

            $calendarData[$date]['sessions'][] = [
                'id' => $session->id,
                'difficulty' => $session->difficulty,
                'difficultyLabel' => $session->difficulty_label,
                'correctCount' => $session->correct_count,
                'totalQuestions' => $session->total_questions,
                'accuracy' => $session->accuracy,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'month' => (int) $month,
                'days' => array_values($calendarData),
            ]
        ]);
    }

    /**
     * 日別統計を取得
     */
    public function daily(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = $request->query('days', 30);

        $dailyStats = UserAnswer::where('user_id', $user->id)
            ->where('answered_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(answered_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($stat) {
                return [
                    'date' => $stat->date,
                    'total' => $stat->total,
                    'correct' => $stat->correct,
                    'accuracy' => $stat->total > 0 ? round(($stat->correct / $stat->total) * 100, 1) : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $dailyStats
        ]);
    }

    /**
     * 総合統計を計算
     */
    private function getTotalStats(int $userId): array
    {
        $stats = UserAnswer::where('user_id', $userId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->first();

        return [
            'totalQuestions' => $stats->total ?? 0,
            'correctAnswers' => $stats->correct ?? 0,
            'accuracy' => $stats->total > 0 ? round(($stats->correct / $stats->total) * 100, 1) : 0,
        ];
    }

    /**
     * 日別統計を計算
     */
    private function getDailyStats(int $userId, string $date): array
    {
        $stats = UserAnswer::where('user_id', $userId)
            ->whereDate('answered_at', $date)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->first();

        return [
            'totalQuestions' => $stats->total ?? 0,
            'correctAnswers' => $stats->correct ?? 0,
            'accuracy' => $stats->total > 0 ? round(($stats->correct / $stats->total) * 100, 1) : 0,
        ];
    }

    /**
     * 週間統計を計算（過去7日間の日別データ）
     */
    private function getWeeklyStats(int $userId): array
    {
        $stats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayStat = UserAnswer::where('user_id', $userId)
                ->whereDate('answered_at', $date)
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
                )
                ->first();

            $stats[] = [
                'date' => $date,
                'dayOfWeek' => now()->subDays($i)->format('D'),
                'total' => $dayStat->total ?? 0,
                'correct' => $dayStat->correct ?? 0,
            ];
        }

        return $stats;
    }

    /**
     * 連続学習日数を計算
     */
    private function getStreak(int $userId): int
    {
        $streak = 0;
        $date = now();

        while (true) {
            $hasAnswers = UserAnswer::where('user_id', $userId)
                ->whereDate('answered_at', $date->format('Y-m-d'))
                ->exists();

            if ($hasAnswers) {
                $streak++;
                $date = $date->subDay();
            } else {
                // 今日まだ学習していない場合は昨日から数える
                if ($streak === 0 && $date->isToday()) {
                    $date = $date->subDay();
                    continue;
                }
                break;
            }
        }

        return $streak;
    }
}
