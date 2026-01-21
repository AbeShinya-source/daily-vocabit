# Daily Vocabit

毎日の英語学習習慣をサポートするTOEIC対策アプリ

🔗 **本番URL**: https://dailyvocabit.com

## 📋 プロジェクト概要

- **フロントエンド**: Vue 3 + Vite + Pinia
- **バックエンド**: Laravel 11
- **データベース**: SQLite (開発) / MySQL (本番)
- **AI**: Gemini API (問題自動生成)
- **インフラ**: AWS EC2 + Route 53 + SES

## ✨ 主な機能

- 毎日のTOEIC単語・イディオムクイズ（Standard / Hard）
- ユーザー認証（メール認証）
- 学習進捗のカレンダー表示
- 月間バッジシステム（ブロンズ/シルバー/ゴールド）
- マイページで学習履歴確認
- Gemini APIによる問題自動生成

## 🚀 開発環境セットアップ

### 前提条件

- Node.js 20以上
- PHP 8.3以上
- Composer
- SQLite3

### バックエンドセットアップ

```bash
cd backend

# 依存関係のインストール
composer install

# 環境変数の設定
cp .env.example .env
# .envファイルを編集してGEMINI_API_KEYを設定

# アプリケーションキーの生成
php artisan key:generate

# データベースの初期化
touch database/database.sqlite
php artisan migrate
php artisan db:seed

# サーバー起動
php artisan serve
```

### フロントエンドセットアップ

```bash
cd frontend

# 依存関係のインストール
npm install

# 開発サーバー起動
npm run dev
```

アクセス:
- フロントエンド: http://localhost:5173
- バックエンドAPI: http://localhost:8000/api

## 📚 API エンドポイント

### 認証

- `POST /api/auth/register` - ユーザー登録
- `POST /api/auth/verify-email` - メール認証
- `POST /api/auth/login` - ログイン
- `POST /api/auth/logout` - ログアウト
- `POST /api/auth/forgot-password` - パスワードリセット

### クイズ

- `GET /api/questions/daily` - 今日の問題を取得
- `POST /api/quiz/start` - クイズセッション開始
- `POST /api/quiz/answer` - 回答を送信
- `POST /api/quiz/complete` - クイズ完了

### ユーザー情報

- `GET /api/user` - ユーザー情報取得
- `GET /api/user/progress` - 学習進捗を取得
- `GET /api/user/badges` - バッジ一覧を取得
- `GET /api/user/calendar` - カレンダーデータ取得

## 🤖 AI問題生成

### 手動生成

```bash
cd backend

# Standard（難易度1）の問題を10問生成
php artisan questions:generate --difficulty=1

# Hard（難易度2）の問題を10問生成
php artisan questions:generate --difficulty=2
```

### 自動生成（Cron）

本番環境では毎日朝5時（JST）に自動生成されます。

## 🌐 本番環境

### インフラ構成

| サービス | 用途 |
|---------|------|
| EC2 (ap-southeast-2) | アプリケーションサーバー |
| MySQL 8.0 | データベース |
| Nginx | Webサーバー |
| Let's Encrypt | SSL証明書 |
| Route 53 | DNS |
| SES | メール送信 |

### デプロイ手順

```bash
# SSHで接続
ssh -i /path/to/daily-vocabit.pem ubuntu@3.106.137.164

# デプロイスクリプト実行
./deploy.sh
```

または、ローカルからワンライナーで:

```bash
ssh -i /path/to/daily-vocabit.pem ubuntu@3.106.137.164 "./deploy.sh"
```

### 手動デプロイ

```bash
# バックエンド更新
cd /var/www/backend
sudo git pull origin main
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache

# フロントエンド更新
cd /var/www/frontend
sudo git pull origin main
npm ci
npm run build
sudo cp -r dist/* /var/www/backend/public/

# 再起動
sudo systemctl restart php8.3-fpm
```

### 問題の手動生成（本番）

```bash
cd /var/www/backend
sudo -u www-data php artisan questions:generate --difficulty=1
sudo -u www-data php artisan questions:generate --difficulty=2
```

## 💰 コスト試算

### 月額費用（概算）

| サービス | 費用 |
|---------|------|
| EC2 (t2.micro) | 無料枠 or ~$10/月 |
| Route 53 | ~$0.50/月 |
| SES | 無料枠内 |
| Gemini API | 無料枠内 |
| ドメイン | ~$13/年 |

**合計**: 無料枠利用時は約$1/月、無料枠終了後は約$11/月

## 🛠️ 開発コマンド

### フロントエンド

```bash
npm run dev      # 開発サーバー起動
npm run build    # 本番ビルド
npm run preview  # ビルドのプレビュー
```

### バックエンド

```bash
php artisan serve                 # APIサーバー起動
php artisan questions:generate    # 問題生成
php artisan migrate               # マイグレーション実行
php artisan db:seed               # シーダー実行
```

## 📖 ドキュメント

- [API Documentation](backend/docs/API_DOCUMENTATION.md)
- [Database Schema](backend/docs/DATABASE_SCHEMA.md)
- [AI Generation Guide](backend/docs/AI_GENERATION_GUIDE.md)

## 📝 ライセンス

MIT
