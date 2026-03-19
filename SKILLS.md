# SKILLS.md

よく使う操作手順・コマンドリファレンス。
アーキテクチャや規約は [CLAUDE.md](CLAUDE.md) を参照。

---

## よく使うコマンド

```bash
# マイグレーション
bin/cake migrations migrate

# bake（BoilerplateTheme テンプレートを使用）
bin/cake bake controller MyModel --theme BoilerplateTheme
bin/cake bake model MyModel --theme BoilerplateTheme
bin/cake bake template MyModel --theme BoilerplateTheme

# 静的解析
vendor/bin/phpstan analyse

# コードスタイルチェック／修正
composer cs-check
composer cs-fix

# テスト
composer test

# キュー処理（dereuromark/cakephp-queue）
bin/cake queue run               # キューワーカー起動
bin/cake queue info              # キューの状態確認
```

---

## 新機能追加の標準フロー

1. `config/Migrations/` にマイグレーションファイル追加
2. `bin/cake migrations migrate` 実行
3. `bin/cake bake model` で Table / Entity 生成（`--theme BoilerplateTheme`）
4. `src/Service/` にビジネスロジッククラス作成
5. `bin/cake bake controller` で Controller 生成（`--theme BoilerplateTheme`）
6. `bin/cake bake template` でテンプレート生成（`--theme BoilerplateTheme`）
7. `config/routes.php` にルート追加
8. `role_permissions` に新コントローラのアクション権限を登録
9. PHPStan・PHPCS がパスすることを確認

---

## kintone 連携パターン（新規アプリ追加）

```php
// 1. src/Service/Kintone/MyAppService.php を作成（AbstractKintoneAppService を継承）
// 2. Controller では KintoneClientTrait を use して $client を取得
// 3. Application::services() で必要なら DI コンテナに登録

// 標準パターン
$client = $this->cybozuOAuthService->makeKintoneClient($userId);
$myAppService = new MyKintoneAppService($appId);
$result = $myAppService->doSomething($client, ...);
```

---

## kintone Webhook プロセッサ追加手順

1. `src/Service/Kintone/MyWebhookProcessor.php` を作成
   - `AbstractKintoneWebhookProcessor` を継承
   - `SampleKintoneWebhookProcessor` を参考に実装

2. `config/app_local.php` の `Cybozu.webhook.apps` に登録

```php
// config/app_local.php
'Cybozu' => [
    'webhook' => [
        'apps' => [
            123 => ['processor' => \App\Service\Kintone\MyWebhookProcessor::class],
        ],
    ],
],
```

3. 必要なら DB テーブルをマイグレーションで追加し、プロセッサ内で保存処理を実装

---

## 権限登録手順

新しいコントローラ／アクションを追加した後：

1. 管理画面の「ロール権限設定」から対象ロールにアクションを追加、または
2. Seeds で直接 `role_permissions` テーブルに INSERT

```bash
# Seeds 再実行
bin/cake migrations seed --seed RolePermissionsSeed
```

3. キャッシュを削除して即時反映

```php
// コードから実行する場合
\App\Service\RolePermissionChecker::invalidateRole($roleId);
```

---

## PHPStan / PHPCS ローカル確認

```bash
# PHPCS チェック（エラー一覧）
composer cs-check

# PHPCS 自動修正
composer cs-fix

# PHPStan（vendor に入っていない場合）
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse
composer remove --dev phpstan/phpstan
```

---

## テスト実行

```bash
# 全テスト
composer test

# 特定のテストファイルのみ
vendor/bin/phpunit tests/TestCase/Model/Table/UsersTableTest.php

# 特定のテストメソッドのみ
vendor/bin/phpunit --filter testSomeMethod tests/TestCase/...
```
