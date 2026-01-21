<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VocabularyController extends Controller
{
    /**
     * 単語・イディオム一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vocabulary::query();

        // フィルタリング
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // 検索
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('word', 'LIKE', "%{$search}%")
                    ->orWhere('meaning', 'LIKE', "%{$search}%");
            });
        }

        // ソート
        $sortBy = $request->query('sort_by', 'frequency');
        $sortOrder = $request->query('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // ページネーション
        $perPage = $request->query('per_page', 20);
        $vocabularies = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $vocabularies
        ]);
    }

    /**
     * 単語・イディオム詳細を取得
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $vocabulary = Vocabulary::with('questions')->find($id);

        if (!$vocabulary) {
            return response()->json([
                'success' => false,
                'message' => '単語が見つかりませんでした'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vocabulary->id,
                'word' => $vocabulary->word,
                'type' => $vocabulary->type,
                'difficulty' => $vocabulary->difficulty,
                'meaning' => $vocabulary->meaning,
                'part_of_speech' => $vocabulary->part_of_speech,
                'example_sentence' => $vocabulary->example_sentence,
                'synonym' => $vocabulary->synonym,
                'antonym' => $vocabulary->antonym,
                'frequency' => $vocabulary->frequency,
                'tags' => $vocabulary->tags,
                'questions_count' => $vocabulary->questions->count()
            ]
        ]);
    }
}
