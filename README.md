# Playio

ゲームの感想やプレイ記録を共有できるゲーム特化型SNSです。

## 概要

ゲームごとに投稿を検索したり、
スクリーンショットやプレイ記録を投稿できるSNSです。
IGDB APIを利用してゲーム情報を検索できます。

ホーム画面
<img width="3839" height="2159" alt="スクリーンショット 2026-07-12 184044" src="https://github.com/user-attachments/assets/57ad11d6-f125-4088-98aa-7b405b1adf09" />

投稿画面
<img width="3839" height="2159" alt="スクリーンショット 2026-07-12 185206" src="https://github.com/user-attachments/assets/4ad373a1-5bf6-451b-a967-bb99b3a35b0f" />
## 主な機能

- ユーザー登録・ログイン
- ゲーム検索
- 投稿機能
- 画像・動画投稿
- タグ機能
- いいね機能
- お気に入りゲーム登録、画像で共有
- ゲーム別投稿検索
- カテゴリ検索
- ハイスコア投稿
- タイムアタック記録投稿
- ネタバレ投稿設定
- 投稿並び替え
- 発売ゲームカレンダー

## 使用技術

- PHP
- JavaScript
- HTML
- CSS
- MySQL / MariaDB
- IGDB API

## 開発環境

- XAMPP
- Apache
- MariaDB

## セットアップ

1. リポジトリをクローン
2. `sql/schema.sql` をデータベースへインポート
3. `config/db.example.php` を `config/db.php` にコピー
4. DB接続情報を設定
5. `config/env.example.php` を `config/env.php` にコピー
6. IGDB APIのClient IDとClient Secretを設定

## 工夫した点

ゲームSNSとして、通常の投稿だけでなく、
ハイスコアやタイムアタックの記録を投稿できるようにしました。

また、ゲーム情報はIGDB APIから取得し、
ゲームごとに投稿を検索できるようにしています。

## 今後追加したい機能

- 通知機能
- ユーザーフォロー
- 投稿編集
- プロフィール編集
