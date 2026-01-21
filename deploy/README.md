# EC2 デプロイ手順

## 1. EC2インスタンス作成

### AWS コンソールで実行

1. **EC2ダッシュボード** → **インスタンスを起動**

2. **設定内容:**
   - **名前:** `toeic-daily`
   - **AMI:** Ubuntu Server 22.04 LTS (64-bit x86)
   - **インスタンスタイプ:** `t3.micro`（無料枠対象）または `t3.small`
   - **キーペア:** 新規作成 or 既存を選択（SSH接続用）
   - **ネットワーク設定:**
     - パブリックIPの自動割り当て: **有効**
     - セキュリティグループ: 新規作成
       - SSH (22): マイIP
       - HTTP (80): 0.0.0.0/0
       - HTTPS (443): 0.0.0.0/0
   - **ストレージ:** 20GB gp3

3. **インスタンスを起動**

## 2. SSH接続

```bash
# キーペアのパーミッション設定
chmod 400 your-key.pem

# SSH接続
ssh -i your-key.pem ubuntu@YOUR_EC2_PUBLIC_IP
```

## 3. サーバーセットアップ

```bash
# リポジトリをクローン
cd /tmp
git clone https://github.com/YOUR_USERNAME/toeic-daily.git

# アプリケーションディレクトリに移動
sudo mv toeic-daily /var/www/

# セットアップスクリプト実行
sudo bash /var/www/toeic-daily/deploy/setup-ec2.sh
```

## 4. 環境設定

```bash
# 本番用.envを編集
sudo nano /var/www/toeic-daily/backend/.env.production
```

以下を設定:
- `APP_URL`: `http://YOUR_EC2_PUBLIC_IP`
- `SANCTUM_STATEFUL_DOMAINS`: `YOUR_EC2_PUBLIC_IP`
- `GEMINI_API_KEY`: あなたのGemini APIキー

## 5. デプロイ実行

```bash
sudo bash /var/www/toeic-daily/deploy/deploy.sh
```

## 6. Cronジョブ設定（問題自動生成）

```bash
# cronジョブを設定
sudo crontab -u www-data /var/www/toeic-daily/deploy/crontab

# 確認
sudo crontab -u www-data -l
```

## 7. 動作確認

ブラウザで `http://YOUR_EC2_PUBLIC_IP` にアクセス

## トラブルシューティング

### ログ確認
```bash
# Nginx エラーログ
sudo tail -f /var/log/nginx/toeic-daily-error.log

# Laravel ログ
sudo tail -f /var/www/toeic-daily/backend/storage/logs/laravel.log

# PHP-FPM ログ
sudo tail -f /var/log/php8.1-fpm.log
```

### パーミッションエラー
```bash
sudo chown -R www-data:www-data /var/www/toeic-daily/backend/storage
sudo chown -R www-data:www-data /var/www/toeic-daily/backend/bootstrap/cache
sudo chown -R www-data:www-data /var/www/toeic-daily/backend/database
sudo chmod -R 775 /var/www/toeic-daily/backend/storage
```

### サービス再起動
```bash
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

## ドメイン設定（オプション）

1. Route 53 または他のDNSでドメインを設定
2. A レコードでEC2のパブリックIPを設定
3. `.env.production` の `APP_URL` と `SANCTUM_STATEFUL_DOMAINS` を更新
4. Nginx設定の `server_name` を更新
5. Let's Encrypt でSSL証明書を取得:

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d your-domain.com
```

## コスト概算

- **t3.micro:** 約 $8-10/月（無料枠後）
- **t3.small:** 約 $15-20/月
- **EBS 20GB:** 約 $2/月
- **データ転送:** 最初の100GB/月は無料

---

## 更新デプロイ

```bash
cd /var/www/toeic-daily
sudo git pull origin main
sudo bash deploy/deploy.sh
```
