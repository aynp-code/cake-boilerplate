<?php
/*
 * app.php の設定を上書きするためのローカル設定ファイルです。
 * このファイルを app_local.php としてコピー・保存し、必要に応じて変更してください。
 * 注意: app_local.php のように認証情報（資格情報）を含むファイルを、
 * ソースコードのバージョン管理にコミットすることは推奨されていません。
 */
return [
    /*
    * デバッグレベル:
    *
    * 本番モード:
    * false: エラーメッセージ、エラー、警告は一切表示されません。
    *
    * 開発モード:
    * true: エラーおよび警告が表示されます。
    */
    'debug' => True,

    /*
    * アプリケーション共通設定
    *
    * - defaultLocale
    *   アプリケーション全体で使用するデフォルトのロケールを指定します。
    *   日付・時刻・数値・言語表記などの地域設定に影響します。
    */
    'App' => [
        'defaultLocale' => 'ja_JP',
        'defaultTimezone' => 'Asia/Tokyo',
    ],
    
    /*
    * DebugKit 設定
    *
    * - ignoreAuthorization
    *   true に設定すると、認可（Authorization）チェックを無視して
    *   DebugKit のパネルを表示します。
    */
    'DebugKit' => [
        'ignoreAuthorization' => true
    ],
    
    /*
    * セキュリティおよび暗号化に関する設定
    *
    * - salt - セキュリティ用のハッシュ処理で使用されるランダムな文字列です。
    *   この salt の値は暗号化キーとしても使用されます。
    *   そのため、極めて機密性の高いデータとして扱う必要があります。
    */
    'Security' => [
        'salt' => '',
    ],

    /*
    * データベース設定（マスター／レプリカ）
    *
    * この設定は、アプリケーションで使用するデフォルトのデータソースと、
    * マスターおよびレプリカの役割別接続情報を定義します。
    *
    * - default
    *   アプリケーションで使用される基本のデータソース設定です。
    *
    * - roles
    *   - master
    *     書き込み処理（INSERT / UPDATE / DELETE）に使用される接続です。
    *
    *   - replica
    *     読み取り処理（SELECT）に使用される接続です。
    *
    * 各役割ごとにホストや認証情報を分けることで、
    * 読み書き分離やスケーラビリティの向上が可能になります。
    */
    'Datasources' => [
        'default' => [
            'host' => 'master_db',
            'username' => 'master_user',
            'password' => 'master_password',
            'database' => 'master_database',
            'roles' => [
                'master' => [
                    'host' => 'master_db',
                    'username' => 'master_user',
                    'password' => 'master_password',
                    'database' => 'master_database',
                ],
                'replica' => [
                    'host' => 'replica_db',
                    'username' => 'replica_user',
                    'password' => 'replica_password',
                    'database' => 'replica_database',
                ],
            ],
        ],
        'test' => [
            'host' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_password',
            'database' => 'test_database',
        ],
    ],

    /*
    * メール設定
    *
    * SmtpTransport を使用する場合の、
    * ホストおよび認証情報に関する設定です。
    *
    * その他の設定項目については app.php を参照してください。
    */
    'EmailTransport' => [
        'default' => [
            'className' => 'Smtp',
            'host' => env('SMTP_HOST', 'localhost'),
            'port' => env('SMTP_PORT', 25),
            'username' => env('SMTP_USER', 'username'),
            'password' => env('SMTP_PSWD', 'password'),
            'timeout' => 30,
            'tls' => filter_var(env('SMTP_TLS', true), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

    /*
    * キャッシュ設定
    *
    * Redis をキャッシュエンジンとして使用する設定です。
    * キャッシュの用途ごとにデータベース番号や有効期限を分けて定義しています。
    *
    * - default
    *   アプリケーション全体で使用されるデフォルトのキャッシュ設定です。
    *   一時的なデータや汎用キャッシュの保存に使用されます。
    *
    * - _cake_model_
    *   CakePHP のモデルキャッシュ用の設定です。
    *   スキーマ情報やメタデータなど、比較的寿命の長いデータを保存します。
    *
    * 各設定では、Redis のデータベース番号やキーのプレフィックスを分けることで、
    * キャッシュの衝突を防ぎ、用途別に管理できるようにしています。
    */
    'Cache' => [
        'default' => [
            'className' => \Cake\Cache\Engine\RedisEngine::class,
            'server' => 'redis_host',
            'port' => 6379,
            'database' => 0,
            'prefix' => 'cake_',
            'duration' => '+1 hours',
        ],
        '_cake_model_' => [
            'className' => \Cake\Cache\Engine\RedisEngine::class,
            'server' => 'redis_host',
            'port' => 6379,
            'database' => 1,
            'prefix' => 'cake_model_',
            'duration' => '+1 days',
        ],
        '_cake_core_' => [
            'className' => \Cake\Cache\Engine\RedisEngine::class,
            'server' => 'redis_host',
            'port' => 6379,
            'database' => 2,
            'prefix' => 'cake_core_',
            'duration' => '+1 days',
        ],
        '_cake_routes_' => [
            'className' => \Cake\Cache\Engine\RedisEngine::class,
            'server' => 'redis_host',
            'port' => 6379,
            'database' => 2,
            'prefix' => 'cake_routes_',
            'duration' => '+1 days',
        ],
        '_cake_permissions' => [
            'className' => \Cake\Cache\Engine\RedisEngine::class,
            'server' => 'redis_host',
            'port' => 6379,
            'database' => 0,
            'prefix' => 'cake_permissions_',
            'duration' => '+1 year',
        ],
    ],
];
