<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\RolePermissionChecker;
use App\Service\RolePermissionCheckerInterface;
use App\Service\RoutePermissionTargetNormalizer;
use Cake\Http\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RolePermissionAuthorizationMiddleware implements MiddlewareInterface
{
    /**
     * スキップルールの型定義:
     * [
     *   ['controller' => 'Users', 'actions' => ['login', 'logout']],
     *   ['controller' => 'Cybozu'],  // actions 省略 = 全アクション
     * ]
     *
     * @var array<int, array{controller:string, actions?:array<int,string>}>
     */
    private array $skip;

    /**
     * @param array<int, array{controller:string, actions?:array<int,string>}> $skip
     *   スキップするコントローラ／アクションのルール。
     *   Application::middleware() から設定として渡すことで OCP に準拠し、
     *   ミドルウェア本体を修正せずに例外を追加できる。
     */
    public function __construct(
        private readonly RolePermissionCheckerInterface $checker = new RolePermissionChecker(),
        private readonly RoutePermissionTargetNormalizer $normalizer = new RoutePermissionTargetNormalizer(),
        array $skip = [],
    ) {
        $this->skip = $skip;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!method_exists($request, 'getParam')) {
            return $handler->handle($request);
        }

        $plugin = $request->getParam('plugin');
        if ($plugin === 'DebugKit') {
            return $handler->handle($request);
        }

        /** @var mixed $controller */
        $controller = $request->getParam('controller');
        /** @var mixed $action */
        $action = $request->getParam('action');

        if (!is_string($controller) || $controller === '' || !is_string($action) || $action === '') {
            return $handler->handle($request);
        }

        if ($controller === 'Error') {
            return $handler->handle($request);
        }

        // スキップルールを評価（ハードコードを廃止し、全てここで処理する）
        foreach ($this->skip as $rule) {
            if (($rule['controller'] ?? null) !== $controller) {
                continue;
            }
            $actions = $rule['actions'] ?? null;
            if ($actions === null || in_array($action, $actions, true)) {
                return $handler->handle($request);
            }
        }

        $identity = $request->getAttribute('identity');
        if (!$identity) {
            return $handler->handle($request);
        }

        $roleId = null;
        if (method_exists($identity, 'get')) {
            $roleId = $identity->get('role_id');
        }

        if (!is_string($roleId) || $roleId === '') {
            throw new ForbiddenException('Role is not assigned.');
        }

        $allowed = $this->checker->can($roleId, [
            'plugin'     => $this->normalizer->normalizePlugin($request->getParam('plugin')),
            'prefix'     => $this->normalizer->normalizePrefix($request->getParam('prefix')),
            'controller' => $controller,
            'action'     => $action,
        ]);

        if (!$allowed) {
            throw new ForbiddenException('You are not allowed to access this resource.');
        }

        return $handler->handle($request);
    }
}
