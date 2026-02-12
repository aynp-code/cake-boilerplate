<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\RolePermissionChecker;
use App\Service\RoutePermissionTargetNormalizer;
use Cake\Http\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RolePermissionAuthorizationMiddleware implements MiddlewareInterface
{
    private RoutePermissionTargetNormalizer $normalizer;

    /**
     * @param array<int, array{controller:string, actions?:array<int,string>}> $skip
     */
    public function __construct(
        private readonly RolePermissionChecker $checker = new RolePermissionChecker(),
        ?RoutePermissionTargetNormalizer $normalizer = null,
        private readonly array $skip = []
    ) {
        $this->normalizer = $normalizer ?? new RoutePermissionTargetNormalizer();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Cake\Http\ServerRequest なら getParam() がある
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

        // 例外：Error は素通し（従来踏襲）
        if ($controller === 'Error') {
            return $handler->handle($request);
        }

        // 例外：login/logout は常に許可（従来踏襲）
        if ($controller === 'Users' && in_array($action, ['login', 'logout'], true)) {
            return $handler->handle($request);
        }

        // 追加のスキップ設定（必要なら）
        foreach ($this->skip as $rule) {
            if (($rule['controller'] ?? null) !== $controller) {
                continue;
            }
            $actions = $rule['actions'] ?? null;
            if ($actions === null || in_array($action, $actions, true)) {
                return $handler->handle($request);
            }
        }

        // 未認証は AuthenticationComponent 側が redirect する想定なので、ここでは何もしない（従来踏襲）
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
            'plugin' => $this->normalizer->normalizePlugin($request->getParam('plugin')),
            'prefix' => $this->normalizer->normalizePrefix($request->getParam('prefix')),
            'controller' => $controller,
            'action' => $action,
        ]);

        if (!$allowed) {
            throw new ForbiddenException('You are not allowed to access this resource.');
        }

        return $handler->handle($request);
    }
}
