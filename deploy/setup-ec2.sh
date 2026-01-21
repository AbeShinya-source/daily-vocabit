#!/bin/bash
# EC2 Ubuntu 22.04 LTS セットアップスクリプト
# 使用方法: sudo bash setup-ec2.sh

set -e

echo "=========================================="
echo "TOEIC Daily - EC2 Setup Script"
echo "=========================================="

# システムアップデート
echo ">>> Updating system packages..."
apt update && apt upgrade -y

# 必要なパッケージをインストール
echo ">>> Installing required packages..."
apt install -y nginx php8.1-fpm php8.1-cli php8.1-mbstring php8.1-xml php8.1-curl php8.1-sqlite3 php8.1-zip unzip git curl

# Composerインストール
echo ">>> Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Node.js 20.x インストール
echo ">>> Installing Node.js 20.x..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# アプリケーションディレクトリ作成
echo ">>> Creating application directory..."
mkdir -p /var/www/toeic-daily
chown -R www-data:www-data /var/www/toeic-daily

echo "=========================================="
echo "Setup complete!"
echo ""
echo "Next steps:"
echo "1. Clone your repository to /var/www/toeic-daily"
echo "2. Run: sudo bash /var/www/toeic-daily/deploy/deploy.sh"
echo "=========================================="
