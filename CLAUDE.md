# CLAUDE.md

## プロジェクト概要

CakePHP5 ボイラープレート。
Cybozu（kintone）OAuth 連携・ロールベース権限管理を内包した業務アプリの出発点。

- **プロジェクトルート**: `/var/dockers/cake-docker/cake`
- **フレームワーク**: CakePHP 5.3+
- **PHP**: 8.2+（`declare(strict_types=1)` 必須）
- **DB**: MySQL 8.0（主キーは UUID）
- **UI**: AdminLTE 3（arodu/cakelte プラグイン経由）
- **テスト**: PHPUnit 11/12
- **静的解析**: PHPStan level 8 / PHPCS（CakePHP規約）

---

## ディレクトリ構成と役割

```
src/
├── Controller/
│   ├── AppController.php       # Flash・Authentication ロード。レイアウト設定のみ
│   ├── Trait/KintoneClientTrait.php  # kintone トークン取得の共通処理
│   └── *.php                   # 薄く保つ。ビジネスロジックは Service へ
├── Middleware/
│   ├── CurrentUserMiddleware.php             # identity → Configure(Auth.User.*) にセット
│   └── RolePermissionAuthorizationMiddleware.php  # ロール権限チェック（スキップ設定は Application.php）
├── Model/
│   ├── Table/
│   │   └── AppTable.php        # 全Tableの基底。Timestamp・UserTracking・DeleteGuard を自動付与
│   ├── Entity/
│   └── Behavior/
│       ├── UserTrackingBehavior.php   # created_by / modified_by の自動補完
│       └── DeleteGuardBehavior.php    # 関連レコードがある場合の削除ガード
└── Service/
    ├── CybozuOAuthService.php          # OAuth トークンのライフサイクル管理
    ├── KintoneApiClient.php            # kintone REST API クライアント
    ├── KintoneApiClientInterface.php   # クライアントのインターフェース
    ├── Kintone/
    │   ├── AbstractKintoneAppService.php  # kintone アプリ操作の基底クラス
    │   └── SampleKintoneService.php       # サンプル実装（新規アプリはこれを参考に）
    ├── RolePermissionChecker.php       # role_id + controller/action → 可否判定（キャッシュあり）
    ├── RolePermissionCheckerInterface.php
    ├── RolePermissionMatrixService.php # 権限マトリクスのCRUD
    ├── RoutePermissionTargetNormalizer.php  # plugin/prefix の正規化
    ├── ControllerActionCatalog.php     # 利用可能なコントローラ/アクション一覧
    └── ViewAuthorization.php           # テンプレート内の権限チェック用

config/
├── Migrations/                 # Phinx マイグレーション（主キーUUID）
├── Seeds/                      # 初期データ投入
├── routes.php                  # DashedRoute。fallbacks() は最後
└── cakelte.php                 # AdminLTE のテーマ設定

plugins/BoilerplateTheme/
└── templates/bake/             # bake テンプレート（Controller・Table・各View）
```

---

## アーキテクチャ原則

### Controller は薄く保つ
- リクエスト受取・バリデーション呼び出し・レスポンス返却のみ
- ビジネスロジックは `src/Service/` へ切り出す
- DB操作は `Model/Table/` に閉じる

### 権限制御の仕組み
- `RolePermissionAuthorizationMiddleware` がリクエストごとに `role_permissions` テーブルを参照
- `role_id` はセッション（`Configure::read('Auth.User.role_id')`）から取得
- スキップルール（login/logout/Cybozuコールバック）は `Application::middleware()` に集約
- 権限マップは `_cake_permissions` キャッシュに保存。変更後は `RolePermissionChecker::invalidateRole()` で削除

### 監査カラム（全テーブル共通）
すべてのテーブルは `AppTable` を継承し以下を自動付与：
- `created` / `modified` … Timestamp Behavior
- `created_by` / `modified_by` … UserTracking Behavior（`Configure::read('Auth.User.id')` から取得）

### 削除ガード
- 各Tableに `restrictDeleteAssociations(): array` を実装すると `DeleteGuardBehavior` が有効化
- 関連レコードが存在する場合は削除を中止しエラーをセット

---

## kintone 連携パターン

新しいkintoneアプリ連携を追加する場合：

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

## コーディング規約

- PSR-12 + CakePHP コーディング規約（phpcs.xml 参照）
- 型宣言は PHPDoc より native type hint を優先
- `declare(strict_types=1)` はすべてのファイルに必須
- PHPStan level 8 を維持（`@phpstan-ignore` は原則禁止）
- メソッドの戻り値型・引数型は必ず明示する

---

## DB 規約

- 主キーは UUID（`'id' => false, 'primary_key' => ['id']`）
- テーブル名は snake_case 複数形
- 全テーブルに `created / created_by / modified / modified_by` を含める
- マイグレーションは `config/Migrations/` に追加（`bin/cake migrations migrate` で適用）
- 論理削除は使わず `is_active` フラグで対応（必要な場合）

---

## やってはいけないこと

- Controller にビジネスロジックを書かない
- 生SQL禁止（CakePHP ORM を使う）
- `TableRegistry::getTableLocator()->get()` を Controller/Service で直接使わない（`fetchTable()` を使う）
- `allowFallbackClass(false)` が設定されているため、存在しない Table クラスの暗黙生成は不可
- `@phpstan-ignore` でエラーを握りつぶさない
- `role_permissions` の変更後にキャッシュ削除を忘れない

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

## 環境設定

- 環境変数は `config/.env`（`config/.env.example` を参考に作成）
- Cybozu 連携設定は `config/app_local.php` の `Cybozu` セクションに記載
- AdminLTE のテーマ設定は `config/cakelte.php`
