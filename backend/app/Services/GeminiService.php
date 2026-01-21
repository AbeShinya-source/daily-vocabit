<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $model = 'gemini-flash-latest';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * 汎用的なコンテンツ生成
     *
     * @param string $prompt プロンプト
     * @return array レスポンスデータ
     */
    public function generateContent(string $prompt): array
    {
        $response = Http::timeout(30)
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ]
            ]);

        if ($response->failed()) {
            throw new \Exception('Gemini API Error: ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            throw new \Exception('Gemini API: No text in response');
        }

        return [
            'text' => $text,
            'usage' => [
                'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            ]
        ];
    }

    /**
     * TOEIC問題を生成
     *
     * @param string $word 単語またはイディオム
     * @param string $type WORD or IDIOM
     * @param int $difficulty 難易度（1-3）
     * @param string $meaning 日本語の意味
     * @param string|null $partOfSpeech 品詞
     * @return array|null 生成された問題データ
     */
    public function generateQuestion(
        string $word,
        string $type,
        int $difficulty,
        string $meaning,
        ?string $partOfSpeech = null
    ): ?array {
        $prompt = $this->buildPrompt($word, $type, $difficulty, $meaning, $partOfSpeech);

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            // レスポンスからテキストを抽出
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                Log::error('Gemini API: No text in response', ['data' => $data]);
                return null;
            }

            // JSONを抽出してパース（ターゲット単語を渡して自動修正を有効化）
            $question = $this->parseQuestionFromResponse($text, $word);

            if (!$question) {
                Log::error('Failed to parse question', ['text' => $text]);
                return null;
            }

            // トークン使用量を記録
            $usage = [
                'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            ];

            return [
                'question' => $question,
                'usage' => $usage
            ];

        } catch (\Exception $e) {
            Log::error('Gemini API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * プロンプトを構築
     */
    private function buildPrompt(
        string $word,
        string $type,
        int $difficulty,
        string $meaning,
        ?string $partOfSpeech
    ): string {
        $difficultyText = match($difficulty) {
            1 => '基礎レベル（TOEIC 600点目標）',
            2 => '上級レベル（TOEIC 800点目標）',
            3 => '超上級レベル（TOEIC 900点以上）',
            default => '基礎レベル'
        };

        $typeText = $type === 'WORD' ? '単語' : 'イディオム';
        $partOfSpeechText = $partOfSpeech ? "品詞: {$partOfSpeech}" : '';

        return <<<PROMPT
あなたはTOEIC問題作成の専門家です。以下の{$typeText}を使った4択問題を1問作成してください。

【対象{$typeText}】
- {$typeText}: {$word}
- 意味: {$meaning}
{$partOfSpeechText}
- 難易度: {$difficultyText}

【問題作成の要件】
1. TOEIC Part 5（短文穴埋め問題）の形式で作成
2. ビジネスシーンで使用される自然な英文を作成（会議、メール、報告書、プレゼンテーション、契約、交渉、オフィス業務など）
3. **文章構成の多様性を確保する（非常に重要）：**
   - 主語を多様化：人名（Ms. Johnson, Mr. Smith）、会社名（ABC Corporation）、部署名（The marketing team）、一般名詞（The report, All employees）、代名詞（They, We）などをバランスよく使う
   - 文構造を変化させる：能動態・受動態、肯定文・否定文、単文・複文、条件文、時間の表現など
   - 空欄の位置を変える：文頭・文中・文末で変化を持たせる
   - 時制を変える：現在形、過去形、未来形、現在完了形、過去完了形など
   - **絶対に避けるべきパターン**: 「人名 + 動詞 + 目的語」の繰り返し（例：Ms. Johnson reviewed, Mr. Smith completed など）
   - 様々な文脈を使う：指示・依頼・報告・提案・質問・説明など
4. 正解の選択肢は「{$word}」を含める
5. 不正解の選択肢3つは、以下の基準で選ぶ：
   - 意味が似ている・関連性のある単語（例：abundant/plentiful、effect/result）
   - 同じ文脈で使われやすいが意味が異なる単語
   - 綴りや発音が似ている単語も適度に含める
   - 公式TOEIC問題のような、受験者が文脈を正しく理解しないと間違えやすい選択肢
6. 全ての選択肢は同じ品詞で統一する
7. **解説のフォーマット（必須）：**
   - 第1行: 「正解は (X) 『{$word}（意味）』です。」という形式（Xは選択肢のアルファベット A/B/C/D）
   - 第2行: 空行
   - 第3行以降: 詳細な解説（なぜこの答えが正解なのか、文脈での使い方、他の選択肢との違いなど）
8. **重要**: choices配列の中で「{$word}」がどの位置にあるかを確認し、そのインデックス番号（0, 1, 2, または 3）をcorrectIndexに設定すること

【出力形式】
以下のJSON形式で出力してください。JSONのみを出力し、他の説明文は含めないでください。

```json
{
  "questionText": "問題文（英語。空欄は _____ で表現）",
  "questionTranslation": "問題文の日本語訳全文",
  "choices": ["選択肢1", "選択肢2", "選択肢3", "選択肢4"],
  "correctIndex": 0,
  "explanation": "正解は (A) 『{$word}（意味）』です。\n\n詳細な解説文..."
}
```

【解説の記述例】
correctIndexが1（B）の場合：
questionTranslation: "この報告書は、プロジェクトの進捗状況を_____記録しています。"
explanation: "正解は (B) 『accurately（正確に）』です。\n\n「accurately」は「正確に、精密に」という意味の副詞で、ビジネス文書では品質の高さを表現する際によく使用されます。報告書が進捗状況を「正確に」記録していることを表現するため、この文脈に最適です。他の選択肢「approximately（おおよそ）」「casually（気軽に）」「vaguely（曖昧に）」は、ビジネス報告書の正確性を損なうため不適切です。"

**アルファベット表記ルール:**
- correctIndex = 0 → (A)
- correctIndex = 1 → (B)
- correctIndex = 2 → (C)
- correctIndex = 3 → (D)

**correctIndexの設定方法（必須）:**
- choicesの配列を作成後、「{$word}」がどのインデックスに配置されているか確認してください
- 例: choices = ["incorrect1", "{$word}", "incorrect2", "incorrect3"] の場合、correctIndex = 1
- 例: choices = ["incorrect1", "incorrect2", "incorrect3", "{$word}"] の場合、correctIndex = 3
- correctIndexは必ず「{$word}」が含まれる選択肢の正確な位置（0-3）を指定してください
PROMPT;
    }

    /**
     * Geminiのレスポンスから問題データをパース
     */
    private function parseQuestionFromResponse(string $text, string $targetWord = null): ?array
    {
        // JSONブロックを抽出（```json ... ``` または { ... } の形式）
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $jsonText = $matches[1];
        } elseif (preg_match('/(\{.*\})/s', $text, $matches)) {
            $jsonText = $matches[1];
        } else {
            return null;
        }

        try {
            $data = json_decode($jsonText, true);

            if (!$data || !isset($data['questionText'], $data['questionTranslation'], $data['choices'], $data['correctIndex'], $data['explanation'])) {
                return null;
            }

            // バリデーション
            if (!is_array($data['choices']) || count($data['choices']) !== 4) {
                return null;
            }

            if (!is_int($data['correctIndex']) || $data['correctIndex'] < 0 || $data['correctIndex'] > 3) {
                return null;
            }

            // correctIndexの自動修正: ターゲット単語が含まれる選択肢を探す
            if ($targetWord) {
                $actualCorrectIndex = null;
                foreach ($data['choices'] as $index => $choice) {
                    if (stripos($choice, $targetWord) !== false) {
                        $actualCorrectIndex = $index;
                        break;
                    }
                }

                // AIが間違ったインデックスを返した場合、修正する
                if ($actualCorrectIndex !== null && $actualCorrectIndex !== $data['correctIndex']) {
                    Log::warning('Corrected incorrect correctIndex from AI', [
                        'word' => $targetWord,
                        'ai_index' => $data['correctIndex'],
                        'actual_index' => $actualCorrectIndex,
                        'choices' => $data['choices']
                    ]);
                    $data['correctIndex'] = $actualCorrectIndex;
                }
            }

            // 選択肢をシャッフルして正解の位置をランダム化
            $choices = $data['choices'];
            $correctChoice = $choices[$data['correctIndex']];

            // Fisher-Yatesアルゴリズムでシャッフル
            for ($i = count($choices) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                $temp = $choices[$i];
                $choices[$i] = $choices[$j];
                $choices[$j] = $temp;
            }

            // シャッフル後の正解のインデックスを見つける
            $newCorrectIndex = array_search($correctChoice, $choices);

            // 解説文のアルファベット表記を更新（シャッフル後の正しい位置に）
            $explanation = $data['explanation'];
            $newLetter = chr(65 + $newCorrectIndex); // 0=A, 1=B, 2=C, 3=D
            $explanation = preg_replace('/正解は\s*\(\K[A-D](?=\))/', $newLetter, $explanation);

            return [
                'question_text' => $data['questionText'],
                'question_translation' => $data['questionTranslation'],
                'choices' => $choices, // Eloquent cast handles JSON encoding
                'correct_index' => $newCorrectIndex,
                'explanation' => $explanation
            ];

        } catch (\Exception $e) {
            Log::error('JSON parse error', ['error' => $e->getMessage(), 'text' => $jsonText]);
            return null;
        }
    }

    /**
     * 複数の問題を一括生成
     *
     * @param array $vocabularies 単語リスト（各要素は連想配列）
     * @return array 生成結果
     */
    public function generateMultipleQuestions(array $vocabularies): array
    {
        $results = [];
        $totalUsage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0
        ];

        foreach ($vocabularies as $vocab) {
            $result = $this->generateQuestion(
                $vocab['word'],
                $vocab['type'],
                $vocab['difficulty'],
                $vocab['meaning'],
                $vocab['part_of_speech'] ?? null
            );

            if ($result) {
                $results[] = [
                    'vocabulary_id' => $vocab['id'],
                    'question' => $result['question'],
                    'success' => true
                ];

                // トークン使用量を累積
                $totalUsage['prompt_tokens'] += $result['usage']['prompt_tokens'];
                $totalUsage['completion_tokens'] += $result['usage']['completion_tokens'];
                $totalUsage['total_tokens'] += $result['usage']['total_tokens'];
            } else {
                $results[] = [
                    'vocabulary_id' => $vocab['id'],
                    'question' => null,
                    'success' => false
                ];
            }

            // API rate limitを考慮して少し待機（Gemini Flashは毎分15リクエスト）
            if (count($vocabularies) > 1) {
                usleep(250000); // 0.25秒待機 = 最大毎分240リクエスト → 余裕を持って制限内
            }
        }

        return [
            'results' => $results,
            'total_usage' => $totalUsage,
            'success_count' => count(array_filter($results, fn($r) => $r['success'])),
            'total_count' => count($results)
        ];
    }
}
