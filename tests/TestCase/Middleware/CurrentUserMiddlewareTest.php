<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\CurrentUserMiddleware;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class CurrentUserMiddlewareTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('Auth');
    }

    public function testIdentityIsWrittenAndCleared(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('identity', new class {
            public function get($key)
            {
                return match ($key) {
                    'id' => 'user-1',
                    'role_id' => 'role-1',
                    default => null,
                };
            }
        });

        $middleware = new CurrentUserMiddleware();

        $handler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): Response
            {
                TestCase::assertSame('user-1', Configure::read('Auth.User.id'));
                TestCase::assertSame('role-1', Configure::read('Auth.User.role_id'));
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        $this->assertNull(Configure::read('Auth.User.id'));
        $this->assertNull(Configure::read('Auth.User.role_id'));
    }
}
