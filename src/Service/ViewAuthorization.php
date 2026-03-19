<?php
declare(strict_types=1);

namespace App\Service;

use App\Middleware\CurrentUserMiddleware;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Laminas\Diactoros\Uri;
use Throwable;

class ViewAuthorization
{
    private RoutePermissionTargetNormalizer $normalizer;

    /**
     * @param \App\Service\RolePermissionChecker $checker The permission checker.
     * @param \App\Service\RoutePermissionTargetNormalizer|null $normalizer The route target normalizer.
     */
    public function __construct(
        private readonly RolePermissionChecker $checker = new RolePermissionChecker(),
        ?RoutePermissionTargetNormalizer $normalizer = null,
    ) {
        $this->normalizer = $normalizer ?? new RoutePermissionTargetNormalizer();
    }

    /**
     * mixed $url を受けて「判定できるものだけ」権限チェックする。
     *
     * role_id の取得元を Configure から Request Attribute に変更。
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param mixed $url The URL to check.
     * @return bool
     */
    public function canUrl(ServerRequest $request, mixed $url): bool
    {
        // Configure::read ではなく Request Attribute から取得
        $currentUser = $request->getAttribute(CurrentUserMiddleware::ATTRIBUTE);
        $roleId = $currentUser['role_id'] ?? null;

        if (!is_string($roleId) || $roleId === '') {
            return false;
        }

        if (is_array($url)) {
            $target = $this->normalizeArrayUrl($request, $url);
            if ($target === null) {
                return true;
            }

            return $this->checker->can($roleId, $target);
        }

        if (is_string($url)) {
            $s = trim($url);

            if ($s === '' || $s === '#' || str_starts_with($s, 'javascript:')) {
                return true;
            }
            if (preg_match('#^(https?:)?//#i', $s)) {
                return true;
            }
            if (preg_match('#^(mailto:|tel:)#i', $s)) {
                return true;
            }

            if (str_starts_with($s, '/')) {
                $path = preg_split('/[?#]/', $s, 2)[0] ?? $s;
                $target = $this->normalizePathUrl($request, $path);
                if ($target === null) {
                    return true;
                }

                return $this->checker->can($roleId, $target);
            }

            return true;
        }

        return false;
    }

    /**
     * Normalize an array URL to a permission target array.
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param array<string|int, mixed> $url The array URL.
     * @return array<string, mixed>|null
     */
    private function normalizeArrayUrl(ServerRequest $request, array $url): ?array
    {
        if (isset($url['_name']) && is_string($url['_name'])) {
            try {
                $path = Router::reverse($url);

                return $this->normalizePathUrl($request, $path);
            } catch (Throwable) {
                return null;
            }
        }

        $controller = $url['controller'] ?? $request->getParam('controller');
        $action = $url['action'] ?? 'index';

        if (!is_string($controller) || !is_string($action) || $controller === '' || $action === '') {
            return null;
        }

        return [
            'plugin' => $this->normalizer->normalizePlugin($url['plugin'] ?? $request->getParam('plugin')),
            'prefix' => $this->normalizer->normalizePrefix($url['prefix'] ?? $request->getParam('prefix')),
            'controller' => $controller,
            'action' => $action,
        ];
    }

    /**
     * Normalize a path URL to a permission target array.
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param string $path The path to normalize.
     * @return array<string, mixed>|null
     */
    private function normalizePathUrl(ServerRequest $request, string $path): ?array
    {
        try {
            $req2 = $request->withUri(new Uri($path));
            $params = Router::parseRequest($req2);

            $controller = $params['controller'] ?? null;
            $action = $params['action'] ?? null;

            if (!is_string($controller) || !is_string($action) || $controller === '' || $action === '') {
                return null;
            }

            return [
                'plugin' => $this->normalizer->normalizePlugin($params['plugin'] ?? null),
                'prefix' => $this->normalizer->normalizePrefix($params['prefix'] ?? null),
                'controller' => $controller,
                'action' => $action,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
