<?php
declare(strict_types=1);

namespace App;

use App\Middleware\CurrentUserMiddleware;
use App\Service\CybozuOAuthService;
use App\Service\KintoneWhoAmIService;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use App\Middleware\RolePermissionAuthorizationMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\I18n\I18n;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin('Queue');

        FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));

        $locale = Configure::read('App.defaultLocale', 'ja_JP');
        I18n::setLocale($locale);
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))
            ->add(new RoutingMiddleware($this))

            // 認証 → identity を request に付与
            ->add(new AuthenticationMiddleware($this))

            // identity から Configure(Auth.User.*) をセット（リクエスト中限定）
            ->add(new CurrentUserMiddleware())

            ->add(new RolePermissionAuthorizationMiddleware(skip: [
                // login / logout は未認証でもアクセス可能
                ['controller' => 'Users', 'actions' => ['login', 'logout']],
                // Cybozu OAuth は認証済みユーザなら常に許可
                ['controller' => 'Cybozu', 'actions' => ['connect', 'callback', 'revoke']],
                // kintone webhook は認証不要
                ['controller' => 'KintoneWebhook', 'actions' => ['receive']],
            ]))

            ->add(new BodyParserMiddleware())

            ->add(
                // kintone webhook エンドポイントは CSRF 検証をスキップする
                // CakePHP 5 では skipCheckCallback() メソッドで設定する（コンストラクタ引数では無効）
                (new CsrfProtectionMiddleware([
                    'httponly' => true,
                    'secure' => !Configure::read('debug'),
                    'samesite' => 'Lax',
                ]))->skipCheckCallback(function (ServerRequestInterface $request): bool {
                    return str_starts_with($request->getUri()->getPath(), '/webhook/');
                })
            );

        return $middlewareQueue;
    }

    public function services(ContainerInterface $container): void
    {
        // CybozuOAuthService: 設定値をコンストラクタ引数として注入
        $container->add(CybozuOAuthService::class, function () {
            $config = Configure::read('Cybozu');

            if (
                empty($config['subdomain'])
                || empty($config['oauth']['client_id'])
                || empty($config['oauth']['client_secret'])
                || empty($config['oauth']['redirect_uri'])
            ) {
                throw new \RuntimeException('Cybozu configuration is incomplete. Check app_local.php Cybozu section.');
            }

            return new CybozuOAuthService(
                subdomain:    $config['subdomain'],
                clientId:     $config['oauth']['client_id'],
                clientSecret: $config['oauth']['client_secret'],
                redirectUri:  $config['oauth']['redirect_uri'],
            );
        });

        // KintoneWhoAmIService: whoami アプリ ID を注入
        $container->add(KintoneWhoAmIService::class, function () {
            $appId = (int)Configure::read('Cybozu.apps.whoami');

            if ($appId === 0) {
                throw new \RuntimeException('Cybozu.apps.whoami is not configured. Check app_local.php.');
            }

            return new KintoneWhoAmIService(appId: $appId);
        });
    }

    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        return $eventManager;
    }

    /**
     * Authentication サービス構築
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();

        $service->setConfig([
            'unauthenticatedRedirect' => '/users/login',
            'queryParam' => 'redirect',
        ]);

        $service->loadAuthenticator('Authentication.Session');

        $service->loadAuthenticator('Authentication.Form', [
            'fields' => [
                'username' => 'username',
                'password' => 'password',
            ],
            'loginUrl' => '/users/login',

            'identifiers' => [
                'Authentication.Password' => [
                    'fields' => [
                        'username' => 'username',
                        'password' => 'password',
                    ],
                    'resolver' => [
                        'className' => 'Authentication.Orm',
                        'userModel' => 'Users',
                    ],
                ],
            ],
        ]);

        return $service;
    }
}
