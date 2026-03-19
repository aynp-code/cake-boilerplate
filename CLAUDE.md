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
├── Queue/
│   └── Task/
│       └── KintoneWebhookTask.php     # kintone webhook キュー処理タスク
└── Service/
    ├── CybozuOAuthService.php          # OAuth トークンのライフサイクル管理
    ├── KintoneApiClient.php            # kintone REST API クライアント
    ├── KintoneApiClientInterface.php   # クライアントのインターフェース
    ├── Kintone/
    │   ├── AbstractKintoneAppService.php         # kintone アプリ操作の基底クラス
    │   ├── AbstractKintoneWebhookProcessor.php   # webhook 処理の基底クラス
    │   ├── SampleKintoneService.php              # サンプル実装（新規アプリはこれを参考に）
    │   └── SampleKintoneWebhookProcessor.php     # webhook 処理サンプル実装
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
- `beforeDelete()` は CakePHP 5.2+ で戻り値禁止。`$event->stopPropagation()` で中止し `void` を返す

### kintone Webhook キュー処理
- `KintoneWebhookController` が webhook を受け取り `dereuromark/cakephp-queue` でキューに積む
- `KintoneWebhookTask` がキューを処理し、設定（`Cybozu.webhook.apps.{appId}.processor`）で指定したプロセッサを呼ぶ
- 新しい kintone アプリの webhook を処理する場合は `AbstractKintoneWebhookProcessor` を継承したクラスを作成し、`config/app_local.php` に登録する

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

---

## kintone 連携パターン

新しいkintoneアプリ連携を追加する場合は [SKILLS.md](SKILLS.md) の「kintone 連携パターン」を参照。

- `AbstractKintoneAppService` を継承した Service クラスを `src/Service/Kintone/` に作成
- Controller では `KintoneClientTrait` を `use` して `$client` を取得
- Webhook プロセッサは `AbstractKintoneWebhookProcessor` を継承し `config/app_local.php` に登録

---

## コーディング規約

- PSR-12 + CakePHP コーディング規約（phpcs.xml 参照）
- 型宣言は PHPDoc より native type hint を優先
- `declare(strict_types=1)` はすべてのファイルに必須
- PHPStan level 8 を維持（`@phpstan-ignore` は原則禁止）
- メソッドの戻り値型・引数型は必ず明示する
- 全 public/protected メソッドに PHPDoc コメントが必要（PHPCS 要件）
- 配列の値をアライメントするスペース（`$a      = 1`）は不可。スペース1つのみ

### PHPStan ジェネリック型の記述ルール

CakePHP の型引数は以下のルールに従う（`mixed` は不可）：

| 型 | 正しい記述 |
|---|---|
| `EventInterface` (Controller内) | `EventInterface<\Cake\Controller\Controller>` |
| `EventInterface` (Table/Behavior内) | `EventInterface<\Cake\ORM\Table>` |
| `ArrayObject` | `ArrayObject<array-key, mixed>` |
| `SelectQuery` | `SelectQuery<\Cake\Datasource\EntityInterface>` |
| `ResultSetInterface` | `ResultSetInterface<int, EntityClass>` |

Controller アクションの引数は `mixed` 不可。UUID等は `string $id` と明示する。

---

## DB 規約

- 主キーは UUID（`'id' => false, 'primary_key' => ['id']`）
- テーブル名は snake_case 複数形
- 全テーブルに `created / created_by / modified / modified_by` を含める
- マイグレーションは `config/Migrations/` に追加（`bin/cake migrations migrate` で適用）
- 論理削除は使わず `is_active` フラグで対応（必要な場合）

### マイグレーション記述ルール

- 基底クラスは `Migrations\BaseMigration`（`AbstractMigration` は非推奨）
- `addIndex()` の名前キーは **`'name'`**
- `addForeignKey()` の制約名キーは **`'constraint'`**（両者は異なるので混同しない）

```php
$this->table('users')
    ->addIndex(['role_id'], ['name' => 'IDX_USERS_ROLE_ID'])          // インデックスは 'name'
    ->addForeignKey('role_id', 'roles', 'id', [
        'constraint' => 'FK_USERS_ROLES',                             // FKは 'constraint'
        'update' => 'CASCADE',
        'delete' => 'RESTRICT',
    ])
    ->update();
```

---

## やってはいけないこと

- Controller にビジネスロジックを書かない
- 生SQL禁止（CakePHP ORM を使う）
- `TableRegistry::getTableLocator()->get()` を Controller/Service で直接使わない（`fetchTable()` を使う）
- `allowFallbackClass(false)` が設定されているため、存在しない Table クラスの暗黙生成は不可
- `@phpstan-ignore` でエラーを握りつぶさない
- `role_permissions` の変更後にキャッシュ削除を忘れない
- `beforeDelete()` で `return false` しない（CakePHP 5.2+ で非推奨。`$event->stopPropagation()` を使う）
- Migration で `addIndex()` のオプションに `'constraint'` を使わない（`'name'` が正しい）
- Migration の基底クラスに `AbstractMigration` を使わない（`BaseMigration` を使う）

---

## よく使うコマンドと手順

詳細は [SKILLS.md](SKILLS.md) を参照。

```bash
bin/cake migrations migrate          # マイグレーション実行
bin/cake bake model MyModel --theme BoilerplateTheme
bin/cake bake controller MyModel --theme BoilerplateTheme
bin/cake bake template MyModel --theme BoilerplateTheme
composer cs-check && composer cs-fix # コードスタイル
composer test                        # テスト
bin/cake queue run                   # キューワーカー起動
```

---

## 環境設定

- 環境変数は `config/.env`（`config/.env.example` を参考に作成）
- Cybozu 連携設定は `config/app_local.php` の `Cybozu` セクションに記載
- AdminLTE のテーマ設定は `config/cakelte.php`

---

## CI（GitHub Actions）

### CI の構成
- `.github/workflows/ci.yml` にて PHP 8.2/lowest・8.3/locked・8.5/highest の3マトリクスでテスト
- テスト DB は SQLite（`DATABASE_TEST_URL: sqlite://./testdb.sqlite`）
- PHPCS + PHPStan は別ジョブ（`coding-standard`）で実行

### テスト環境での注意点

**キャッシュ（Redis）**
- CI には Redis がないため `tests/bootstrap.php` で全キャッシュエンジンを FileEngine に上書き済み
- `app_local.php`（`app_local.example.php` のコピー）に Redis 設定が含まれていても無効化される
- キャッシュ上書きには `Cache::drop()` + `Cache::setConfig()` を使う（`Configure::write()` は登録済みエンジンに効かない）

**PHPUnit 設定**
- `phpunit.xml.dist` に `apc.enable_cli=1` を書かない（PHPUnit warning でexit code 1になる）

**PHPStan**
- CI は `phpstan:1.12` をシステムツールとしてインストールして実行（`vendor/bin/phpstan` ではない）
- ローカルで確認する場合は `composer require --dev phpstan/phpstan` で一時インストールして確認後 `composer remove --dev phpstan/phpstan` で削除
