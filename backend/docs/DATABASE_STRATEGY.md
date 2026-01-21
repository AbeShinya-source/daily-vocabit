# データベース戦略ガイド

## 概要

TOEIC Dailyでは、開発効率と本番環境の要件を両立するため、**環境に応じてデータベースを切り替える**戦略を採用します。

---

## 環境別データベース選択

| 環境 | データベース | 理由 |
|-----|------------|------|
| **ローカル開発** | SQLite | 高速、軽量、セットアップ不要 |
| **CI/CD（テスト）** | SQLite | テスト実行の高速化 |
| **ステージング** | MySQL | 本番環境と同じ構成 |
| **本番環境** | MySQL | 高パフォーマンス、スケーラビリティ |

---

## Phase 1: 開発開始（現在）→ SQLite

### メリット

✅ **即座に開発開始**
- Dockerの起動・設定不要
- ファイルベースで管理が簡単
- Git管理も可能（.gitignoreで除外推奨）

✅ **高速な開発サイクル**
- マイグレーションの実行が高速
- テストの実行が高速
- データのリセットが簡単

✅ **リソース消費が少ない**
- メモリ使用量が少ない
- CPUへの負荷が少ない
- バッテリー消費が少ない（ノートPC）

### セットアップ

```bash
# 1. データベースファイルを確認（既に作成済み）
ls -la /home/abe_shinya/dev/toeic-daily/backend/database.sqlite

# 2. .env ファイルを設定
cd /home/abe_shinya/dev/toeic-daily/backend
cat > .env << 'EOF'
APP_NAME="TOEIC Daily"
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
EOF

# 3. テーブル作成（SQLスクリプト実行）
# DBeaver または sqlite3 コマンドで実行
```

### DBeaverでの接続

1. 新しい接続作成 → SQLite
2. Path: `/home/abe_shinya/dev/toeic-daily/backend/database.sqlite`
3. [DBEAVER_CONNECTION.md](DBEAVER_CONNECTION.md) のSQLスクリプトを実行

---

## Phase 2: 本番デプロイ準備 → MySQL

### いつ切り替えるか

以下のタイミングでMySQLに切り替えを検討:

- ✅ 基本機能（問題生成、回答記録）が完成
- ✅ 複数ユーザーでのテストを開始
- ✅ 本番環境へのデプロイ準備

### MySQL のメリット（本番環境）

✅ **高パフォーマンス**
- 大量データの高速処理
- 複雑なクエリの最適化
- インデックスの効率的な利用

✅ **同時接続対応**
- 複数ユーザーの同時アクセス
- トランザクション管理
- ロック機構

✅ **スケーラビリティ**
- レプリケーション（読み取り分散）
- シャーディング（データ分散）
- クラウドサービスとの統合

### セットアップ

#### Docker Composeを使用

```bash
cd /home/abe_shinya/dev/toeic-daily/backend

# docker-compose.yml を作成
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: toeic-daily-mysql
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: toeic_daily
      MYSQL_USER: sail
      MYSQL_PASSWORD: password
    volumes:
      - mysql_data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password

volumes:
  mysql_data:
EOF

# MySQL起動
docker compose up -d

# 接続確認
docker compose ps
```

#### .env ファイルを更新

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toeic_daily
DB_USERNAME=sail
DB_PASSWORD=password
```

#### マイグレーション実行

```bash
# Laravelプロジェクトがセットアップされた後
php artisan migrate
```

---

## データベース切り替えの手順

### SQLite → MySQL への移行

#### 1. データのエクスポート（必要な場合）

```bash
# SQLiteからデータをダンプ
sqlite3 database.sqlite .dump > backup.sql
```

#### 2. MySQL 起動

```bash
docker compose up -d
```

#### 3. .env を更新

```env
# 変更前
DB_CONNECTION=sqlite

# 変更後
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toeic_daily
DB_USERNAME=sail
DB_PASSWORD=password
```

#### 4. マイグレーション実行

```bash
php artisan migrate:fresh
```

#### 5. データのインポート（必要な場合）

```bash
# シーダーでサンプルデータ投入
php artisan db:seed
```

---

## マイグレーションファイルの互換性

作成したマイグレーションファイルは、SQLiteとMySQL両方で動作するように設計されています。

### 互換性のポイント

✅ **基本データ型**
- `string()` → VARCHAR
- `text()` → TEXT
- `integer()` → INT
- `boolean()` → BOOLEAN（SQLiteは0/1）
- `json()` → JSON（SQLiteはTEXT）

✅ **外部キー制約**
- SQLite: `PRAGMA foreign_keys = ON;` が必要
- MySQL: デフォルトで有効

✅ **ENUM型の代替**
- SQLite: CHECK制約で実装
- MySQL: ネイティブENUM型

---

## 本番環境の選択肢

### 1. PlanetScale（推奨）

- MySQL互換のサーバーレスDB
- 自動スケーリング
- 無料プランあり
- Laravelとの統合が簡単

### 2. AWS RDS

- 本格的なマネージドMySQL
- 高可用性
- バックアップ・リストア機能

### 3. Supabase

- PostgreSQL（MySQLではない）
- リアルタイム機能
- 認証機能統合

---

## パフォーマンス最適化

### SQLite（開発環境）

```php
// config/database.php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => database_path('database.sqlite'),
    'foreign_key_constraints' => true, // 重要
],
```

### MySQL（本番環境）

```php
'mysql' => [
    'driver' => 'mysql',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true, // パフォーマンス向上
    ],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

---

## FAQ

### Q: SQLiteで開発したアプリはMySQLで動きますか？

**A:** はい。Laravelのマイグレーションとクエリビルダーを使っていれば、ほぼ問題なく動作します。

注意点:
- 日付フォーマットの違い
- JSON操作の構文
- トランザクション処理

### Q: いつMySQLに切り替えるべき？

**A:** 以下のタイミング:
- 複数人での開発開始時
- ステージング環境構築時
- 本番デプロイ1週間前

### Q: SQLiteのまま本番公開できますか？

**A:** 小規模アプリなら可能ですが、推奨しません。理由:
- 同時接続数の制限
- 書き込みロック
- スケーラビリティの問題

---

## まとめ

### 今すぐ（開発開始）

✅ **SQLiteを使う**
- 高速に開発開始
- AI問題生成機能の実装に集中
- マイグレーション・モデルのテスト

### 後で（本番準備）

✅ **MySQLに切り替え**
- Docker Composeで環境構築
- 本番環境でのテスト
- パフォーマンスチューニング

---

## 次のステップ

1. ✅ **SQLiteでDBeaverに接続**
   - [DBEAVER_CONNECTION.md](DBEAVER_CONNECTION.md) を参照
   - テーブル作成SQLを実行

2. ⏭️ **AI問題生成機能の実装**
   - OpenAI / Claude API統合
   - 日次バッチ処理

3. ⏭️ **MySQL移行の準備**（必要になったタイミングで）
   - docker-compose.yml 作成
   - マイグレーション検証
