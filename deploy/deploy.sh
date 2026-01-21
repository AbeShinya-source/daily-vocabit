#!/bin/bash
# デプロイスクリプト
# 使用方法: sudo bash deploy.sh

set -e

APP_DIR="/var/www/toeic-daily"
BACKEND_DIR="$APP_DIR/backend"
FRONTEND_DIR="$APP_DIR/frontend"

echo "=========================================="
echo "TOEIC Daily - Deploy Script"
echo "=========================================="

# Backendセットアップ
echo ">>> Setting up Backend..."
cd $BACKEND_DIR

# Composerパッケージインストール
composer install --no-dev --optimize-autoloader

# .envファイルが存在しない場合はコピー
if [ ! -f .env ]; then
    cp .env.production .env
    php artisan key:generate
fi

# SQLiteデータベースファイル作成
touch database/database.sqlite
chown www-data:www-data database/database.sqlite

# マイグレーション実行
php artisan migrate --force

# シーダー実行（初回のみ）
# php artisan db:seed --force

# キャッシュクリア＆最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ストレージリンク
php artisan storage:link 2>/dev/null || true

# パーミッション設定
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

# Frontendビルド
echo ">>> Building Frontend..."
cd $FRONTEND_DIR
npm ci
npm run build

# ビルド済みファイルをpublicに配置
rm -rf $BACKEND_DIR/public/assets
cp -r dist/* $BACKEND_DIR/public/

# Nginx設定をコピー
echo ">>> Configuring Nginx..."
cp $APP_DIR/deploy/nginx.conf /etc/nginx/sites-available/toeic-daily
ln -sf /etc/nginx/sites-available/toeic-daily /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Nginx設定テスト
nginx -t

# サービス再起動
echo ">>> Restarting services..."
systemctl restart php8.1-fpm
systemctl restart nginx

echo "=========================================="
echo "Deploy complete!"
echo ""
echo "Your app should now be accessible at:"
echo "http://YOUR_EC2_PUBLIC_IP"
echo "=========================================="
