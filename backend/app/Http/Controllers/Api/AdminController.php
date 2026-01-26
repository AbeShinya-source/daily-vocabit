<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GenerationLog;
use App\Models\Question;
use App\Models\Vocabulary;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * 日ごとの問題一覧を取得
     */
    public function questions(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $difficulty = $request->query('difficulty');

        $query = Question::with('vocabulary')
            ->whereDate('generated_date', $date)
            ->orderBy('difficulty')
            ->orderBy('id');

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        $questions = $query->get();

        return response()->json([
            'date' => $date,
            'questions' => $questions,
            'total' => $questions->count(),
        ]);
    }

    /**
     * 利用可能な問題の日付一覧を取得
     */
    public function questionDates()
    {
        $dates = Question::selectRaw('DATE(generated_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['dates' => $dates]);
    }

    /**
     * 語彙一覧を取得
     */
    public function vocabularies(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $search = $request->query('search');
        $type = $request->query('type');
        $difficulty = $request->query('difficulty');

        $query = Vocabulary::query()->orderBy('id', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('word', 'like', "%{$search}%")
                  ->orWhere('meaning', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        $vocabularies = $query->paginate($perPage);

        return response()->json($vocabularies);
    }

    /**
     * 語彙詳細を取得
     */
    public function vocabularyShow($id)
    {
        $vocabulary = Vocabulary::with('questions')->findOrFail($id);

        return response()->json($vocabulary);
    }

    /**
     * 語彙を作成
     */
    public function vocabularyStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'word' => 'required|string|max:255',
            'type' => 'required|in:WORD,IDIOM',
            'difficulty' => 'required|integer|min:1|max:3',
            'meaning' => 'required|string|max:500',
            'part_of_speech' => 'nullable|string|max:50',
            'example_sentence' => 'nullable|string|max:1000',
            'synonym' => 'nullable|string|max:255',
            'antonym' => 'nullable|string|max:255',
            'frequency' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vocabulary = Vocabulary::create($validator->validated());

        return response()->json($vocabulary, 201);
    }

    /**
     * 語彙を更新
     */
    public function vocabularyUpdate(Request $request, $id)
    {
        $vocabulary = Vocabulary::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'word' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:WORD,IDIOM',
            'difficulty' => 'sometimes|required|integer|min:1|max:3',
            'meaning' => 'sometimes|required|string|max:500',
            'part_of_speech' => 'nullable|string|max:50',
            'example_sentence' => 'nullable|string|max:1000',
            'synonym' => 'nullable|string|max:255',
            'antonym' => 'nullable|string|max:255',
            'frequency' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vocabulary->update($validator->validated());

        return response()->json($vocabulary);
    }

    /**
     * 語彙を削除
     */
    public function vocabularyDestroy($id)
    {
        $vocabulary = Vocabulary::findOrFail($id);

        // 関連する問題があるか確認
        if ($vocabulary->questions()->exists()) {
            return response()->json([
                'message' => 'この語彙には関連する問題があるため削除できません。'
            ], 422);
        }

        $vocabulary->delete();

        return response()->json(['message' => '削除しました。']);
    }

    /**
     * ダッシュボード統計
     */
    public function dashboard()
    {
        $today = now()->format('Y-m-d');

        return response()->json([
            'total_vocabularies' => Vocabulary::count(),
            'total_questions' => Question::count(),
            'today_questions' => Question::whereDate('generated_date', $today)->count(),
            'vocabularies_by_difficulty' => [
                1 => Vocabulary::where('difficulty', 1)->count(),
                2 => Vocabulary::where('difficulty', 2)->count(),
                3 => Vocabulary::where('difficulty', 3)->count(),
            ],
            'vocabularies_by_type' => [
                'WORD' => Vocabulary::where('type', 'WORD')->count(),
                'IDIOM' => Vocabulary::where('type', 'IDIOM')->count(),
            ],
        ]);
    }

    /**
     * 問題を手動生成
     */
    public function generateQuestions(Request $request, GeminiService $geminiService)
    {
        $validator = Validator::make($request->all(), [
            'difficulty' => 'required|integer|min:1|max:2',
            'count' => 'required|integer|min:1|max:20',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $difficulty = (int) $request->difficulty;
        $count = (int) $request->count;
        $date = $request->date;

        // 既存の問題数を確認
        $existingCount = Question::where('difficulty', $difficulty)
            ->where('generated_date', $date)
            ->count();

        // 語彙を選択
        $vocabularies = $this->selectVocabulariesForGeneration($difficulty, $count);

        if (count($vocabularies) < $count) {
            return response()->json([
                'error' => 'データベースに十分な語彙がありません',
                'available' => count($vocabularies),
                'requested' => $count,
            ], 422);
        }

        // 問題生成
        $startTime = microtime(true);
        $result = $geminiService->generateMultipleQuestions($vocabularies);
        $endTime = microtime(true);

        // データベースに保存
        $savedCount = 0;
        $failedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($result['results'] as $item) {
                if ($item['success']) {
                    $vocab = collect($vocabularies)->firstWhere('id', $item['vocabulary_id']);
                    $vocabType = $vocab['type'] ?? 'WORD';

                    Question::create([
                        'vocabulary_id' => $item['vocabulary_id'],
                        'type' => $vocabType,
                        'difficulty' => $difficulty,
                        'question_text' => $item['question']['question_text'],
                        'question_translation' => $item['question']['question_translation'],
                        'choices' => $item['question']['choices'],
                        'correct_index' => $item['question']['correct_index'],
                        'explanation' => $item['question']['explanation'],
                        'generated_date' => $date,
                        'is_active' => true,
                        'usage_count' => 0
                    ]);
                    $savedCount++;
                } else {
                    $failedCount++;
                }
            }

            // 生成ログを記録
            $totalTokens = $result['total_usage']['total_tokens'];
            $estimatedCost = $this->calculateGenerationCost($result['total_usage']);

            GenerationLog::create([
                'generated_date' => $date,
                'type' => 'WORD',
                'difficulty' => $difficulty,
                'questions_count' => $savedCount,
                'ai_model' => 'gemini-1.5-flash',
                'prompt_tokens' => $result['total_usage']['prompt_tokens'],
                'completion_tokens' => $result['total_usage']['completion_tokens'],
                'total_cost' => $estimatedCost,
                'status' => $failedCount === 0 ? 'SUCCESS' : 'PARTIAL',
                'error_message' => $failedCount > 0 ? "{$failedCount} 問の生成に失敗しました" : null
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '問題生成が完了しました',
                'data' => [
                    'saved_count' => $savedCount,
                    'failed_count' => $failedCount,
                    'processing_time' => round($endTime - $startTime, 2),
                    'total_tokens' => $totalTokens,
                    'estimated_cost' => $estimatedCost,
                    'existing_count' => $existingCount,
                    'total_count' => $existingCount + $savedCount,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'エラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 問題生成用に語彙を選択
     */
    private function selectVocabulariesForGeneration(int $difficulty, int $count): array
    {
        $wordCount = (int) ceil($count / 2);
        $idiomCount = $count - $wordCount;

        $words = Vocabulary::where('type', 'WORD')
            ->where('difficulty', $difficulty)
            ->inRandomOrder()
            ->limit($wordCount)
            ->get()
            ->toArray();

        $idioms = Vocabulary::where('type', 'IDIOM')
            ->where('difficulty', $difficulty)
            ->inRandomOrder()
            ->limit($idiomCount)
            ->get()
            ->toArray();

        $vocabularies = array_merge($words, $idioms);

        // 足りない場合は他方から補充
        $remainingCount = $count - count($vocabularies);
        if ($remainingCount > 0) {
            $usedIds = array_column($vocabularies, 'id');

            $additional = Vocabulary::where('difficulty', $difficulty)
                ->whereNotIn('id', $usedIds)
                ->inRandomOrder()
                ->limit($remainingCount)
                ->get()
                ->toArray();

            $vocabularies = array_merge($vocabularies, $additional);
        }

        shuffle($vocabularies);

        return array_slice($vocabularies, 0, $count);
    }

    /**
     * Gemini API使用料金を計算
     */
    private function calculateGenerationCost(array $usage): float
    {
        $inputCost = ($usage['prompt_tokens'] / 1_000_000) * 0.075;
        $outputCost = ($usage['completion_tokens'] / 1_000_000) * 0.30;

        return $inputCost + $outputCost;
    }
}
