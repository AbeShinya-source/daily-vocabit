<?php

namespace App\Console\Commands;

use App\Models\Theme;
use App\Models\Vocabulary;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateVocabularyCommand extends Command
{
    /**
     * コマンド名と引数
     *
     * @var string
     */
    protected $signature = 'vocabulary:generate
                            {--type=WORD : 単語タイプ（WORD or IDIOM）}
                            {--difficulty=1 : 難易度（1-3）}
                            {--count=50 : 生成する単語数}
                            {--date= : テーマ適用日（YYYY-MM-DD、指定するとその日のテーマに沿った単語を生成）}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'Gemini APIを使用してTOEIC向け単語データを自動生成します';

    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    /**
     * コマンドを実行
     */
    public function handle(): int
    {
        $type = strtoupper($this->option('type'));
        $difficulty = (int) $this->option('difficulty');
        $count = (int) $this->option('count');
        $date = $this->option('date');

        // バリデーション
        if (!in_array($type, ['WORD', 'IDIOM'])) {
            $this->error('❌ typeは WORD または IDIOM を指定してください');
            return self::FAILURE;
        }

        if ($difficulty < 1 || $difficulty > 3) {
            $this->error('❌ difficultyは 1, 2, 3 のいずれかを指定してください');
            return self::FAILURE;
        }

        if ($count < 1 || $count > 200) {
            $this->error('❌ countは 1〜200 の範囲で指定してください');
            return self::FAILURE;
        }

        // テーマを取得
        $theme = null;
        if ($date) {
            $theme = Theme::getThemeForDate($date);
            if (!$theme) {
                $this->warn("⚠️  {$date} のテーマが見つかりません。テーマなしで生成します。");
            }
        }

        $this->info("📚 単語データ生成を開始します");
        $this->info("   タイプ: {$type}");
        $this->info("   難易度: {$difficulty}");
        $this->info("   生成数: {$count}個");
        if ($theme) {
            $this->info("   テーマ: {$theme->title_ja} ({$theme->title_en})");
        }
        $this->newLine();

        // プロンプトを作成
        $prompt = $this->buildPrompt($type, $difficulty, $count, $theme);

        $this->info("🤖 Gemini APIで単語データを生成中...");

        try {
            $response = $this->geminiService->generateContent($prompt);

            // JSONを抽出
            $jsonText = $this->extractJson($response['text']);
            $vocabularies = json_decode($jsonText, true);

            if (!is_array($vocabularies)) {
                throw new \Exception('生成されたデータが配列ではありません');
            }

            $this->newLine();
            $this->info("✅ {$count}個の単語データを生成しました");

            // データベースに保存
            $savedCount = 0;
            $skippedCount = 0;

            $progressBar = $this->output->createProgressBar(count($vocabularies));
            $progressBar->start();

            DB::beginTransaction();
            try {
                foreach ($vocabularies as $vocab) {
                    // 既に存在するかチェック
                    $exists = Vocabulary::where('word', $vocab['word'])
                        ->where('type', $type)
                        ->exists();

                    if ($exists) {
                        $skippedCount++;
                        $progressBar->advance();
                        continue;
                    }

                    Vocabulary::create([
                        'word' => $vocab['word'],
                        'meaning' => $vocab['meaning'],
                        'type' => $type,
                        'difficulty' => $difficulty,
                        'example_sentence' => $vocab['example'] ?? null,
                        'part_of_speech' => $vocab['part_of_speech'] ?? null,
                    ]);
                    $savedCount++;
                    $progressBar->advance();
                }

                DB::commit();
                $progressBar->finish();
                $this->newLine(2);

                // 結果表示
                $this->info("✅ 単語データの保存が完了しました");
                $this->table(
                    ['項目', '値'],
                    [
                        ['新規保存', "{$savedCount} 個"],
                        ['スキップ（重複）', "{$skippedCount} 個"],
                        ['合計', count($vocabularies) . ' 個'],
                    ]
                );

                return self::SUCCESS;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ データベース保存エラー: " . $e->getMessage());
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ エラーが発生しました: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * プロンプトを構築
     */
    private function buildPrompt(string $type, int $difficulty, int $count, ?Theme $theme = null): string
    {
        $difficultyLabel = match($difficulty) {
            1 => '基礎レベル（TOEIC 400-600点程度）',
            2 => '中級レベル（TOEIC 600-800点程度）',
            3 => '上級レベル（TOEIC 800-990点程度）',
        };

        $typeLabel = $type === 'WORD' ? 'ビジネス英単語' : 'ビジネスイディオム';
        $themeText = $theme ? "\n- テーマ: {$theme->title_ja} ({$theme->title_en}) - {$theme->description}" : '';
        $exampleNote = $theme ? "（テーマ「{$theme->title_en}」に関連する内容が望ましい）" : "";

        if ($type === 'WORD') {
            return <<<PROMPT
あなたはTOEIC対策の専門家です。
ビジネス英語で頻出する単語を{$count}個生成してください。

【条件】
- 難易度: {$difficultyLabel}
- TOEICのビジネスシーンで実際に使われる単語{$themeText}
- 名詞、動詞、形容詞、副詞をバランスよく含める
- 例文はビジネスシーンで実用的なもの{$exampleNote}

【出力形式】
以下のJSON形式で{$count}個の単語を出力してください：

```json
[
  {
    "word": "単語",
    "meaning": "日本語の意味",
    "part_of_speech": "品詞（noun/verb/adjective/adverb）",
    "example": "例文（英語）"
  }
]
```

重要: 必ずJSON形式のみを出力し、説明文は含めないでください。
PROMPT;
        } else {
            return <<<PROMPT
あなたはTOEIC対策の専門家です。
ビジネス英語で頻出するイディオム（熟語・慣用句）を{$count}個生成してください。

【条件】
- 難易度: {$difficultyLabel}
- TOEICのビジネスシーンで実際に使われるイディオム{$themeText}
- ビジネスコミュニケーションで役立つ表現{$exampleNote}

【出力形式】
以下のJSON形式で{$count}個のイディオムを出力してください：

```json
[
  {
    "word": "イディオム",
    "meaning": "日本語の意味",
    "part_of_speech": "idiom",
    "example": "例文（英語）"
  }
]
```

重要: 必ずJSON形式のみを出力し、説明文は含めないでください。
PROMPT;
        }
    }

    /**
     * レスポンスからJSONを抽出
     */
    private function extractJson(string $text): string
    {
        // コードブロックを除去
        $text = preg_replace('/```json\s*/s', '', $text);
        $text = preg_replace('/```\s*/s', '', $text);

        // 最初の [ から最後の ] までを抽出
        if (preg_match('/\[.*\]/s', $text, $matches)) {
            return $matches[0];
        }

        return $text;
    }
}
