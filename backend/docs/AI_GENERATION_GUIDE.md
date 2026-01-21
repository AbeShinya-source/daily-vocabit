# AI問題生成機能ガイド

## 概要

Gemini API（Google）を使用して、TOEIC形式の問題を自動生成する機能です。

## セットアップ

### 1. Gemini API キーの取得

1. [Google AI Studio](https://aistudio.google.com/app/apikey) にアクセス
2. Googleアカウントでログイン
3. 「Create API Key」をクリック
4. API キーをコピー

### 2. 環境変数の設定

`.env` ファイルにAPI キーを追加：

```bash
GEMINI_API_KEY=your_api_key_here
```

### 3. 依存関係の確認

Laravelの `Http` ファサードを使用するため、追加のパッケージは不要です。

---

## 使い方

### 基本的な使用方法

```bash
# 基礎レベルの単語問題を8問生成（デフォルト）
php artisan questions:generate

# イディオム問題を生成
php artisan questions:generate --type=IDIOM

# 上級レベル（難易度2）の問題を生成
php artisan questions:generate --difficulty=2

# 特定の日付で10問生成
php artisan questions:generate --count=10 --date=2024-01-20
```

### オプション一覧

| オプション | 説明 | デフォルト値 | 指定可能な値 |
|----------|------|------------|------------|
| `--type` | 問題タイプ | WORD | WORD, IDIOM |
| `--difficulty` | 難易度 | 1 | 1（基礎）, 2（上級）, 3（超上級） |
| `--count` | 生成する問題数 | 8 | 1〜100 |
| `--date` | 生成日 | 今日 | YYYY-MM-DD形式 |

---

## 実行例

### 1. 今日の基礎レベル単語問題を8問生成

```bash
php artisan questions:generate
```

**出力例:**
```
📝 問題生成を開始します
   タイプ: WORD
   難易度: 1
   生成数: 8問
   生成日: 2024-01-16

🤖 Gemini APIで問題を生成中...
 8/8 [============================] 100%

✅ 問題生成が完了しました
┌──────────────┬─────────────────────┐
│ 項目         │ 値                  │
├──────────────┼─────────────────────┤
│ 生成成功     │ 8 問                │
│ 生成失敗     │ 0 問                │
│ 処理時間     │ 12.45 秒            │
│ 使用トークン │ 6,400 tokens        │
│ 推定コスト   │ $0.0024             │
└──────────────┴─────────────────────┘
```

### 2. 上級レベルのイディオム問題を5問生成

```bash
php artisan questions:generate --type=IDIOM --difficulty=2 --count=5
```

### 3. 未来の日付で問題を事前生成

```bash
php artisan questions:generate --date=2024-02-01 --count=10
```

---

## 動作の仕組み

### 1. 単語の選択

データベースから指定された条件（type, difficulty）に一致する単語をランダムに選択します。

```php
Vocabulary::where('type', $type)
    ->where('difficulty', $difficulty)
    ->inRandomOrder()
    ->limit($count)
    ->get();
```

### 2. Gemini APIへのリクエスト

各単語について、以下の情報を含むプロンプトを送信：

- 単語/イディオム本体
- 日本語の意味
- 品詞（単語の場合）
- 難易度レベル

**プロンプト例:**
```
あなたはTOEIC問題作成の専門家です。以下の単語を使った4択問題を1問作成してください。

【対象単語】
- 単語: expand
- 意味: 拡大する
- 品詞: 動詞
- 難易度: 基礎レベル（TOEIC 600点目標）

【問題作成の要件】
1. TOEIC Part 5（短文穴埋め問題）の形式で作成
2. 文脈から正解を推測できる自然なビジネス英文を作成
3. 正解の選択肢は「expand」を含める
...
```

### 3. レスポンスのパース

Gemini APIからのJSON形式のレスポンスを解析：

```json
{
  "questionText": "The company plans to _____ its operations overseas.",
  "choices": ["expand", "expect", "export", "expose"],
  "correctIndex": 0,
  "explanation": "「expand」は「拡大する」という意味で..."
}
```

### 4. データベースへの保存

生成された問題を `questions` テーブルに保存し、生成ログを `generation_logs` テーブルに記録します。

---

## 料金について

### Gemini 1.5 Flash 料金（2026年1月時点）

| 項目 | 料金 |
|-----|------|
| 入力トークン | $0.075 / 1M tokens |
| 出力トークン | $0.30 / 1M tokens |
| **無料枠** | **毎日1,500リクエスト** |

### コスト試算

**1問あたりの使用量（推定）:**
- 入力: 約500トークン（プロンプト）
- 出力: 約300トークン（問題+解説）
- 合計: 約800トークン/問

**8問生成した場合:**
```
入力: 500 × 8 = 4,000 tokens → $0.0003
出力: 300 × 8 = 2,400 tokens → $0.00072
合計コスト: 約 $0.001 (0.1円程度)
```

**1ヶ月間（30日）毎日8問生成:**
```
30日 × $0.001 = $0.03 (約4円)
```

→ **無料枠内で十分運用可能！**

---

## 自動実行の設定

### Laravel Schedulerを使用した毎日の自動生成

#### 1. `app/Console/Kernel.php` を編集

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // 毎日午前2時に基礎レベルの単語問題を8問生成
        $schedule->command('questions:generate --type=WORD --difficulty=1 --count=8')
            ->dailyAt('02:00')
            ->appendOutputTo(storage_path('logs/question-generation.log'));

        // 毎日午前2時10分に上級レベルの単語問題を8問生成
        $schedule->command('questions:generate --type=WORD --difficulty=2 --count=8')
            ->dailyAt('02:10')
            ->appendOutputTo(storage_path('logs/question-generation.log'));

        // 毎日午前2時20分に基礎レベルのイディオム問題を4問生成
        $schedule->command('questions:generate --type=IDIOM --difficulty=1 --count=4')
            ->dailyAt('02:20')
            ->appendOutputTo(storage_path('logs/question-generation.log'));
    }
}
```

#### 2. サーバーのcronに登録

```bash
# crontabを編集
crontab -e

# 以下の行を追加（毎分Laravelスケジューラーをチェック）
* * * * * cd /path/to/toeic-daily/backend && php artisan schedule:run >> /dev/null 2>&1
```

#### 3. 動作確認

```bash
# スケジュールされたコマンド一覧を表示
php artisan schedule:list

# スケジューラーを手動実行してテスト
php artisan schedule:run
```

---

## トラブルシューティング

### API キーエラー

**エラー:**
```
Gemini API Error: 401 Unauthorized
```

**解決方法:**
1. `.env` ファイルに正しいAPI キーが設定されているか確認
2. API キーが有効か確認（Google AI Studioで確認）
3. キャッシュをクリア: `php artisan config:clear`

### Rate Limit エラー

**エラー:**
```
Gemini API Error: 429 Too Many Requests
```

**解決方法:**
- 無料枠の制限（毎日1,500リクエスト、毎分15リクエスト）を超えています
- `GeminiService.php` の `usleep(250000)` の値を増やしてリクエスト間隔を広げる
- または Google Cloud Console で課金を有効化

### JSON パースエラー

**エラー:**
```
Failed to parse question
```

**解決方法:**
- Geminiが正しいJSON形式で応答していない可能性があります
- `storage/logs/laravel.log` でエラー詳細を確認
- プロンプトを調整する必要がある場合は `GeminiService.php` の `buildPrompt()` を編集

### 単語不足エラー

**エラー:**
```
⚠️  データベースに十分な単語がありません
```

**解決方法:**
- `vocabularies` テーブルに単語を追加してください
- 既存のサンプルデータは12単語のみです
- より多くの単語を追加するか、`--count` の値を減らしてください

---

## 生成ログの確認

### データベースで確認

```sql
-- 最近の生成ログを確認
SELECT
    generated_date,
    type,
    difficulty,
    questions_count,
    total_cost,
    status
FROM generation_logs
ORDER BY created_at DESC
LIMIT 10;
```

### DBeaver での確認

1. DBeaver で `generation_logs` テーブルを開く
2. トークン使用量やコストを確認
3. 失敗した生成があれば `error_message` を確認

---

## プロンプトのカスタマイズ

より高品質な問題を生成したい場合、`app/Services/GeminiService.php` の `buildPrompt()` メソッドを編集してください。

**カスタマイズ例:**
```php
// ビジネス文脈を強調
【問題作成の要件】
1. ビジネスシーン（会議、プレゼン、契約、交渉など）を想定した文章を作成
2. TOEIC Part 5の形式を厳守
...
```

---

## 次のステップ

1. ✅ Gemini API統合完了
2. ✅ 問題生成コマンド作成完了
3. ⏭️ 自動実行スケジューラーの設定
4. ⏭️ フロントエンドからAPIを呼び出して生成された問題を表示
5. ⏭️ 単語データの拡充（より多くの単語を追加）

---

## 参考リンク

- [Google AI Studio](https://aistudio.google.com/)
- [Gemini API Documentation](https://ai.google.dev/docs)
- [Laravel Task Scheduling](https://laravel.com/docs/11.x/scheduling)
