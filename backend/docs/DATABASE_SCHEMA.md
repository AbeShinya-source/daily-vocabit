# TOEIC Daily データベーススキーマ設計書 (Laravel)

## 概要

このドキュメントは、TOEIC Daily アプリケーションのデータベーススキーマ設計を説明します。
AI を活用した問題生成と、ユーザーの学習進捗管理を実現するための設計になっています。

## 技術スタック

- **バックエンドフレームワーク**: Laravel 11.x
- **ORM**: Eloquent ORM
- **データベース**: MySQL 8.0 (開発・本番共通)
- **開発環境**: Docker + Laravel Sail

---

## テーブル一覧

| テーブル名 | モデル名 | 説明 |
|-----------|---------|------|
| `vocabularies` | Vocabulary | 単語・イディオムマスターデータ |
| `questions` | Question | AI 生成された問題データ |
| `users` | User | ユーザー情報 (認証用) |
| `user_answers` | UserAnswer | ユーザーの回答履歴 |
| `user_progress` | UserProgress | ユーザーの学習進捗 (日次集計) |
| `generation_logs` | GenerationLog | AI 問題生成のログ (コスト・品質管理) |

---

## 1. vocabularies テーブル

単語・イディオムの基本情報を保存するマスターテーブル。

### カラム定義

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|----|----|---------|------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| `word` | VARCHAR(255) | NO | - | 単語またはイディオム本体 (UNIQUE) |
| `type` | ENUM('WORD','IDIOM') | NO | - | 種別 |
| `difficulty` | TINYINT | NO | - | 難易度 (1=600点, 2=800点, 3=超上級) |
| `meaning` | VARCHAR(255) | NO | - | 日本語の意味 |
| `part_of_speech` | VARCHAR(255) | YES | NULL | 品詞 (動詞、名詞など) |
| `example_sentence` | TEXT | YES | NULL | 例文 |
| `synonym` | VARCHAR(255) | YES | NULL | 類義語 (カンマ区切り) |
| `antonym` | VARCHAR(255) | YES | NULL | 対義語 (カンマ区切り) |
| `frequency` | INT | NO | 0 | TOEIC 頻出度 (高いほど重要) |
| `tags` | VARCHAR(255) | YES | NULL | タグ (カンマ区切り: "ビジネス,会議,交渉") |
| `created_at` | TIMESTAMP | YES | NULL | 作成日時 |
| `updated_at` | TIMESTAMP | YES | NULL | 更新日時 |

### インデックス

- PRIMARY KEY (`id`)
- UNIQUE KEY (`word`)
- INDEX (`type`, `difficulty`)
- INDEX (`frequency`)

### サンプルデータ

```php
Vocabulary::create([
    'word' => 'expand',
    'type' => 'WORD',
    'difficulty' => 1,
    'meaning' => '拡大する',
    'part_of_speech' => '動詞',
    'frequency' => 85,
    'tags' => 'ビジネス,成長'
]);
```

---

## 2. questions テーブル

AI が生成した問題をキャッシュするテーブル。日次で問題を生成し、ユーザーに配信します。

### カラム定義

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|----|----|---------|------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| `vocabulary_id` | BIGINT UNSIGNED | NO | - | 単語ID (FOREIGN KEY) |
| `type` | ENUM('WORD','IDIOM') | NO | - | 種別 |
| `difficulty` | TINYINT | NO | - | 難易度 |
| `question_text` | TEXT | NO | - | 問題文 |
| `choices` | JSON | NO | - | 選択肢 (JSON配列) |
| `correct_index` | TINYINT | NO | - | 正解のインデックス (0-3) |
| `explanation` | TEXT | NO | - | 解説文 |
| `generated_date` | DATE | NO | - | 生成日 (YYYY-MM-DD) |
| `is_active` | BOOLEAN | NO | TRUE | 使用可能かどうか |
| `usage_count` | INT | NO | 0 | 出題回数 |
| `created_at` | TIMESTAMP | YES | NULL | 作成日時 |
| `updated_at` | TIMESTAMP | YES | NULL | 更新日時 |

### インデックス

- PRIMARY KEY (`id`)
- FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabularies`(`id`) ON DELETE CASCADE
- INDEX (`generated_date`, `type`, `difficulty`, `is_active`)
- INDEX (`vocabulary_id`)

### choices カラムの JSON 形式

```json
["expand", "expect", "export", "expose"]
```

---

## 3. users テーブル

ユーザー情報を保存するテーブル。Laravel 標準の認証機能と統合します。

### カラム定義

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|----|----|---------|------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| `email` | VARCHAR(255) | YES | NULL | メールアドレス (UNIQUE) |
| `name` | VARCHAR(255) | YES | NULL | ユーザー名 |
| `password` | VARCHAR(255) | YES | NULL | パスワード (ハッシュ化) |
| `remember_token` | VARCHAR(100) | YES | NULL | Remember Me トークン |
| `created_at` | TIMESTAMP | YES | NULL | 作成日時 |
| `updated_at` | TIMESTAMP | YES | NULL | 更新日時 |

### インデックス

- PRIMARY KEY (`id`)
- UNIQUE KEY (`email`)

---

## 4. user_answers テーブル

ユーザーの回答記録を保存するテーブル。正答率分析や学習履歴に使用します。

### カラム定義

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|----|----|---------|------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| `user_id` | BIGINT UNSIGNED | YES | NULL | ユーザーID (FOREIGN KEY, nullable) |
| `question_id` | BIGINT UNSIGNED | NO | - | 問題ID (FOREIGN KEY) |
| `selected_index` | TINYINT | NO | - | 選択した選択肢 (0-3) |
| `is_correct` | BOOLEAN | NO | - | 正解したか |
| `answered_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | 回答日時 |
| `created_at` | TIMESTAMP | YES | NULL | 作成日時 |
| `updated_at` | TIMESTAMP | YES | NULL | 更新日時 |

### インデックス

- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
- FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
- INDEX (`user_id`, `answered_at`)
- INDEX (`question_id`)

---

## 5. user_progress テーブル

ユーザーの学習進捗を日次で集計するテーブル。

### カラム定義

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|----|----|---------|------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| `user_id` | BIGINT UNSIGNED | NO | - | ユーザーID (FOREIGN KEY) |
| `date` | DATE | NO | - | 学習日 (YYYY-MM-DD) |
| `type` | ENUM('WORD','IDIOM') | NO | - | 種別 |
| `difficulty` | TINYINT | NO | - | 難易度 |
| `total_questions` | INT | NO | - | その日に解いた問題数 |
| `correct_count` | INT | NO | - | 正解数 |
| `score_percent` | INT | NO | - | 正答率 (%) |
| `study_time` | INT | YES | NULL | 学習時間 (秒) |
| `created_at` | TIMESTAMP | YES | NULL | 作成日時 |
| `updated_at` | TIMESTAMP | YES | NULL | 更新日時 |

### インデックス

- PRIMARY KEY (`id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- UNIQUE KEY (`user_id`, `date`, `type`, `difficulty`)
- INDEX (`user_id`, `date`)

---

## 6. generation_logs テーブル

AI 問題生成のログを記録するテーブル。コスト管理と品質チェックに使用します。

### カラム定義

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|----|----|---------|------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | 主キー |
| `generated_date` | DATE | NO | - | 生成日 (YYYY-MM-DD) |
| `type` | ENUM('WORD','IDIOM') | NO | - | 種別 |
| `difficulty` | TINYINT | NO | - | 難易度 |
| `questions_count` | INT | NO | - | 生成した問題数 |
| `ai_model` | VARCHAR(255) | NO | - | 使用したAIモデル (gpt-4, claude-3.5) |
| `prompt_tokens` | INT | YES | NULL | プロンプトトークン数 |
| `completion_tokens` | INT | YES | NULL | 補完トークン数 |
| `total_cost` | DECIMAL(10,6) | YES | NULL | APIコスト ($) |
| `status` | ENUM('SUCCESS','PARTIAL','FAILED') | NO | 'SUCCESS' | ステータス |
| `error_message` | TEXT | YES | NULL | エラー時のメッセージ |
| `created_at` | TIMESTAMP | YES | NULL | 作成日時 |
| `updated_at` | TIMESTAMP | YES | NULL | 更新日時 |

### インデックス

- PRIMARY KEY (`id`)
- INDEX (`generated_date`)

---

## ER 図 (テキスト表現)

```
vocabularies (1) ━━━ (N) questions (N) ━━━ (N) user_answers (N) ━━━ (1) users
                                                                           ┃
                                                                           ┃
                                                                  (1) ━━━ (N) user_progress

generation_logs (独立したログテーブル)
```

---

## Eloquent リレーション

### Vocabulary モデル

```php
public function questions() {
    return $this->hasMany(Question::class);
}
```

### Question モデル

```php
public function vocabulary() {
    return $this->belongsTo(Vocabulary::class);
}

public function userAnswers() {
    return $this->hasMany(UserAnswer::class);
}
```

### User モデル

```php
public function answers() {
    return $this->hasMany(UserAnswer::class);
}

public function progress() {
    return $this->hasMany(UserProgress::class);
}
```

### UserAnswer モデル

```php
public function user() {
    return $this->belongsTo(User::class);
}

public function question() {
    return $this->belongsTo(Question::class);
}
```

### UserProgress モデル

```php
public function user() {
    return $this->belongsTo(User::class);
}
```

---

## セットアップ手順

### 1. 環境構築 (Laravel Sail)

```bash
# Dockerを起動
cd backend
./vendor/bin/sail up -d

# 依存パッケージのインストール
./vendor/bin/sail composer install
```

### 2. 環境変数の設定

`.env` ファイルを編集:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=toeic_daily
DB_USERNAME=sail
DB_PASSWORD=password
```

### 3. マイグレーション実行

```bash
./vendor/bin/sail artisan migrate
```

### 4. シーダー実行 (サンプルデータ投入)

```bash
./vendor/bin/sail artisan db:seed
```

---

## API エンドポイント (将来実装予定)

| メソッド | エンドポイント | 説明 |
|---------|-------------|------|
| GET | `/api/questions/daily` | 今日の問題を取得 |
| POST | `/api/answers` | 回答を記録 |
| GET | `/api/progress` | 学習進捗を取得 |
| POST | `/api/generate` | AI で問題を生成 (管理者用) |

---

## パフォーマンス最適化

### クエリ最適化

- N+1 問題の防止: Eager Loading を使用
  ```php
  Question::with('vocabulary')->get();
  ```
- インデックスの活用: 複合インデックスで検索を高速化

### キャッシング戦略

- 生成済み問題は `generated_date` でキャッシュ
- Laravel の標準キャッシュ機能を活用

```php
Cache::remember("questions:{$date}:{$type}:{$difficulty}", 3600, function () {
    return Question::where('generated_date', $date)->get();
});
```

---

## セキュリティ考慮事項

1. **Mass Assignment 対策**: `$fillable` または `$guarded` を適切に設定
2. **SQL インジェクション対策**: Eloquent ORM のパラメータバインディングで自動防御
3. **認証・認可**: Laravel Sanctum で API トークン認証を実装
4. **環境変数管理**: `.env` ファイルで機密情報を管理 (.gitignore に追加)

---

## 将来の拡張

- [ ] ユーザー認証機能 (Laravel Sanctum)
- [ ] 学習時間トラッキング (`study_time` カラムの活用)
- [ ] 問題のお気に入り・復習機能
- [ ] ユーザー間の正答率ランキング
- [ ] Redis によるキャッシング層の追加
- [ ] WebSocket によるリアルタイム学習通知

---

## マイグレーション履歴

| 日付 | ファイル名 | 説明 |
|------|----------|------|
| 2024-01-16 | `create_vocabularies_table` | 単語・イディオムテーブル作成 |
| 2024-01-16 | `create_questions_table` | 問題テーブル作成 |
| 2024-01-16 | `create_users_table` | ユーザーテーブル作成 |
| 2024-01-16 | `create_user_answers_table` | 回答履歴テーブル作成 |
| 2024-01-16 | `create_user_progress_table` | 学習進捗テーブル作成 |
| 2024-01-16 | `create_generation_logs_table` | 生成ログテーブル作成 |

---

## 参考リンク

- [Laravel 11 ドキュメント](https://laravel.com/docs/11.x)
- [Eloquent ORM](https://laravel.com/docs/11.x/eloquent)
- [Laravel Sail](https://laravel.com/docs/11.x/sail)
- [OpenAI API ドキュメント](https://platform.openai.com/docs)
- [Claude API ドキュメント](https://docs.anthropic.com/)
