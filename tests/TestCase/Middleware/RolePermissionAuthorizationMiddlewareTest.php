<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\RolePermissionAuthorizationMiddleware;
use App\Service\RolePermissionCheckerInterface;
use App\Service\RoutePermissionTargetNormalizer;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RolePermissionAuthorizationMiddlewareTest extends TestCase
{
    private function makeRequest(string $controller, string $action, ?object $identity = null): ServerRequest
    {
        $request = new ServerRequest([
            'params' => [
                'controller' => $controller,
                'action' => $action,
            ],
        ]);

        if ($identity !== null) {
            $request = $request->withAttribute('identity', $identity);
        }

        return $request;
    }

    private function makeIdentityWithRoleId(?string $roleId): object
    {
        return new class($roleId) {
            public function __construct(private ?string $roleId) {}
            public function get($key)
            {
                return $key === 'role_id' ? $this->roleId : null;
            }
        };
    }

    private function makeHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): Response
            {
                return new Response();
            }
        };
    }

    public function testAllowsAccessWhenCheckerReturnsTrue(): void
    {
        $request = $this->makeRequest(
            'Users',
            'index',
            $this->makeIdentityWithRoleId('role-1')
        );

        $checker = new class implements RolePermissionCheckerInterface {
            public array $lastTarget = [];
            public ?string $lastRoleId = null;

            public function invalidateRole(string $roleId): void {}

            public function can(string $roleId, array $target): bool
            {
                $this->lastRoleId = $roleId;
                $this->lastTarget = $target;
                return true;
            }
        };

        $middleware = new RolePermissionAuthorizationMiddleware($checker);
        $response = $middleware->process($request, $this->makeHandler());

        $this->assertInstanceOf(Response::class, $response);

        // ついでに最低限、targetに controller/action が入ってることだけ確認
        $this->assertSame('role-1', $checker->lastRoleId);
        $this->assertSame('Users', $checker->lastTarget['controller'] ?? null);
        $this->assertSame('index', $checker->lastTarget['action'] ?? null);
    }

    public function testThrowsForbiddenWhenCheckerReturnsFalse(): void
    {
        $this->expectException(ForbiddenException::class);

        $request = $this->makeRequest(
            'Users',
            'index',
            $this->makeIdentityWithRoleId('role-1')
        );

        $checker = new class implements RolePermissionCheckerInterface {
            public function invalidateRole(string $roleId): void {}

            public function can(string $roleId, array $target): bool
            {
                return false;
            }
        };

        $middleware = new RolePermissionAuthorizationMiddleware($checker);
        $middleware->process($request, $this->makeHandler());
    }

    public function testPassThroughWhenNoIdentity(): void
    {
        $request = $this->makeRequest('Users', 'index', null);

        $checker = new class implements RolePermissionCheckerInterface {
            public bool $called = false;
            public function invalidateRole(string $roleId): void {}

            public function can(string $roleId, array $target): bool
            {
                $this->called = true;
                return false;
            }
        };

        $middleware = new RolePermissionAuthorizationMiddleware($checker);
        $response = $middleware->process($request, $this->makeHandler());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($checker->called, 'Checker should not be called when identity is missing.');
    }

    public function testPassThroughForUsersLogin(): void
    {
        $request = $this->makeRequest('Users', 'login', null);

        $checker = new class implements RolePermissionCheckerInterface {
            public bool $called = false;
            public function invalidateRole(string $roleId): void {}

            public function can(string $roleId, array $target): bool
            {
                $this->called = true;
                return false;
            }
        };

        $middleware = new RolePermissionAuthorizationMiddleware($checker);
        $response = $middleware->process($request, $this->makeHandler());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($checker->called);
    }

    public function testPassThroughBySkipRule(): void
    {
        $request = $this->makeRequest(
            'Users',
            'index',
            $this->makeIdentityWithRoleId('role-1')
        );

        $checker = new class implements RolePermissionCheckerInterface {
            public bool $called = false;
            public function invalidateRole(string $roleId): void {}

            public function can(string $roleId, array $target): bool
            {
                $this->called = true;
                return false;
            }
        };

        $skip = [
            ['controller' => 'Users', 'actions' => ['index']],
        ];

        $middleware = new RolePermissionAuthorizationMiddleware($checker, new RoutePermissionTargetNormalizer(), $skip);
        $response = $middleware->process($request, $this->makeHandler());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($checker->called, 'Checker should not be called when request is skipped.');
    }
}
