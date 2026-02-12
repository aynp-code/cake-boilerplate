<?php
declare(strict_types=1);

namespace App;

use App\Middleware\CurrentUserMiddleware;
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

            ->add(new RolePermissionAuthorizationMiddleware())

            ->add(new BodyParserMiddleware())

            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
                'secure' => !Configure::read('debug'),
                'samesite' => 'Lax',
            ]));

        return $middlewareQueue;
    }

    public function services(ContainerInterface $container): void
    {
        // $container->delegate(new \Cake\ORM\Locator\TableContainer());
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

        // Identifier（DB照合）
        $service->loadIdentifier('Authentication.Password', [
            'fields' => [
                'username' => 'username',
                'password' => 'password',
            ],
            'resolver' => [
                'className' => 'Authentication.Orm',
                'userModel' => 'Users',
            ],
        ]);

        // Session
        $service->loadAuthenticator('Authentication.Session');

        // Form
        $service->loadAuthenticator('Authentication.Form', [
            'fields' => [
                'username' => 'username',
                'password' => 'password',
            ],
            'loginUrl' => '/users/login',
        ]);

        return $service;
    }
}
