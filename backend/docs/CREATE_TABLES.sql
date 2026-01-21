-- TOEIC Daily データベーステーブル作成スクリプト (SQLite用)
-- 実行順序に従って、1つずつ実行してください

-- ========================================
-- 1. vocabularies テーブル
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

-- ========================================
-- 2. questions テーブル
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
-- 3. users テーブル
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
-- 4. user_answers テーブル
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
-- 5. user_progress テーブル
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
-- 6. generation_logs テーブル
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
