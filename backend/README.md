# TOEIC Daily Backend (Laravel)

AI を活用した TOEIC 学習アプリケーションのバックエンド API

## 技術スタック

- **フレームワーク**: Laravel 11.x
- **言語**: PHP 8.2+
- **データベース**: MySQL 8.0
- **ORM**: Eloquent
- **開発環境**: Docker + Laravel Sail

---

## セットアップ手順

### 前提条件

- Docker Desktop がインストールされていること
- Composer がインストールされていること (またはDocker経由で実行)

### 1. Laravel プロジェクトのセットアップ

```bash
# プロジェクトディレクトリに移動
cd toeic-daily/backend

# Laravel Sail 経由で Laravel プロジェクトを作成
curl -s "https://laravel.build/backend?with=mysql" | bash

# または Composer でインストール
composer create-project laravel/laravel .
```

### 2. Docker コンテナの起動

```bash
# Sail を使ってコンテナを起動
./vendor/bin/sail up -d

# エイリアスを設定すると便利
alias sail='./vendor/bin/sail'
sail up -d
```

### 3. 環境変数の設定

`.env` ファイルを編集:

```env
APP_NAME="TOEIC Daily"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=toeic_daily
DB_USERNAME=sail
DB_PASSWORD=password
```

### 4. データベースマイグレーション

```bash
# マイグレーションを実行
sail artisan migrate

# 確認 (オプション)
sail artisan migrate:status
```

### 5. 開発サーバーの起動

```bash
# Sail でサーバー起動 (既に起動している場合は不要)
sail up

# ブラウザでアクセス
# http://localhost
```

---

## プロジェクト構成

```
backend/
├── app/
│   ├── Models/              # Eloquent モデル
│   │   ├── Vocabulary.php
│   │   ├── Question.php
│   │   ├── User.php
│   │   ├── UserAnswer.php
│   │   ├── UserProgress.php
│   │   └── GenerationLog.php
│   └── Http/
│       └── Controllers/     # API コントローラー
├── database/
│   ├── migrations/          # データベースマイグレーション
│   │   ├── 2024_01_16_000001_create_vocabularies_table.php
│   │   ├── 2024_01_16_000002_create_questions_table.php
│   │   ├── 2024_01_16_000003_create_users_table.php
│   │   ├── 2024_01_16_000004_create_user_answers_table.php
│   │   ├── 2024_01_16_000005_create_user_progress_table.php
│   │   └── 2024_01_16_000006_create_generation_logs_table.php
│   └── seeders/             # シーダー (サンプルデータ)
├── routes/
│   ├── api.php              # API ルート定義
│   └── web.php              # Web ルート定義
├── docs/
│   └── DATABASE_SCHEMA.md   # データベーススキーマドキュメント
├── .env                     # 環境変数 (Git管理外)
├── composer.json            # PHP 依存パッケージ
└── README.md                # このファイル
```

---

## データベース設計

詳細は [DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md) を参照してください。

### テーブル一覧

- `vocabularies` - 単語・イディオムマスターデータ
- `questions` - AI 生成された問題データ
- `users` - ユーザー情報
- `user_answers` - ユーザーの回答履歴
- `user_progress` - ユーザーの学習進捗
- `generation_logs` - AI 問題生成のログ

---

## 開発コマンド

### マイグレーション

```bash
# マイグレーション実行
sail artisan migrate

# マイグレーションをロールバック
sail artisan migrate:rollback

# マイグレーションをリセット
sail artisan migrate:fresh
```

### シーダー

```bash
# シーダーを実行
sail artisan db:seed

# マイグレーション + シーダー
sail artisan migrate:fresh --seed
```

### Tinker (REPL)

```bash
# Tinker を起動
sail artisan tinker

# 例: 単語を作成
>>> Vocabulary::create(['word' => 'expand', 'type' => 'WORD', 'difficulty' => 1, 'meaning' => '拡大する', 'frequency' => 85]);
```

### テスト

```bash
# テストを実行
sail artisan test
```

---

## API エンドポイント (予定)

### 問題取得

```http
GET /api/questions/daily?type=WORD&difficulty=1
```

レスポンス例:

```json
{
  "questions": [
    {
      "id": 1,
      "question_text": "The company plans to _____ its operations overseas.",
      "choices": ["expand", "expect", "export", "expose"],
      "generated_date": "2024-01-16"
    }
  ]
}
```

### 回答記録

```http
POST /api/answers
Content-Type: application/json

{
  "question_id": 1,
  "selected_index": 0
}
```

---

## AI 問題生成の実装計画

### 1. OpenAI / Claude API 統合

```bash
# OpenAI PHP クライアントをインストール
sail composer require openai-php/client
```

### 2. 問題生成コマンド作成

```bash
sail artisan make:command GenerateDailyQuestions
```

### 3. スケジューラー設定

`app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // 毎日午前 0 時に問題を生成
    $schedule->command('questions:generate')->dailyAt('00:00');
}
```

---

## トラブルシューティング

### Docker が起動しない

```bash
# Docker Desktop が起動しているか確認
docker ps

# Sail を再起動
sail down
sail up -d
```

### マイグレーションエラー

```bash
# データベースをリセット
sail artisan migrate:fresh
```

### パーミッションエラー

```bash
# storage と bootstrap/cache のパーミッション修正
sail artisan storage:link
sudo chmod -R 777 storage bootstrap/cache
```

---

## ライセンス

MIT License

---

## 開発者

TOEIC Daily チーム
