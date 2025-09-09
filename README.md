# KintaiApp

git clone <repo-url>
cd KintaiApp
cp .env.example .env
docker compose up -d (または php artisan serve を使うならその方針)
composer install
php artisan key:generate
php artisan migrate --seed (後で追加)
npm install && npm run dev (後で UI 入れるなら) 管理者ログイン: （後で記載） 一般ユーザーログイン: （後で記載）




laravel 8.6.12
php 8.2
nginx:1.21.1
mysql:8.0.29