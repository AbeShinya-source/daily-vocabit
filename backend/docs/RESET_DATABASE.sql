-- データベースリセットスクリプト
-- 既存のテーブルを全て削除してから再作成する場合に使用

-- 外部キー制約を一時的に無効化
PRAGMA foreign_keys = OFF;

-- 既存のテーブルを削除（存在する場合のみ）
DROP TABLE IF EXISTS generation_logs;
DROP TABLE IF EXISTS user_progress;
DROP TABLE IF EXISTS user_answers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS vocabularies;

-- 外部キー制約を再度有効化
PRAGMA foreign_keys = ON;

-- 確認: テーブル一覧を表示（空になっているはず）
SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;
