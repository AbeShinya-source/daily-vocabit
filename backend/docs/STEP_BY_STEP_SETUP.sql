-- ========================================
-- ステップ1: データベースリセット
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
PRAGMA foreign_keys = OFF;
DROP TABLE IF EXISTS generation_logs;
DROP TABLE IF EXISTS user_progress;
DROP TABLE IF EXISTS user_answers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS vocabularies;
PRAGMA foreign_keys = ON;

-- 実行後、下のメッセージが出ればOK: "Commands completed successfully"
-- 次に進む前に、左側のナビゲーターで Tables を右クリック → Refresh


-- ========================================
-- ステップ2-1: vocabularies テーブル作成
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
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

-- 実行後、左側のナビゲーターを Refresh して vocabularies が表示されることを確認


-- ========================================
-- ステップ2-2: questions テーブル作成
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
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


-- ========================================
-- ステップ2-3: users テーブル作成
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    password VARCHAR(255),
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);


-- ========================================
-- ステップ2-4: user_answers テーブル作成
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
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


-- ========================================
-- ステップ2-5: user_progress テーブル作成
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
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


-- ========================================
-- ステップ2-6: generation_logs テーブル作成
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
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


-- ========================================
-- ステップ3: テーブル確認
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;

-- 以下の6つのテーブルが表示されればOK:
-- generation_logs
-- questions
-- user_answers
-- user_progress
-- users
-- vocabularies


-- ========================================
-- ステップ4: サンプルデータ投入
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
INSERT INTO vocabularies (word, type, difficulty, meaning, part_of_speech, frequency, tags, created_at, updated_at)
VALUES
  ('expand', 'WORD', 1, '拡大する', '動詞', 85, 'ビジネス,成長', datetime('now'), datetime('now')),
  ('negotiate', 'WORD', 1, '交渉する', '動詞', 78, 'ビジネス,交渉', datetime('now'), datetime('now')),
  ('implement', 'WORD', 2, '実装する、実行する', '動詞', 92, 'ビジネス,プロジェクト', datetime('now'), datetime('now')),
  ('consolidate', 'WORD', 2, '統合する', '動詞', 70, 'ビジネス,統合', datetime('now'), datetime('now')),
  ('exceed', 'WORD', 1, '超える', '動詞', 88, 'ビジネス,目標', datetime('now'), datetime('now')),
  ('substantial', 'WORD', 2, '相当な、実質的な', '形容詞', 75, 'ビジネス,重要', datetime('now'), datetime('now')),
  ('accommodate', 'WORD', 1, '収容する、適応させる', '動詞', 68, 'ビジネス,対応', datetime('now'), datetime('now')),
  ('comprehend', 'WORD', 2, '理解する', '動詞', 72, '学習,理解', datetime('now'), datetime('now')),
  ('hit the books', 'IDIOM', 2, '勉強を始める', NULL, 60, '学習,勉強', datetime('now'), datetime('now')),
  ('break the ice', 'IDIOM', 1, '緊張をほぐす', NULL, 55, 'コミュニケーション', datetime('now'), datetime('now')),
  ('get the ball rolling', 'IDIOM', 1, '物事を始める', NULL, 58, 'ビジネス,開始', datetime('now'), datetime('now')),
  ('on the same page', 'IDIOM', 1, '意見が一致している', NULL, 62, 'コミュニケーション,合意', datetime('now'), datetime('now'));


-- ========================================
-- ステップ5: 問題データ投入
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
INSERT INTO questions (vocabulary_id, type, difficulty, question_text, choices, correct_index, explanation, generated_date, is_active, created_at, updated_at)
VALUES
  (1, 'WORD', 1, 'The company plans to _____ its operations overseas.', '["expand","expect","export","expose"]', 0, '「expand」は「拡大する」という意味で、この文脈に最も適しています。海外展開を拡大するという意味になります。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (2, 'WORD', 1, 'We need to _____ the terms with our suppliers.', '["negotiate","navigate","nominate","notify"]', 0, '「negotiate」は「交渉する」という意味で、契約条件について話し合う場面で使います。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (3, 'WORD', 2, 'The team will _____ the new system next quarter.', '["implement","compliment","supplement","document"]', 0, '「implement」は「実装する、導入する」という意味で、新しいシステムを稼働させる際に使用します。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (5, 'WORD', 1, 'Sales this quarter _____ our expectations by 20%.', '["exceed","precede","recede","concede"]', 0, '「exceed」は「超える」という意味で、期待値を上回るという意味で使われます。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (7, 'WORD', 1, 'The hotel can _____ up to 500 guests.', '["accommodate","accumulate","accelerate","accentuate"]', 0, '「accommodate」は「収容する」という意味で、宿泊施設の収容人数を表す際によく使われます。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (9, 'IDIOM', 2, 'I need to _____ for the exam tomorrow.', '["hit the books","break the ice","get the ball rolling","on the same page"]', 0, '「hit the books」は「勉強を始める」という意味のイディオムです。試験勉強をする場面で使われます。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (10, 'IDIOM', 1, 'Let me tell a joke to _____ at the meeting.', '["break the ice","hit the books","get the ball rolling","on the same page"]', 0, '「break the ice」は「緊張をほぐす」という意味で、初対面の場や会議の冒頭で使われます。', '2024-01-16', 1, datetime('now'), datetime('now')),
  (11, 'IDIOM', 1, 'Let''s _____ with the project presentation.', '["get the ball rolling","break the ice","hit the books","on the same page"]', 0, '「get the ball rolling」は「物事を始める」という意味で、プロジェクトや活動を開始する際に使います。', '2024-01-16', 1, datetime('now'), datetime('now'));


-- ========================================
-- ステップ6: 最終確認
-- このブロックを選択して Ctrl+Enter で実行
-- ========================================
SELECT 'vocabularies' as table_name, COUNT(*) as count FROM vocabularies
UNION ALL
SELECT 'questions', COUNT(*) FROM questions
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'user_answers', COUNT(*) FROM user_answers
UNION ALL
SELECT 'user_progress', COUNT(*) FROM user_progress
UNION ALL
SELECT 'generation_logs', COUNT(*) FROM generation_logs;

-- 期待される結果:
-- vocabularies: 12
-- questions: 8
-- users: 0
-- user_answers: 0
-- user_progress: 0
-- generation_logs: 0
