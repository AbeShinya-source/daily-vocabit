<?php

namespace App\Console\Commands;

use App\Models\Vocabulary;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScrapeVocabularyCommand extends Command
{
    /**
     * コマンド名と引数
     */
    protected $signature = 'vocabulary:generate
                            {--difficulty=1 : 難易度（1-3）}
                            {--count=10 : 生成する単語数}
                            {--type=WORD : 語彙タイプ（WORD or IDIOM）}';

    /**
     * コマンドの説明
     */
    protected $description = 'Gemini APIを使用して実用的なビジネス語彙を生成します';

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
        $difficulty = (int) $this->option('difficulty');
        $count = (int) $this->option('count');
        $type = strtoupper($this->option('type'));

        // バリデーション
        if (!in_array($type, ['WORD', 'IDIOM'])) {
            $this->error('❌ typeは WORD または IDIOM を指定してください');
            return self::FAILURE;
        }

        if ($difficulty < 1 || $difficulty > 3) {
            $this->error('❌ difficultyは 1, 2, 3 のいずれかを指定してください');
            return self::FAILURE;
        }

        if ($count < 1 || $count > 50) {
            $this->error('❌ countは 1〜50 の範囲で指定してください');
            return self::FAILURE;
        }

        $typeLabel = $type === 'WORD' ? '単語' : 'イディオム';
        $this->info("📚 語彙生成を開始します");
        $this->info("   タイプ: {$typeLabel}");
        $this->info("   難易度: {$difficulty}");
        $this->info("   生成数: {$count}語");
        $this->newLine();

        // 既存の単語を取得して重複を避ける
        $existingWords = Vocabulary::where('type', $type)->pluck('word')->toArray();

        // プロンプトを構築
        $prompt = $this->buildPrompt($difficulty, $count, $type, $existingWords);

        $this->info("🤖 Gemini APIで語彙を生成中...");

        try {
            $response = $this->geminiService->generateContent($prompt);
            $vocabularies = $this->parseVocabularies($response['text']);

            if (empty($vocabularies)) {
                $this->error('❌ 語彙の生成に失敗しました');
                return self::FAILURE;
            }

            // データベースに保存
            $savedCount = 0;
            DB::beginTransaction();

            try {
                foreach ($vocabularies as $vocab) {
                    // 重複チェック
                    if (Vocabulary::where('word', $vocab['word'])->exists()) {
                        continue;
                    }

                    Vocabulary::create([
                        'word' => $vocab['word'],
                        'type' => $type,
                        'difficulty' => $difficulty,
                        'meaning' => $vocab['meaning'],
                        'part_of_speech' => $vocab['part_of_speech'] ?? null,
                        'example_sentence' => $vocab['example'] ?? null,
                        'is_active' => true,
                    ]);
                    $savedCount++;
                }

                DB::commit();

                $this->newLine();
                $this->info("✅ 語彙生成が完了しました");
                $this->table(
                    ['項目', '値'],
                    [
                        ['生成成功', "{$savedCount} 語"],
                        ['重複スキップ', (count($vocabularies) - $savedCount) . " 語"],
                        ['使用トークン', number_format($response['usage']['total_tokens']) . " tokens"],
                    ]
                );

                return self::SUCCESS;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ データベース保存エラー: {$e->getMessage()}");
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ API エラー: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * プロンプトを構築
     */
    private function buildPrompt(int $difficulty, int $count, string $type, array $existingWords): string
    {
        $difficultyText = match($difficulty) {
            1 => 'TOEIC 600点レベル（基礎的なビジネス語彙）',
            2 => 'TOEIC 800点レベル（実務的なビジネス語彙）',
            3 => 'TOEIC 900点以上レベル（高度なビジネス語彙）',
            default => '基礎レベル'
        };

        $existingWordsText = !empty($existingWords)
            ? "以下の単語・イディオムは既に登録されているため、避けてください：\n" . implode(', ', array_slice($existingWords, -100))
            : '';

        if ($type === 'IDIOM') {
            return $this->buildIdiomPrompt($difficulty, $count, $difficultyText, $existingWordsText);
        }

        return <<<PROMPT
あなたは英語教育の専門家です。最近のビジネスニュースや実務で頻繁に使用される実用的な英単語を{$count}個生成してください。

【生成する語彙の条件】
- 難易度: {$difficultyText}
- TOEICで出題されるビジネス英語の語彙
- 実際のビジネスシーン（会議、メール、報告書、契約書、プレゼンテーション、交渉など）で使用される単語
- 最近のビジネストレンド、経済、マーケティング、人事、財務、技術、環境などの分野から選択
- 名詞、動詞、形容詞、副詞をバランスよく含める

{$existingWordsText}

【出力形式】
以下のJSON配列形式で{$count}個の単語を出力してください。JSONのみを出力し、説明文は含めないでください。

```json
[
  {
    "word": "英単語",
    "meaning": "日本語の意味",
    "part_of_speech": "品詞（名詞/動詞/形容詞/副詞）",
    "example": "ビジネスシーンでの例文（英語）"
  }
]
```

重要事項:
- 必ず{$count}個の単語を生成してください
- 各単語は実用性が高く、TOEICで出題される可能性のあるものを選んでください
- 例文はビジネスコンテキストで自然な文章にしてください
PROMPT;
    }

    /**
     * イディオム生成用プロンプトを構築
     */
    private function buildIdiomPrompt(int $difficulty, int $count, string $difficultyText, string $existingWordsText): string
    {
        return <<<PROMPT
あなたは英語教育の専門家です。最近のビジネスニュースや実務で頻繁に使用される実用的な英語イディオム（熟語・慣用句）を{$count}個生成してください。

【生成する語彙の条件】
- 難易度: {$difficultyText}
- TOEICで出題されるビジネス英語のイディオム
- 実際のビジネスシーン（会議、メール、報告書、契約書、プレゼンテーション、交渉など）で使用される熟語・慣用句
- 最近のビジネストレンド、経済、マーケティング、人事、財務、技術、環境などの分野から選択
- 動詞句、前置詞句、形容詞句などをバランスよく含める
- 2語以上で構成される慣用表現（例：take advantage of, in charge of, due to など）

{$existingWordsText}

【出力形式】
以下のJSON配列形式で{$count}個のイディオムを出力してください。JSONのみを出力し、説明文は含めないでください。

```json
[
  {
    "word": "イディオム（英語表現）",
    "meaning": "日本語の意味",
    "part_of_speech": "品詞（動詞句/前置詞句/形容詞句/副詞句）",
    "example": "ビジネスシーンでの例文（英語）"
  }
]
```

重要事項:
- 必ず{$count}個のイディオムを生成してください
- 各イディオムは実用性が高く、TOEICで出題される可能性のあるものを選んでください
- 例文はビジネスコンテキストで自然な文章にしてください
- 単語ではなく、必ず2語以上の熟語・慣用句を生成してください
PROMPT;
    }

    /**
     * レスポンスから語彙をパース
     */
    private function parseVocabularies(string $text): array
    {
        // JSONブロックを抽出
        if (preg_match('/```json\s*(\[.*?\])\s*```/s', $text, $matches)) {
            $jsonText = $matches[1];
        } elseif (preg_match('/(\[.*\])/s', $text, $matches)) {
            $jsonText = $matches[1];
        } else {
            return [];
        }

        try {
            $vocabularies = json_decode($jsonText, true);

            if (!is_array($vocabularies)) {
                return [];
            }

            return $vocabularies;

        } catch (\Exception $e) {
            $this->warn("⚠️  JSONパースエラー: {$e->getMessage()}");
            return [];
        }
    }
}
