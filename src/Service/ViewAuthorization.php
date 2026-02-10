<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Laminas\Diactoros\Uri;

class ViewAuthorization
{
    private RoutePermissionTargetNormalizer $normalizer;

    public function __construct(
        private readonly RolePermissionChecker $checker = new RolePermissionChecker(),
        ?RoutePermissionTargetNormalizer $normalizer = null
    ) {
        $this->normalizer = $normalizer ?? new RoutePermissionTargetNormalizer();
    }

    /**
     * mixed $url を受けて「判定できるものだけ」権限チェックする
     * - array URL: controller/action に落として判定（足りない場合は request から補完）
     * - string URL:
     *    - 外部URL/#/javascript/mailto/tel は UI 表示のため “許可扱い”（壊さない）
     *    - '/roles/add?x=1' のような内部パスは Router で解析して判定
     * - null/その他: 拒否（安全側）
     */
    public function canUrl(ServerRequest $request, mixed $url): bool
    {
        $roleId = Configure::read('Auth.User.role_id');
        if (!is_string($roleId) || $roleId === '') {
            return false;
        }

        // 1) 配列URL（Cakeの正規ルート）
        if (is_array($url)) {
            $target = $this->normalizeArrayUrl($request, $url);

            // 解析不能なら UI を壊さない（表示は許可）＝ボイラープレートの安全策
            if ($target === null) {
                return true;
            }

            return $this->checker->can($roleId, $target);
        }

        // 2) 文字列URL（外部/アンカー/JS/内部パス）
        if (is_string($url)) {
            $s = trim($url);

            // UI上よくある“実行先を持たない”リンクは壊さない（表示は許可）
            if ($s === '' || $s === '#' || str_starts_with($s, 'javascript:')) {
                return true;
            }
            if (preg_match('#^(https?:)?//#i', $s) === 1) {
                return true; // 外部/プロトコル相対
            }
            if (preg_match('#^(mailto:|tel:)#i', $s) === 1) {
                return true;
            }

            // 内部パスっぽいものだけ Router で解析して判定
            if (str_starts_with($s, '/')) {
                // ?query / #fragment を除いて parse
                $path = preg_split('/[?#]/', $s, 2)[0] ?? $s;

                $target = $this->normalizePathUrl($request, $path);
                if ($target === null) {
                    return true; // 解析不能ならUIを壊さない（表示は許可）
                }
                return $this->checker->can($roleId, $target);
            }

            // それ以外（相対文字列など）は壊さない
            return true;
        }

        // 3) null/その他
        return false;
    }

    /**
     * @param array<string|int, mixed> $url
     * @return array{plugin?:?string,prefix?:?string,controller:string,action:string}|null
     */
    private function normalizeArrayUrl(ServerRequest $request, array $url): ?array
    {
        // named route など（_name）が来た場合、reverse→parse で解決できることがある
        if (isset($url['_name']) && is_string($url['_name'])) {
            try {
                $path = Router::reverse($url);
                return $this->normalizePathUrl($request, $path);
            } catch (\Throwable) {
                return null;
            }
        }

        $plugin = $url['plugin'] ?? $request->getParam('plugin');
        $prefix = $url['prefix'] ?? $request->getParam('prefix');

        // controller が省略されることがある（['action'=>'add']）
        $controller = $url['controller'] ?? $request->getParam('controller');

        // action が省略されることがある（['controller'=>'Users']）
        $action = $url['action'] ?? 'index';

        if (!is_string($controller) || $controller === '' || !is_string($action) || $action === '') {
            return null;
        }

        return [
            'plugin' => $this->normalizer->normalizePlugin($plugin),
            'prefix' => $this->normalizer->normalizePrefix($prefix),
            'controller' => $controller,
            'action' => $action,
        ];
    }

    /**
     * @return array{plugin?:?string,prefix?:?string,controller:string,action:string}|null
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

            $plugin = $params['plugin'] ?? null;
            $prefix = $params['prefix'] ?? null;

            return [
                'plugin' => $this->normalizer->normalizePlugin($plugin),
                'prefix' => $this->normalizer->normalizePrefix($prefix),
                'controller' => $controller,
                'action' => $action,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
