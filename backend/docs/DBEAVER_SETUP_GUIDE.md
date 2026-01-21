# DBeaver セットアップガイド（完全版）

## ステップ1: データベース接続

### 1-1. 新しい接続を作成

1. DBeaverを起動
2. 左上の `Database` メニュー → `New Database Connection` をクリック
   - または、ツールバーの **プラグアイコン** をクリック
3. `SQLite` を選択して `Next` をクリック

### 1-2. 接続情報を入力

**Path** に以下のいずれかを入力:

```
C:\dev\toeic-daily\database.sqlite
```

または（WSL経由の場合）:

```
\\wsl.localhost\Ubuntu\home\abe_shinya\dev\toeic-daily\backend\database.sqlite
```

### 1-3. 接続テスト

1. `Test Connection` ボタンをクリック
2. "Connected" と表示されればOK
3. 初回はドライバのダウンロードが必要な場合があります（自動でダウンロードされます）
4. `Finish` をクリック

---

## ステップ2: テーブル作成

### 2-1. SQLエディタを開く

接続が完了したら、以下のいずれかの方法でSQLエディタを開きます:

**方法A: メニューから**
1. 上部メニューの `SQL Editor` をクリック
2. `New SQL Script` を選択

**方法B: ショートカットキー**
- `Ctrl + ]` を押す（または `F3`）

**方法C: 接続を右クリック**
1. 左側のデータベースナビゲーターで接続を右クリック
2. `SQL Editor` → `New SQL Script` を選択

### 2-2. テーブル作成SQLの実行

以下の順序で、1つずつSQLを実行します。

---

#### ① vocabularies テーブル

SQLエディタに以下をコピー&ペースト:

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

**実行方法:**
- `Ctrl + Alt + X` を押す
- または、エディタ内で右クリック → `Execute SQL Script`
- または、ツールバーの **▶（実行）ボタン** をクリック

**確認:**
- 下部の `Execution Log` に "OK" と表示されればOK
- エラーが出たら、メッセージを確認

---

#### ② questions テーブル

```sql
CREATE TABLE questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vocabulary_id INTEGER NOT NULL,
    type TEXT CHECK(type IN ('WORD', 'IDIOM')) NOT NULL,
    difficulty INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    choices TEXT NOT NULL,
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

---

#### ③ users テーブル

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

---

#### ④ user_answers テーブル

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

---

#### ⑤ user_progress テーブル

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

---

#### ⑥ generation_logs テーブル

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

## ステップ3: テーブル確認

### 3-1. テーブル一覧を表示

1. 左側のデータベースナビゲーターで接続を展開
2. `Tables` フォルダをクリック
3. 以下の6つのテーブルが表示されていればOK:
   - vocabularies
   - questions
   - users
   - user_answers
   - user_progress
   - generation_logs

### 3-2. テーブル構造を確認

各テーブルをダブルクリックすると、カラム情報が表示されます。

---

## ステップ4: サンプルデータ投入（オプション）

### 4-1. 単語データを投入

```sql
INSERT INTO vocabularies (word, type, difficulty, meaning, part_of_speech, frequency, tags, created_at, updated_at)
VALUES
  ('expand', 'WORD', 1, '拡大する', '動詞', 85, 'ビジネス,成長', datetime('now'), datetime('now')),
  ('negotiate', 'WORD', 1, '交渉する', '動詞', 78, 'ビジネス,交渉', datetime('now'), datetime('now')),
  ('implement', 'WORD', 2, '実装する、実行する', '動詞', 92, 'ビジネス,プロジェクト', datetime('now'), datetime('now')),
  ('consolidate', 'WORD', 2, '統合する', '動詞', 70, 'ビジネス,統合', datetime('now'), datetime('now')),
  ('hit the books', 'IDIOM', 2, '勉強を始める', NULL, 60, '学習,勉強', datetime('now'), datetime('now')),
  ('break the ice', 'IDIOM', 1, '緊張をほぐす', NULL, 55, 'コミュニケーション', datetime('now'), datetime('now'));
```

### 4-2. 問題データを投入

```sql
INSERT INTO questions (vocabulary_id, type, difficulty, question_text, choices, correct_index, explanation, generated_date, is_active, created_at, updated_at)
VALUES
  (1, 'WORD', 1, 'The company plans to _____ its operations overseas.', '["expand","expect","export","expose"]', 0, '「expand」は「拡大する」という意味で、この文脈に最も適しています。海外展開を拡大するという意味になります。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (2, 'WORD', 1, 'We need to _____ the terms with our suppliers.', '["negotiate","navigate","nominate","notify"]', 0, '「negotiate」は「交渉する」という意味で、契約条件について話し合う場面で使います。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (3, 'WORD', 2, 'The team will _____ the new system next quarter.', '["implement","compliment","supplement","document"]', 0, '「implement」は「実装する、導入する」という意味で、新しいシステムを稼働させる際に使用します。', '2024-01-16', 1, datetime('now'), datetime('now'));
```

### 4-3. データ確認

```sql
-- vocabularies テーブルの確認
SELECT * FROM vocabularies;

-- questions テーブルの確認
SELECT * FROM questions;

-- 単語と問題を結合して確認
SELECT
    v.word,
    v.meaning,
    q.question_text,
    q.choices
FROM vocabularies v
LEFT JOIN questions q ON v.id = q.vocabulary_id;
```

---

## 便利なショートカットキー

| ショートカット | 機能 |
|-------------|------|
| `Ctrl + Enter` | 選択したSQLを実行 |
| `Ctrl + Alt + X` | スクリプト全体を実行 |
| `Ctrl + ]` | 新しいSQLエディタを開く |
| `Ctrl + Shift + F` | SQLフォーマット（整形） |
| `F4` | テーブル構造を表示 |
| `Ctrl + Space` | オートコンプリート |

---

## トラブルシューティング

### エラー: "table already exists"

すでにテーブルが存在しています。削除してから再作成:

```sql
DROP TABLE IF EXISTS generation_logs;
DROP TABLE IF EXISTS user_progress;
DROP TABLE IF EXISTS user_answers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS vocabularies;
```

その後、再度CREATE TABLEを実行してください。

### エラー: "FOREIGN KEY constraint failed"

外部キー制約が有効になっていない可能性があります:

```sql
PRAGMA foreign_keys = ON;
```

その後、再度テーブル作成を試してください。

### テーブルが表示されない

1. 左側のナビゲーターで接続を右クリック
2. `Refresh` を選択
3. `Tables` フォルダを展開

---

## 次のステップ

✅ テーブル作成が完了したら:

1. サンプルデータを投入して動作確認
2. AI問題生成機能の実装に進む
3. バックエンドAPIの開発を開始

データベース構造を確認しながら開発を進めていきましょう！
