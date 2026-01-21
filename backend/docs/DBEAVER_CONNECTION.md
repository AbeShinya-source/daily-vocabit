# DBeaver 接続情報

## 方法1: SQLite（推奨 - 簡単）

### 接続情報

| 項目 | 値 |
|-----|-----|
| データベースタイプ | SQLite |
| データベースファイルパス | `/home/abe_shinya/dev/toeic-daily/backend/database.sqlite` |

### DBeaver での接続手順

1. DBeaver を起動
2. 新しい接続を作成: `Database` → `New Database Connection`
3. `SQLite` を選択して `Next`
4. `Path` に以下を入力:
   ```
   /home/abe_shinya/dev/toeic-daily/backend/database.sqlite
   ```
5. `Test Connection` をクリックして接続確認
6. `Finish` をクリック

### テーブル作成

現在、データベースファイルは空です。テーブルを作成するには、以下のSQLを実行してください。

#### 1. vocabularies テーブル

```sql
CREATE TABLE vocabularies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    word VARCHAR(255) NOT NULL UNIQUE,
    type TEXT CHECK(type IN ('WORD', 'IDIOM')) NOT NULL,
    difficulty INTEGER NOT NULL,
    meaning VARCHAR(255) NOT NULL,
    part_of_speech VARCHAR(255),
    example_sentence TEXT,
    synonym VARCHAR(255),
    antonym VARCHAR(255),
    frequency INTEGER DEFAULT 0,
    tags VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_vocabularies_type_difficulty ON vocabularies(type, difficulty);
CREATE INDEX idx_vocabularies_frequency ON vocabularies(frequency);
```

#### 2. questions テーブル

```sql
CREATE TABLE questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vocabulary_id INTEGER NOT NULL,
    type TEXT CHECK(type IN ('WORD', 'IDIOM')) NOT NULL,
    difficulty INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    choices TEXT NOT NULL, -- JSON配列として保存
    correct_index INTEGER NOT NULL,
    explanation TEXT NOT NULL,
    generated_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    usage_count INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (vocabulary_id) REFERENCES vocabularies(id) ON DELETE CASCADE
);

CREATE INDEX idx_questions_generated ON questions(generated_date, type, difficulty, is_active);
CREATE INDEX idx_questions_vocabulary ON questions(vocabulary_id);
```

#### 3. users テーブル

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    password VARCHAR(255),
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 4. user_answers テーブル

```sql
CREATE TABLE user_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    question_id INTEGER NOT NULL,
    selected_index INTEGER NOT NULL,
    is_correct BOOLEAN NOT NULL,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_answers_user ON user_answers(user_id, answered_at);
CREATE INDEX idx_user_answers_question ON user_answers(question_id);
```

#### 5. user_progress テーブル

```sql
CREATE TABLE user_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    date DATE NOT NULL,
    type TEXT CHECK(type IN ('WORD', 'IDIOM')) NOT NULL,
    difficulty INTEGER NOT NULL,
    total_questions INTEGER NOT NULL,
    correct_count INTEGER NOT NULL,
    score_percent INTEGER NOT NULL,
    study_time INTEGER,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, date, type, difficulty)
);

CREATE INDEX idx_user_progress_user_date ON user_progress(user_id, date);
```

#### 6. generation_logs テーブル

```sql
CREATE TABLE generation_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    generated_date DATE NOT NULL,
    type TEXT CHECK(type IN ('WORD', 'IDIOM')) NOT NULL,
    difficulty INTEGER NOT NULL,
    questions_count INTEGER NOT NULL,
    ai_model VARCHAR(255) NOT NULL,
    prompt_tokens INTEGER,
    completion_tokens INTEGER,
    total_cost REAL,
    status TEXT CHECK(status IN ('SUCCESS', 'PARTIAL', 'FAILED')) DEFAULT 'SUCCESS',
    error_message TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_generation_logs_date ON generation_logs(generated_date);
```

---

## 方法2: MySQL（Docker使用）

Docker Desktop を起動してから、以下の手順でMySQLを起動できます。

### docker-compose.yml を作成

`backend/docker-compose.yml`:

```yaml
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

volumes:
  mysql_data:
```

### MySQL 起動

```bash
cd /home/abe_shinya/dev/toeic-daily/backend
docker compose up -d
```

### DBeaver 接続情報

| 項目 | 値 |
|-----|-----|
| データベースタイプ | MySQL |
| ホスト | localhost |
| ポート | 3306 |
| データベース名 | toeic_daily |
| ユーザー名 | sail |
| パスワード | password |

### DBeaver での接続手順

1. DBeaver を起動
2. 新しい接続を作成: `Database` → `New Database Connection`
3. `MySQL` を選択して `Next`
4. 接続情報を入力:
   - **Server Host**: `localhost`
   - **Port**: `3306`
   - **Database**: `toeic_daily`
   - **Username**: `sail`
   - **Password**: `password`
5. `Test Connection` をクリック
   - 初回は MySQL ドライバのダウンロードが必要な場合があります
6. `Finish` をクリック

### マイグレーション実行

Laravelプロジェクトが完全にセットアップされた後:

```bash
cd /home/abe_shinya/dev/toeic-daily/backend
php artisan migrate
```

---

## サンプルデータの投入

### vocabularies テーブルにデータ挿入

```sql
INSERT INTO vocabularies (word, type, difficulty, meaning, part_of_speech, frequency, tags, created_at, updated_at)
VALUES
  ('expand', 'WORD', 1, '拡大する', '動詞', 85, 'ビジネス,成長', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  ('negotiate', 'WORD', 1, '交渉する', '動詞', 78, 'ビジネス,交渉', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  ('implement', 'WORD', 2, '実装する、実行する', '動詞', 92, 'ビジネス,プロジェクト', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  ('hit the books', 'IDIOM', 2, '勉強を始める', NULL, 60, '学習,勉強', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  ('break the ice', 'IDIOM', 1, '緊張をほぐす', NULL, 55, 'コミュニケーション', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
```

### questions テーブルにデータ挿入

```sql
INSERT INTO questions (vocabulary_id, type, difficulty, question_text, choices, correct_index, explanation, generated_date, is_active, created_at, updated_at)
VALUES
  (1, 'WORD', 1, 'The company plans to _____ its operations overseas.', '["expand","expect","export","expose"]', 0, '「expand」は「拡大する」という意味で、この文脈に最も適しています。', '2024-01-16', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (2, 'WORD', 1, 'We need to _____ the terms with our suppliers.', '["negotiate","navigate","nominate","notify"]', 0, '「negotiate」は「交渉する」という意味で、契約条件について話し合う場面で使います。', '2024-01-16', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
```

---

## トラブルシューティング

### SQLite: データベースファイルが見つからない

```bash
# ファイルを作成
touch /home/abe_shinya/dev/toeic-daily/backend/database.sqlite

# パーミッション確認
ls -la /home/abe_shinya/dev/toeic-daily/backend/database.sqlite
```

### MySQL: 接続できない

1. Docker コンテナが起動しているか確認:
   ```bash
   docker ps
   ```

2. コンテナを再起動:
   ```bash
   docker compose down
   docker compose up -d
   ```

3. ポートが使用中の場合、docker-compose.yml のポート番号を変更:
   ```yaml
   ports:
     - "3307:3306"  # 3306 → 3307 に変更
   ```

### DBeaver: MySQL ドライバが見つからない

- DBeaver が自動的にドライバをダウンロードします
- `Download` ボタンをクリックしてドライバをインストール

---

## 推奨事項

開発初期段階では **SQLite（方法1）** の使用を推奨します：

✅ メリット:
- サーバー不要、即座に使える
- ファイルベースで管理が簡単
- 開発・テストに最適

❌ デメリット:
- 本番環境では不向き（パフォーマンス、同時接続数）
- 一部の高度な機能が制限される

本番環境や複数人での開発では、後で **MySQL** に移行することを推奨します。
