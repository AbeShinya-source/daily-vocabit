# TOEIC Daily

TOEIC学習アプリ - 毎日の単語・イディオム学習をサポート

## 📋 プロジェクト概要

- **フロントエンド**: Vue 3 + Vite + Pinia
- **バックエンド**: Laravel 11 + SQLite (開発) / MySQL (本番)
- **AI**: Gemini API (問題自動生成)

## 🚀 開発環境セットアップ

### 前提条件

- Node.js 18以上
- PHP 8.2以上
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
sqlite3 /path/to/database.sqlite < docs/INSERT_TOEIC_VOCABULARIES_CLEAN.sql

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

詳細は [backend/docs/API_DOCUMENTATION.md](backend/docs/API_DOCUMENTATION.md) を参照

### 主要エンドポイント

- `GET /api/questions/daily` - 今日の問題を取得
- `POST /api/answers` - 回答を記録
- `GET /api/progress` - 学習進捗を取得
- `GET /api/vocabularies` - 単語一覧を取得

## 🤖 AI問題生成

### 手動生成

```bash
cd backend

# 基礎レベルの単語問題を8問生成
php artisan questions:generate --type=WORD --difficulty=1 --count=8

# 上級レベルのイディオム問題を生成
php artisan questions:generate --type=IDIOM --difficulty=2 --count=4
```

### 自動生成設定

`app/Console/Kernel.php` で毎日自動生成を設定可能（詳細は [AI_GENERATION_GUIDE.md](backend/docs/AI_GENERATION_GUIDE.md)）

## 📊 データベース

### 現在のデータ

- **単語数**: 122単語（難易度1: 57、難易度2: 65）
- **問題数**: 自動生成により増加
- **データソース**: TOEIC公式対策コンテンツ

### 単語の追加

```bash
cd backend
sqlite3 /path/to/database.sqlite < docs/INSERT_TOEIC_VOCABULARIES_CLEAN.sql
```

## 🎯 実装済み機能

- ✅ データベーススキーマ設計
- ✅ REST API実装
- ✅ Gemini API問題生成機能
- ✅ TOEIC頻出単語データ投入
- ✅ フロントエンドとバックエンドの連携
- ✅ クイズモード（基礎/上級）
- ✅ 回答記録機能

## 🔜 今後の実装予定

- ⏭️ 学習進捗の可視化
- ⏭️ ユーザー認証機能
- ⏭️ 毎日の自動問題生成スケジューラー
- ⏭️ 復習機能（間違えた問題の再出題）
- ⏭️ 成績レポート

## 💰 コスト試算

### Gemini API（無料枠）

- 毎日1,500リクエスト無料
- 10問生成: 約$0.0006（約0.09円）
- **月間コスト**: 約$0.018（約2.7円）

→ 無料枠内で十分運用可能

## 📖 ドキュメント

- [API Documentation](backend/docs/API_DOCUMENTATION.md)
- [Database Schema](backend/docs/DATABASE_SCHEMA.md)
- [AI Generation Guide](backend/docs/AI_GENERATION_GUIDE.md)
- [Database Strategy](backend/docs/DATABASE_STRATEGY.md)

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
php artisan tinker               # REPL起動
```

## 📝 ライセンス

MIT
