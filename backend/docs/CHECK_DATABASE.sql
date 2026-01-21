-- データベース状態確認スクリプト

-- 1. 存在するテーブル一覧
SELECT name as table_name FROM sqlite_master WHERE type='table' ORDER BY name;

-- 2. 各テーブルのレコード数（テーブルが存在する場合のみ）
-- user_answersが存在する場合
SELECT 'user_answers' as table_name, COUNT(*) as record_count FROM user_answers;

-- 以下のクエリを1つずつ試してください
-- SELECT 'vocabularies' as table_name, COUNT(*) as record_count FROM vocabularies;
-- SELECT 'questions' as table_name, COUNT(*) as record_count FROM questions;
-- SELECT 'users' as table_name, COUNT(*) as record_count FROM users;
-- SELECT 'user_progress' as table_name, COUNT(*) as record_count FROM user_progress;
-- SELECT 'generation_logs' as table_name, COUNT(*) as record_count FROM generation_logs;
