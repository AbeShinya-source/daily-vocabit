<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AnswerController extends Controller
{
    /**
     * 回答を記録
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer|exists:questions,id',
            'selected_index' => 'required|integer|min:0|max:3',
            'user_id' => 'nullable|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        // 問題を取得
        $question = Question::find($request->question_id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => '問題が見つかりませんでした'
            ], 404);
        }

        // 正解判定
        $isCorrect = $request->selected_index === $question->correct_index;

        // 認証ユーザーがいればそのIDを使用、なければリクエストのuser_id
        $userId = $request->user()?->id ?? $request->user_id;

        // 回答を記録
        $answer = UserAnswer::create([
            'user_id' => $userId,
            'question_id' => $request->question_id,
            'selected_index' => $request->selected_index,
            'is_correct' => $isCorrect,
            'answered_at' => now()
        ]);

        // 問題の使用回数を増やす
        $question->increment('usage_count');

        return response()->json([
            'success' => true,
            'message' => '回答を記録しました',
            'data' => [
                'answer_id' => $answer->id,
                'is_correct' => $isCorrect,
                'correct_index' => $question->correct_index,
                'explanation' => $question->explanation
            ]
        ], 201);
    }

    /**
     * ユーザーの回答履歴を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_idが必要です'
            ], 400);
        }

        $answers = UserAnswer::with('question.vocabulary')
            ->where('user_id', $userId)
            ->orderBy('answered_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'question_id' => $answer->question_id,
                    'question_text' => $answer->question->question_text,
                    'vocabulary_word' => $answer->question->vocabulary->word,
                    'selected_index' => $answer->selected_index,
                    'is_correct' => $answer->is_correct,
                    'answered_at' => $answer->answered_at->toISOString()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'answers' => $answers,
                'total' => $answers->count()
            ]
        ]);
    }
}
