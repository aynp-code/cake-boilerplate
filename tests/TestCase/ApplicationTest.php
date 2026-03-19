<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Test\TestCase;

use App\Application;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * ApplicationTest class
 */
class ApplicationTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test bootstrap in production.
     *
     * @return void
     */
    public function testBootstrap()
    {
        Configure::write('debug', false);
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $plugins = $app->getPlugins();

        $this->assertTrue($plugins->has('Bake'), 'plugins has Bake?');
        $this->assertFalse($plugins->has('DebugKit'), 'plugins has DebugKit?');
        $this->assertTrue($plugins->has('Migrations'), 'plugins has Migrations?');
    }

    /**
     * Test bootstrap add DebugKit plugin in debug mode.
     *
     * @return void
     */
    public function testBootstrapInDebug()
    {
        Configure::write('debug', true);
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $plugins = $app->getPlugins();

        $this->assertTrue($plugins->has('DebugKit'), 'plugins has DebugKit?');
    }

    /**
     * testMiddleware
     *
     * @return void
     */
    public function testMiddleware()
    {
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $middleware = $app->middleware(new MiddlewareQueue());

        // MiddlewareQueue is iterable. Collect into a list so we can assert
        // presence and relative order without depending on exact indexes,
        // as projects may insert additional middleware (eg. Authentication).
        $stack = [];
        foreach ($middleware as $m) {
            $stack[] = $m;
        }

        $this->assertNotEmpty($stack);

        $classes = array_map(static fn($m) => get_class($m), $stack);

        $errorIndex = array_search(ErrorHandlerMiddleware::class, $classes, true);
        $assetIndex = array_search(AssetMiddleware::class, $classes, true);
        $routingIndex = array_search(RoutingMiddleware::class, $classes, true);

        $this->assertNotFalse($errorIndex, 'ErrorHandlerMiddleware should be in the middleware queue.');
        $this->assertNotFalse($assetIndex, 'AssetMiddleware should be in the middleware queue.');
        $this->assertNotFalse($routingIndex, 'RoutingMiddleware should be in the middleware queue.');

        $this->assertLessThan($assetIndex, $errorIndex, 'ErrorHandler should run before Asset.');
        $this->assertLessThan($routingIndex, $assetIndex, 'Asset should run before Routing.');

        $bodyIndex = array_search(BodyParserMiddleware::class, $classes, true);
        if ($bodyIndex !== false) {
            $this->assertGreaterThan($routingIndex, $bodyIndex, 'BodyParser should run after Routing.');
        }

        $csrfIndex = array_search(CsrfProtectionMiddleware::class, $classes, true);
        $this->assertNotFalse($csrfIndex, 'CsrfProtectionMiddleware should be in the middleware queue.');
        $this->assertGreaterThan($routingIndex, $csrfIndex, 'CSRF should run after Routing.');
        if ($bodyIndex !== false) {
            $this->assertLessThan($csrfIndex, $bodyIndex, 'BodyParser should run before CSRF.');
        }

        // Authentication is optional. If the plugin is installed, ensure the
        // middleware is in the queue and runs before CSRF.
        if (class_exists(AuthenticationMiddleware::class)) {
            $authIndex = array_search(AuthenticationMiddleware::class, $classes, true);
            $this->assertNotFalse(
                $authIndex,
                'AuthenticationMiddleware should be in the middleware queue when installed.',
            );
            $this->assertGreaterThan($routingIndex, $authIndex, 'Authentication should run after Routing.');
            $this->assertGreaterThan($authIndex, $csrfIndex, 'CSRF should run after Authentication.');
        }
    }
}
