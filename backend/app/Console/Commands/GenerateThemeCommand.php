<?php

namespace App\Console\Commands;

use App\Models\Theme;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateThemeCommand extends Command
{
    /**
     * コマンド名と引数
     *
     * @var string
     */
    protected $signature = 'theme:generate
                            {--date= : テーマを適用する日付（Y-m-d形式）}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'Gemini APIを使用して日替わりテーマを自動生成します';

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
        $date = $this->option('date') ?? now()->format('Y-m-d');

        // 既に同じ日付のテーマが存在するかチェック
        $existingTheme = Theme::where('date', $date)->first();
        if ($existingTheme) {
            $this->error("❌ {$date} のテーマは既に存在します");
            $this->info("   テーマ: {$existingTheme->title_ja} ({$existingTheme->title_en})");
            return self::FAILURE;
        }

        $this->info("📅 {$date} のテーマを生成します");
        $this->newLine();

        // 過去のテーマを取得（重複回避のため）
        $pastThemes = Theme::orderBy('date', 'desc')
            ->limit(50)
            ->pluck('title_en')
            ->toArray();

        // プロンプトを作成
        $prompt = $this->buildPrompt($pastThemes);

        $this->info("🤖 Gemini APIでテーマを生成中...");

        try {
            $response = $this->geminiService->generateContent($prompt);

            // JSONを抽出
            $jsonText = $this->extractJson($response['text']);
            $themeData = json_decode($jsonText, true);

            if (!is_array($themeData) || !isset($themeData['title_en'], $themeData['title_ja'])) {
                throw new \Exception('生成されたデータの形式が不正です');
            }

            $this->newLine();
            $this->info("✅ テーマを生成しました");
            $this->table(
                ['項目', '値'],
                [
                    ['英語タイトル', $themeData['title_en']],
                    ['日本語タイトル', $themeData['title_ja']],
                    ['説明', $themeData['description'] ?? ''],
                    ['適用日', $date],
                ]
            );

            // データベースに保存
            DB::beginTransaction();
            try {
                Theme::create([
                    'title_en' => $themeData['title_en'],
                    'title_ja' => $themeData['title_ja'],
                    'description' => $themeData['description'] ?? null,
                    'date' => $date,
                    'is_active' => true,
                ]);

                DB::commit();
                $this->newLine();
                $this->info("✅ テーマの保存が完了しました");

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
    private function buildPrompt(array $pastThemes): string
    {
        $pastThemesList = empty($pastThemes)
            ? 'なし'
            : '- ' . implode("\n- ", $pastThemes);

        return <<<PROMPT
あなたはTOEIC対策の専門家です。
日替わりで学習テーマを1つ生成してください。

【テーマの条件】
- TOEICで頻出するビジネスや日常のトピック
- 1〜2語程度のシンプルなテーマ（例：「会議」「経済」「採用」「予算」「旅行」「健康」「環境」「技術」「交渉」「契約」など）
- 過去に使用したテーマとは異なる新しいテーマ
- 英語学習者が実用的に学べるテーマ
- ビジネス、オフィスワーク、日常会話、旅行、医療、技術、環境などの分野から選択

【過去に使用したテーマ】
{$pastThemesList}

【出力形式】
以下のJSON形式で1つのテーマを出力してください：

```json
{
  "title_en": "英語のテーマ（1〜2語）",
  "title_ja": "日本語のテーマ（1〜2語）",
  "description": "このテーマの簡単な説明（30文字程度）"
}
```

重要:
- title_enとtitle_jaは必ず1〜2語の短い単語にしてください
- JSON形式のみを出力し、説明文は含めないでください
PROMPT;
    }

    /**
     * レスポンスからJSONを抽出
     */
    private function extractJson(string $text): string
    {
        // コードブロックを除去
        $text = preg_replace('/```json\s*/s', '', $text);
        $text = preg_replace('/```\s*/s', '', $text);

        // 最初の { から最後の } までを抽出
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }

        return $text;
    }
}
