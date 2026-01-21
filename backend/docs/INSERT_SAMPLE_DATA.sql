-- サンプルデータ投入スクリプト (SQLite用)
-- テーブル作成後に実行してください

-- ========================================
-- 1. vocabularies テーブルにサンプルデータ
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
-- 2. questions テーブルにサンプルデータ
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
-- 3. データ確認用クエリ
-- ========================================

-- 単語一覧
SELECT * FROM vocabularies ORDER BY type, difficulty, frequency DESC;

-- 問題一覧
SELECT * FROM questions ORDER BY difficulty, id;

-- 単語と問題を結合
SELECT
    v.word,
    v.type,
    v.difficulty,
    v.meaning,
    q.question_text,
    q.choices,
    q.explanation
FROM vocabularies v
LEFT JOIN questions q ON v.id = q.vocabulary_id
ORDER BY v.type, v.difficulty;
