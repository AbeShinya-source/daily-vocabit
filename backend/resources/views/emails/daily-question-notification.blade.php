<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>本日の問題</title>
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
            line-height: 1.8;
            margin-bottom: 24px;
        }
        .cta-button {
            display: block;
            width: fit-content;
            margin: 0 auto 24px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }
        .unsubscribe {
            color: #94a3b8;
            font-size: 11px;
            margin-top: 12px;
        }
        .unsubscribe a {
            color: #94a3b8;
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
            おはようございます！<br><br>
            本日（{{ $date }}）の問題が公開されました。<br>
            StandardとHard、それぞれ10問ずつ新しい問題にチャレンジできます。
        </p>

        <a href="{{ config('app.frontend_url') }}" class="cta-button">
            今日の問題を解く
        </a>

        <div class="footer">
            <p>&copy; Daily Vocabit</p>
            <p class="unsubscribe">
                このメールは通知設定をオンにしているユーザーに送信されています。<br>
                <a href="{{ config('app.frontend_url') }}/mypage">マイページ</a>から通知設定を変更できます。
            </p>
        </div>
    </div>
</body>
</html>
