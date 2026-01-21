<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    /**
     * 今日の問題を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    private const QUESTIONS_PER_DAY = 10;

    public function getDaily(Request $request): JsonResponse
    {
        $difficulty = $request->query('difficulty', 1);
        $date = $request->query('date', now()->format('Y-m-d'));

        // 日付と難易度からシード値を生成（同じ日・同じ難易度なら同じ問題セット）
        $seed = crc32($date . '_' . $difficulty);

        // 1. 今日の日付で生成された問題を取得
        $questions = Question::whereDate('generated_date', $date)
            ->where('difficulty', $difficulty)
            ->where('is_active', true)
            ->with('vocabulary')
            ->limit(self::QUESTIONS_PER_DAY)
            ->get();

        // 2. 問題数が足りない場合、過去の問題から決定的に補完
        $remainingCount = self::QUESTIONS_PER_DAY - $questions->count();

        if ($remainingCount > 0) {
            $usedIds = $questions->pluck('id')->toArray();

            // 全ての候補問題を取得
            $candidateQuestions = Question::where('difficulty', $difficulty)
                ->where('is_active', true)
                ->whereNotIn('id', $usedIds)
                ->whereNotNull('question_translation') // 和訳がある問題のみ
                ->with('vocabulary')
                ->orderBy('id') // 決定的な順序で取得
                ->get();

            // シード値を使って決定的にシャッフル
            $candidateArray = $candidateQuestions->all();
            mt_srand($seed);
            shuffle($candidateArray);

            $fallbackQuestions = collect(array_slice($candidateArray, 0, $remainingCount));
            $questions = $questions->concat($fallbackQuestions);
        }

        // 問題が存在しない場合
        if ($questions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '問題が見つかりませんでした',
                'data' => []
            ], 404);
        }

        // シード値を使って決定的にシャッフル（同じ日なら同じ順序）
        $questionsArray = $questions->all();
        mt_srand($seed);
        shuffle($questionsArray);

        $formattedQuestions = collect($questionsArray)->map(function ($question) {
            return [
                'id' => $question->id,
                'type' => $question->type,
                'difficulty' => $question->difficulty,
                'questionText' => $question->question_text,
                'questionTranslation' => $question->question_translation,
                'choices' => $question->choices, // Eloquent cast handles JSON decode
                'correctIndex' => $question->correct_index,
                'explanation' => $question->explanation,
                'vocabulary' => [
                    'word' => $question->vocabulary->word,
                    'meaning' => $question->vocabulary->meaning,
                    'type' => $question->vocabulary->type,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => '問題を取得しました',
            'data' => [
                'questions' => $formattedQuestions,
                'totalQuestions' => $formattedQuestions->count(),
                'date' => $date,
                'difficulty' => $difficulty
            ]
        ]);
    }

    /**
     * 問題一覧を取得（管理者用）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Question::with('vocabulary');

        // フィルタリング
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->has('date')) {
            $query->where('generated_date', $request->date);
        }

        // ページネーション
        $perPage = $request->query('per_page', 10);
        $questions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    /**
     * 問題詳細を取得
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $question = Question::with('vocabulary')->find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => '問題が見つかりませんでした'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $question->id,
                'type' => $question->type,
                'difficulty' => $question->difficulty,
                'questionText' => $question->question_text,
                'questionTranslation' => $question->question_translation,
                'choices' => $question->choices, // Eloquent cast handles JSON decode
                'correctIndex' => $question->correct_index,
                'explanation' => $question->explanation,
                'vocabulary' => [
                    'word' => $question->vocabulary->word,
                    'meaning' => $question->vocabulary->meaning,
                ]
            ]
        ]);
    }
}
