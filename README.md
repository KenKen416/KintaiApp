# 勤怠管理アプリ 『KintaiApp』

本リポジトリは Laravel ベースのシンプルな勤怠管理アプリです。
主な目的は「打刻（出勤/休憩/退勤）」「勤怠一覧」「勤怠の修正申請・承認（ユーザー ⇄ 管理者）」の実装と、それに伴うバリデーション／テストの整備です。

---

##　環境構築

### 開発環境立ち上げ

  1.リポジトリをクローン<br>

- git clone git@github.com:KenKen416/KintaiApp.git

  2.ディレクトリ移動

- cd KintaiApp

  3.コマンド実行

- make init

### テスト環境

PHPUnit を利用したテストが可能です。

1.テスト用データベースの作成

- docker-compose exec mysql bash
- mysql -u root -p
  （補足）パスワードは root と入力
- create database demo_test;

  2.テスト実行

- docker compose exec php php artisan test

## ログイン情報

### 管理者アカウント

email: admin@admin
password: password

### 一般ユーザー

email: test1@test
password: password

---

email: test2@test
password: password

---

email: test3@test
password: password

---

email: test4@test
password: password

---

email: test5@test
password: password

---

## 使用技術

laravel 8.6.12
php 8.2
nginx:1.21.1
mysql:8.0.29
mailhog

勤怠一覧で、詳細に飛ばすのは勤務があったものだけにすることを、コーチと会話したことを明記する。
