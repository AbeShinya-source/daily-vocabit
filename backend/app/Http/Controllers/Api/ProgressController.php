<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProgressController extends Controller
{
    /**
     * 学習進捗を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_idが必要です'
            ], 400);
        }

        // 日付範囲でフィルタ（オプション）
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date', now()->format('Y-m-d'));

        $query = UserProgress::where('user_id', $userId);

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $progress = $query->orderBy('date', 'desc')->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'type' => $item->type,
                'difficulty' => $item->difficulty,
                'total_questions' => $item->total_questions,
                'correct_count' => $item->correct_count,
                'score_percent' => $item->score_percent,
                'study_time' => $item->study_time
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'progress' => $progress,
                'total_records' => $progress->count()
            ]
        ]);
    }

    /**
     * 学習進捗を保存
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'type' => 'required|in:WORD,IDIOM',
            'difficulty' => 'required|integer|min:1|max:3',
            'total_questions' => 'required|integer|min:0',
            'correct_count' => 'required|integer|min:0',
            'study_time' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        // 正答率を計算
        $scorePercent = $request->total_questions > 0
            ? round(($request->correct_count / $request->total_questions) * 100)
            : 0;

        // 進捗を保存（同じ日・モード・難易度なら更新）
        $progress = UserProgress::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'date' => $request->date,
                'type' => $request->type,
                'difficulty' => $request->difficulty
            ],
            [
                'total_questions' => $request->total_questions,
                'correct_count' => $request->correct_count,
                'score_percent' => $scorePercent,
                'study_time' => $request->study_time
            ]
        );

        return response()->json([
            'success' => true,
            'message' => '学習進捗を保存しました',
            'data' => [
                'id' => $progress->id,
                'score_percent' => $scorePercent
            ]
        ], 201);
    }
}
