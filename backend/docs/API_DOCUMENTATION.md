# TOEIC Daily API ドキュメント

## 概要

TOEIC Daily のバックエンド REST API ドキュメント。

**ベースURL**: `http://localhost:8000/api`

---

## 🔍 目次

1. [ヘルスチェック](#ヘルスチェック)
2. [問題取得API](#問題取得api)
3. [回答記録API](#回答記録api)
4. [学習進捗API](#学習進捗api)
5. [単語・イディオムAPI](#単語イディオムapi)

---

## ヘルスチェック

### GET `/health`

API の稼働状況を確認します。

**リクエスト例:**
```bash
curl http://localhost:8000/api/health
```

**レスポンス例:**
```json
{
  "status": "ok",
  "message": "TOEIC Daily API is running",
  "timestamp": "2024-01-16T12:00:00.000000Z"
}
```

---

## 問題取得API

### GET `/questions/daily`

今日の問題を取得します。

**クエリパラメータ:**

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|----------|---|------|---------|------|
| `type` | string | No | WORD | 問題タイプ（WORD/IDIOM） |
| `difficulty` | integer | No | 1 | 難易度（1=基礎, 2=上級） |
| `date` | string | No | 今日 | 問題の日付（YYYY-MM-DD） |

**リクエスト例:**
```bash
curl "http://localhost:8000/api/questions/daily?type=WORD&difficulty=1"
```

**レスポンス例:**
```json
{
  "success": true,
  "message": "問題を取得しました",
  "data": {
    "questions": [
      {
        "id": 1,
        "type": "WORD",
        "difficulty": 1,
        "questionText": "The company plans to _____ its operations overseas.",
        "choices": ["expand", "expect", "export", "expose"],
        "correctIndex": 0,
        "explanation": "「expand」は「拡大する」という意味で、この文脈に最も適しています。",
        "vocabulary": {
          "word": "expand",
          "meaning": "拡大する"
        }
      }
    ],
    "totalQuestions": 8,
    "date": "2024-01-16",
    "type": "WORD",
    "difficulty": 1
  }
}
```

---

### GET `/questions`

問題一覧を取得します（管理者用）。

**クエリパラメータ:**

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|----------|---|------|---------|------|
| `type` | string | No | - | フィルタ: 問題タイプ |
| `difficulty` | integer | No | - | フィルタ: 難易度 |
| `date` | string | No | - | フィルタ: 生成日 |
| `per_page` | integer | No | 10 | ページあたりの件数 |

**リクエスト例:**
```bash
curl "http://localhost:8000/api/questions?type=WORD&per_page=20"
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 50,
    "per_page": 20
  }
}
```

---

### GET `/questions/{id}`

問題詳細を取得します。

**パスパラメータ:**

| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| `id` | integer | Yes | 問題ID |

**リクエスト例:**
```bash
curl http://localhost:8000/api/questions/1
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "type": "WORD",
    "difficulty": 1,
    "questionText": "The company plans to _____ its operations overseas.",
    "choices": ["expand", "expect", "export", "expose"],
    "correctIndex": 0,
    "explanation": "「expand」は「拡大する」という意味です。",
    "vocabulary": {
      "word": "expand",
      "meaning": "拡大する"
    }
  }
}
```

---

## 回答記録API

### POST `/answers`

ユーザーの回答を記録します。

**リクエストボディ:**

| フィールド | 型 | 必須 | 説明 |
|----------|---|------|------|
| `question_id` | integer | Yes | 問題ID |
| `selected_index` | integer | Yes | 選択した選択肢（0-3） |
| `user_id` | integer | No | ユーザーID（認証未実装時はnull） |

**リクエスト例:**
```bash
curl -X POST http://localhost:8000/api/answers \
  -H "Content-Type: application/json" \
  -d '{
    "question_id": 1,
    "selected_index": 0
  }'
```

**レスポンス例:**
```json
{
  "success": true,
  "message": "回答を記録しました",
  "data": {
    "answer_id": 123,
    "is_correct": true,
    "correct_index": 0,
    "explanation": "「expand」は「拡大する」という意味です。"
  }
}
```

---

### GET `/answers/history`

ユーザーの回答履歴を取得します。

**クエリパラメータ:**

| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| `user_id` | integer | Yes | ユーザーID |

**リクエスト例:**
```bash
curl "http://localhost:8000/api/answers/history?user_id=1"
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "answers": [
      {
        "id": 123,
        "question_id": 1,
        "question_text": "The company plans to _____ its operations overseas.",
        "vocabulary_word": "expand",
        "selected_index": 0,
        "is_correct": true,
        "answered_at": "2024-01-16T12:00:00.000000Z"
      }
    ],
    "total": 50
  }
}
```

---

## 学習進捗API

### GET `/progress`

ユーザーの学習進捗を取得します。

**クエリパラメータ:**

| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| `user_id` | integer | Yes | ユーザーID |
| `start_date` | string | No | 開始日（YYYY-MM-DD） |
| `end_date` | string | No | 終了日（YYYY-MM-DD） |

**リクエスト例:**
```bash
curl "http://localhost:8000/api/progress?user_id=1&start_date=2024-01-01"
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "progress": [
      {
        "date": "2024-01-16",
        "type": "WORD",
        "difficulty": 1,
        "total_questions": 10,
        "correct_count": 8,
        "score_percent": 80,
        "study_time": 600
      }
    ],
    "total_records": 30
  }
}
```

---

### POST `/progress`

学習進捗を保存します。

**リクエストボディ:**

| フィールド | 型 | 必須 | 説明 |
|----------|---|------|------|
| `user_id` | integer | Yes | ユーザーID |
| `date` | string | Yes | 学習日（YYYY-MM-DD） |
| `type` | string | Yes | WORD/IDIOM |
| `difficulty` | integer | Yes | 難易度（1-3） |
| `total_questions` | integer | Yes | 問題数 |
| `correct_count` | integer | Yes | 正解数 |
| `study_time` | integer | No | 学習時間（秒） |

**リクエスト例:**
```bash
curl -X POST http://localhost:8000/api/progress \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "date": "2024-01-16",
    "type": "WORD",
    "difficulty": 1,
    "total_questions": 10,
    "correct_count": 8,
    "study_time": 600
  }'
```

**レスポンス例:**
```json
{
  "success": true,
  "message": "学習進捗を保存しました",
  "data": {
    "id": 45,
    "score_percent": 80
  }
}
```

---

## 単語・イディオムAPI

### GET `/vocabularies`

単語・イディオム一覧を取得します。

**クエリパラメータ:**

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|----------|---|------|---------|------|
| `type` | string | No | - | フィルタ: WORD/IDIOM |
| `difficulty` | integer | No | - | フィルタ: 難易度 |
| `search` | string | No | - | 検索キーワード |
| `sort_by` | string | No | frequency | ソート項目 |
| `sort_order` | string | No | desc | ソート順（asc/desc） |
| `per_page` | integer | No | 20 | ページあたりの件数 |

**リクエスト例:**
```bash
curl "http://localhost:8000/api/vocabularies?type=WORD&difficulty=1&per_page=10"
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "word": "expand",
        "type": "WORD",
        "difficulty": 1,
        "meaning": "拡大する",
        "frequency": 85
      }
    ],
    "total": 100,
    "per_page": 10
  }
}
```

---

### GET `/vocabularies/{id}`

単語・イディオム詳細を取得します。

**パスパラメータ:**

| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| `id` | integer | Yes | 単語ID |

**リクエスト例:**
```bash
curl http://localhost:8000/api/vocabularies/1
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "word": "expand",
    "type": "WORD",
    "difficulty": 1,
    "meaning": "拡大する",
    "part_of_speech": "動詞",
    "example_sentence": "The company expanded its operations.",
    "synonym": "enlarge, extend",
    "antonym": "contract, reduce",
    "frequency": 85,
    "tags": "ビジネス,成長",
    "questions_count": 5
  }
}
```

---

## エラーレスポンス

### 400 Bad Request
```json
{
  "success": false,
  "message": "user_idが必要です"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "問題が見つかりませんでした"
}
```

### 422 Unprocessable Entity
```json
{
  "success": false,
  "message": "バリデーションエラー",
  "errors": {
    "question_id": ["問題IDは必須です"]
  }
}
```

---

## CORS設定

開発環境では、フロントエンド（Vue.js）からのリクエストを許可するため、CORS設定が必要です。

`config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:5173'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

---

## テスト方法

### cURLでテスト

```bash
# ヘルスチェック
curl http://localhost:8000/api/health

# 問題取得
curl "http://localhost:8000/api/questions/daily?type=WORD&difficulty=1"

# 回答記録
curl -X POST http://localhost:8000/api/answers \
  -H "Content-Type: application/json" \
  -d '{"question_id": 1, "selected_index": 0}'
```

### Postmanでテスト

1. Postmanを起動
2. 新しいリクエストを作成
3. メソッドとURLを設定
4. Send をクリック

---

## 次のステップ

1. ✅ データベーススキーマ設計完了
2. ✅ REST API実装完了
3. ⏭️ フロントエンドとの連携
4. ⏭️ AI問題生成機能の実装
5. ⏭️ 認証機能の追加（Laravel Sanctum）

---

## 開発環境での実行

### Laravelサーバー起動（将来）

```bash
cd /home/abe_shinya/dev/toeic-daily/backend
php artisan serve
```

または Laravel Sail を使用:

```bash
./vendor/bin/sail up
./vendor/bin/sail artisan serve
```

サーバーが起動したら、`http://localhost:8000/api` でAPIにアクセスできます。
