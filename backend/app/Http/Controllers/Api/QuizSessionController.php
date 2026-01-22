<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizSessionController extends Controller
{
    /**
     * クイズセッションを開始
     * 同じ日・同じ難易度のセッションが既にある場合は上書き
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'difficulty' => 'required|integer|in:1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $today = now()->startOfDay();

        // 同じ日・同じ難易度の既存セッションを検索
        $existingSession = QuizSession::where('user_id', $user->id)
            ->whereDate('quiz_date', $today)
            ->where('difficulty', $request->difficulty)
            ->first();

        if ($existingSession) {
            // 既存セッションをリセットして再利用
            $existingSession->update([
                'correct_count' => 0,
                'is_completed' => false,
                'started_at' => now(),
                'completed_at' => null,
            ]);
            $session = $existingSession;
        } else {
            // 新規セッション作成
            $session = QuizSession::create([
                'user_id' => $user->id,
                'difficulty' => $request->difficulty,
                'quiz_date' => $today,
                'total_questions' => 10,
                'correct_count' => 0,
                'is_completed' => false,
                'started_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sessionId' => $session->id,
            ]
        ], 201);
    }

    /**
     * クイズセッションを完了
     */
    public function complete(Request $request, int $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'correct_count' => 'required|integer|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        $session = QuizSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'セッションが見つかりません'
            ], 404);
        }

        if ($session->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'セッションは既に完了しています'
            ], 400);
        }

        $session->update([
            'correct_count' => $request->correct_count,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'sessionId' => $session->id,
                'correctCount' => $session->correct_count,
                'totalQuestions' => $session->total_questions,
                'accuracy' => $session->accuracy,
            ]
        ]);
    }

    /**
     * 現在のセッションを取得
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        $session = QuizSession::where('user_id', $user->id)
            ->where('is_completed', false)
            ->orderBy('started_at', 'desc')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sessionId' => $session->id,
                'difficulty' => $session->difficulty,
                'quizDate' => $session->quiz_date->format('Y-m-d'),
                'startedAt' => $session->started_at->toISOString(),
            ]
        ]);
    }
}
