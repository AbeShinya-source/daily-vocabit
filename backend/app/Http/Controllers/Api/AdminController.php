<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
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
}
