# CakePHP5 ボイラープレート

Cybozu（kintone）OAuth 連携・ロールベース権限管理を内包した業務アプリの出発点。

## 技術スタック

| 項目 | バージョン |
|------|-----------|
| PHP | 8.2+ |
| CakePHP | 5.3+ |
| DB | MySQL 8.0 |
| UI | AdminLTE 3（arodu/cakelte） |
| テスト | PHPUnit 11/12 |
| 静的解析 | PHPStan level 8 / PHPCS（CakePHP規約） |

## 主な機能

- **Cybozu OAuth 連携** — kintone のアクセストークン取得・リフレッシュを自動管理
- **kintone API クライアント** — レコード取得・更新・添付ファイルダウンロード
- **kintone Webhook キュー処理** — `dereuromark/cakephp-queue` によるキュー経由の非同期処理
- **ロールベース権限管理** — `role_permissions` テーブルで Controller/Action 単位のアクセス制御
- **UUID 主キー** — 全テーブルで UUID を採用
- **監査カラム自動付与** — `created / modified / created_by / modified_by` を全テーブルに自動セット
- **削除ガード** — 関連レコードが存在する場合の削除を自動ブロック
- **bake テンプレート** — `BoilerplateTheme` で AdminLTE 対応の CRUD を一括生成

## セットアップ

```bash
# 依存パッケージインストール
composer install

# 環境設定ファイル作成
cp config/.env.example config/.env
cp config/app_local.example.php config/app_local.php
# config/.env と config/app_local.php を編集

# DB マイグレーション
bin/cake migrations migrate

# 初期データ投入
bin/cake migrations seed
```

## 開発コマンド

```bash
# bake（BoilerplateTheme テンプレートを使用）
bin/cake bake model MyModel --theme BoilerplateTheme
bin/cake bake controller MyModel --theme BoilerplateTheme
bin/cake bake template MyModel --theme BoilerplateTheme

# コードスタイルチェック／修正
composer cs-check
composer cs-fix

# テスト
composer test

# キューワーカー起動
bin/cake queue run
```

## ディレクトリ構成

```
src/
├── Controller/          # 薄く保つ。ビジネスロジックは Service へ
│   └── Trait/           # KintoneClientTrait など共通 Trait
├── Middleware/          # 認証・権限チェック
├── Model/
│   ├── Table/           # AppTable を基底とした ORM
│   └── Behavior/        # UserTracking / DeleteGuard
├── Queue/Task/          # kintone webhook キュータスク
└── Service/
    ├── Kintone/         # kintone アプリ連携・webhook 処理
    ├── CybozuOAuthService.php
    ├── KintoneApiClient.php
    └── RolePermission*.php

config/
├── Migrations/          # Phinx マイグレーション
├── Seeds/               # 初期データ
└── routes.php

plugins/BoilerplateTheme/
└── templates/bake/      # bake テンプレート
```

## kintone 連携の追加

新しい kintone アプリ連携を追加する場合は [SKILLS.md](SKILLS.md) を参照。

```php
// config/app_local.php に Webhook プロセッサを登録
'Cybozu' => [
    'webhook' => [
        'apps' => [
            123 => ['processor' => \App\Service\Kintone\MyWebhookProcessor::class],
        ],
    ],
],
```

## CI

GitHub Actions で PHP 8.2 / 8.3 / 8.5 の3マトリクスでテストを実行。

- テスト DB: SQLite
- PHPCS + PHPStan: 別ジョブで実行

## ドキュメント

- [CLAUDE.md](CLAUDE.md) — アーキテクチャ原則・コーディング規約・DB 規約
- [SKILLS.md](SKILLS.md) — 操作手順・コマンドリファレンス
