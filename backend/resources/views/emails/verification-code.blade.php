<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>認証コード</title>
    <style>
        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 480px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo-text {
            font-size: 24px;
            font-weight: 700;
        }
        .logo-main {
            color: #5b7a9f;
        }
        .logo-sub {
            color: #b08968;
            margin-left: 4px;
        }
        .greeting {
            color: #334155;
            font-size: 16px;
            margin-bottom: 16px;
        }
        .message {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .code-container {
            background: linear-gradient(135deg, #f0f4f8 0%, #e5ecf2 100%);
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
        }
        .code-label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #334155;
            font-family: 'Courier New', monospace;
        }
        .expire-notice {
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
            margin-bottom: 24px;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span class="logo-text">
                <span class="logo-main">Daily</span>
                <span class="logo-sub">Vocabit</span>
            </span>
        </div>

        <p class="greeting">{{ $name }} 様</p>

        <p class="message">
            Daily Vocabitへのご登録ありがとうございます。<br>
            以下の認証コードを入力して、登録を完了してください。
        </p>

        <div class="code-container">
            <div class="code-label">認証コード</div>
            <div class="code">{{ $code }}</div>
        </div>

        <p class="expire-notice">
            ※ このコードは10分間有効です。
        </p>

        <div class="footer">
            <p>このメールに心当たりがない場合は、無視してください。</p>
            <p>&copy; Daily Vocabit</p>
        </div>
    </div>
</body>
</html>
