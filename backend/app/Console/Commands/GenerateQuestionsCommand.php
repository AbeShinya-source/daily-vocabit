<?php

namespace App\Console\Commands;

use App\Models\GenerationLog;
use App\Models\Question;
use App\Models\Vocabulary;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateQuestionsCommand extends Command
{
    /**
     * コマンド名と引数
     *
     * @var string
     */
    protected $signature = 'questions:generate
                            {--difficulty=1 : 難易度（1-3）}
                            {--count=10 : 生成する問題数}
                            {--date= : 生成日（YYYY-MM-DD、デフォルト：今日）}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'Gemini APIを使用してTOEIC問題を自動生成します';

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
        $date = $this->option('date') ?: now()->format('Y-m-d');

        // バリデーション
        if ($difficulty < 1 || $difficulty > 3) {
            $this->error('❌ difficultyは 1, 2, 3 のいずれかを指定してください');
            return self::FAILURE;
        }

        if ($count < 1 || $count > 100) {
            $this->error('❌ countは 1〜100 の範囲で指定してください');
            return self::FAILURE;
        }

        $this->info("📝 問題生成を開始します");
        $this->info("   難易度: {$difficulty}");
        $this->info("   生成数: {$count}問");
        $this->info("   生成日: {$date}");
        $this->newLine();

        // 既に同じ条件の問題が生成済みか確認
        $existingCount = Question::where('difficulty', $difficulty)
            ->where('generated_date', $date)
            ->count();

        if ($existingCount > 0) {
            if (!$this->confirm("⚠️  {$date} の難易度{$difficulty}の問題が既に {$existingCount} 問存在します。追加生成しますか？", false)) {
                $this->info('処理を中止しました');
                return self::SUCCESS;
            }
        }

        // 語彙を選択（今日追加された語彙を優先、単語とイディオムを組み合わせ）
        $vocabularies = $this->selectVocabularies($difficulty, $count, $date);

        if (count($vocabularies) < $count) {
            $this->warn("⚠️  データベースに十分な語彙がありません（必要: {$count}、利用可能: " . count($vocabularies) . "）");
            $count = count($vocabularies);

            if ($count === 0) {
                $this->error('❌ 生成可能な語彙がありません');
                return self::FAILURE;
            }
        }

        // 問題生成開始
        $this->info("🤖 Gemini APIで問題を生成中...");
        $this->newLine();
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $startTime = microtime(true);
        $result = $this->geminiService->generateMultipleQuestions($vocabularies);
        $endTime = microtime(true);

        $progressBar->finish();
        $this->newLine(2);

        // データベースに保存
        $savedCount = 0;
        $failedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($result['results'] as $item) {
                if ($item['success']) {
                    // vocabulary_idから語彙のtypeを取得
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
            $estimatedCost = $this->calculateCost($result['total_usage']);

            GenerationLog::create([
                'generated_date' => $date,
                'type' => 'WORD', // 単語とイディオムの組み合わせ（ログ用にWORDとして記録）
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

            // 結果表示
            $this->newLine();
            $this->info("✅ 問題生成が完了しました");
            $this->table(
                ['項目', '値'],
                [
                    ['生成成功', "{$savedCount} 問"],
                    ['生成失敗', "{$failedCount} 問"],
                    ['処理時間', round($endTime - $startTime, 2) . ' 秒'],
                    ['使用トークン', number_format($totalTokens) . ' tokens'],
                    ['推定コスト', '$' . number_format($estimatedCost, 4)],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ エラーが発生しました: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * 語彙を選択（単語とイディオムを組み合わせ、今日追加された語彙を優先）
     */
    private function selectVocabularies(int $difficulty, int $count, string $date): array
    {
        $today = now()->format('Y-m-d');
        $vocabularies = [];

        // 1. 今日追加された単語とイディオム（指定難易度）を取得
        $todayWords = Vocabulary::where('type', 'WORD')
            ->where('difficulty', $difficulty)
            ->whereDate('created_at', $today)
            ->inRandomOrder()
            ->get()
            ->toArray();

        $todayIdioms = Vocabulary::where('type', 'IDIOM')
            ->where('difficulty', $difficulty)
            ->whereDate('created_at', $today)
            ->inRandomOrder()
            ->get()
            ->toArray();

        $todayCount = count($todayWords) + count($todayIdioms);

        if ($todayCount > 0) {
            $this->info("✨ 今日追加された語彙を {$todayCount} 件使用します（単語: " . count($todayWords) . "、イディオム: " . count($todayIdioms) . "）");
        }

        $vocabularies = array_merge($todayWords, $todayIdioms);

        // 2. 足りない分は過去の語彙（指定難易度）からランダムに取得
        $remainingCount = $count - count($vocabularies);

        if ($remainingCount > 0) {
            $usedIds = array_column($vocabularies, 'id');

            $wordCount = ceil($remainingCount / 2);
            $idiomCount = $remainingCount - $wordCount;

            $existingWords = Vocabulary::where('type', 'WORD')
                ->where('difficulty', $difficulty)
                ->whereNotIn('id', $usedIds)
                ->inRandomOrder()
                ->limit($wordCount)
                ->get()
                ->toArray();

            $existingIdioms = Vocabulary::where('type', 'IDIOM')
                ->where('difficulty', $difficulty)
                ->whereNotIn('id', $usedIds)
                ->inRandomOrder()
                ->limit($idiomCount)
                ->get()
                ->toArray();

            $vocabularies = array_merge($vocabularies, $existingWords, $existingIdioms);

            if (count($existingWords) + count($existingIdioms) > 0) {
                $this->info("📚 過去の語彙（難易度{$difficulty}）から " . (count($existingWords) + count($existingIdioms)) . " 件追加しました");
            }
        }

        // 3. まだ足りない場合は全難易度からランダムに取得（フォールバック）
        $remainingCount = $count - count($vocabularies);

        if ($remainingCount > 0) {
            $usedIds = array_column($vocabularies, 'id');

            $fallbackVocabs = Vocabulary::whereNotIn('id', $usedIds)
                ->inRandomOrder()
                ->limit($remainingCount)
                ->get()
                ->toArray();

            if (count($fallbackVocabs) > 0) {
                $this->warn("⚠️  難易度{$difficulty}の語彙が不足のため、他の難易度から " . count($fallbackVocabs) . " 件追加しました");
                $vocabularies = array_merge($vocabularies, $fallbackVocabs);
            }
        }

        // シャッフルして単語とイディオムを混ぜる
        shuffle($vocabularies);

        return array_slice($vocabularies, 0, $count);
    }

    /**
     * Gemini API使用料金を計算（Gemini 1.5 Flash料金）
     */
    private function calculateCost(array $usage): float
    {
        // Gemini 1.5 Flash料金（128K未満）
        // Input: $0.075 per 1M tokens
        // Output: $0.30 per 1M tokens

        $inputCost = ($usage['prompt_tokens'] / 1_000_000) * 0.075;
        $outputCost = ($usage['completion_tokens'] / 1_000_000) * 0.30;

        return $inputCost + $outputCost;
    }
}
