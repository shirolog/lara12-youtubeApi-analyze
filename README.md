# YouTube Channel Analyzer

Laravel と YouTube Data API v3 を使用して制作した、YouTube チャンネル分析アプリです。

## 概要

YouTube のチャンネルハンドルを入力すると、チャンネル情報や投稿動画の再生数、動画の長さなどを取得・分析できます。

## 使用技術

- PHP 8.2
- Laravel 12
- MySQL / MariaDB
- Blade
- JavaScript
- jQuery Ajax
- Chart.js
- YouTube Data API v3

## 主な機能

- YouTube チャンネルの検索（ハンドル名による検索）
- チャンネル情報の取得
- 再生数上位10本の動画を表示
- 動画の長さ別の本数を集計
- 動画の再生数・高評価数などを表示
- Chart.js による分析結果のグラフ表示
- Ajax による非同期通信

## 使用 API

YouTube Data API v3 を使用して、チャンネル情報や動画情報を取得しています。

取得した動画の再生時間は秒数に変換し、以下の区分で集計します。

- 3分未満
- 3分以上〜10分未満
- 10分以上〜30分未満
- 30分以上〜1時間未満
- 1時間以上

## セットアップ

1. リポジトリをクローンします。

   ```bash
   git clone リポジトリのURL
   ```

2. プロジェクトのディレクトリへ移動します。

   ```bash
   cd プロジェクト名
   ```

3. Composer で依存パッケージをインストールします。

   ```bash
   composer install
   ```

4. `.env.example` をコピーして `.env` を作成し、データベースなどの設定を行います。

   ```bash
   copy .env.example .env
   ```

5. アプリケーションキーを生成します。

   ```bash
   php artisan key:generate
   ```

6. `.env` に YouTube Data API のキーを設定します。

   ```env
   YOUTUBE_API_KEY=取得したAPIキー
   ```

   `config/services.php` で環境変数を読み込むように設定します。

   ```php
   'youtube' => [
       'key' => env('YOUTUBE_API_KEY'),
   ],
   ```

   サービスクラスでは `config('services.youtube.key')` から API キーを取得します。

7. 必要に応じて設定キャッシュをクリアします。

   ```bash
   php artisan config:clear
   ```

8. 開発サーバーを起動します。

   ```bash
   php artisan serve
   ```

## 環境変数について

YouTube Data API のキーは `.env` で管理してください。API キーを GitHub などの公開リポジトリに直接記述しないでください。

## 今後の改善案

- チャンネル情報の分析項目を追加
- 動画データの比較機能
- 分析結果の保存機能
