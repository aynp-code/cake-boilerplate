<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Utility\Inflector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;

class ControllerActionCatalog
{
    private RoutePermissionTargetNormalizer $normalizer;

    public function __construct(?RoutePermissionTargetNormalizer $normalizer = null)
    {
        $this->normalizer = $normalizer ?? new RoutePermissionTargetNormalizer();
    }

    /**
     * 収集したアクション行の配列を返す
     *
     * 返却形式（RolePermissions の縦軸想定）:
     * [
     *   ['plugin' => null, 'prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index'],
     *   ...
     * ]
     */
    public function collect(): array
    {
        $controllerRoot = ROOT . DS . 'src' . DS . 'Controller';

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllerRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $files = new RegexIterator($it, '/^.+Controller\.php$/i', RegexIterator::GET_MATCH);

        $rows = [];

        foreach ($files as $match) {
            $filepath = $match[0];

            // AppController / ErrorController は除外
            $base = basename($filepath);
            if (in_array($base, ['AppController.php', 'ErrorController.php'], true)) {
                continue;
            }

            // 1) フォルダ構成から “namespace用prefix” を推定（CamelCase + '/'）
            [$nsPrefix, $controller] = $this->inferNamespacePrefixAndController($controllerRoot, $filepath);

            // 2) namespace用prefix で FQCN を組み立て（aliasは適用しない）
            $fqcn = $this->inferFqcn($nsPrefix, $controller);

            if (!class_exists($fqcn)) {
                continue;
            }

            // 3) 権限照合用prefix（DBに入るprefix）は normalizer に集約（alias適用はこちら）
            $permPrefix = $this->normalizer->normalizePrefix($nsPrefix);

            foreach ($this->extractActions($fqcn) as $action) {
                $rows[] = [
                    'plugin' => null,
                    'prefix' => $permPrefix,       // null or 'Admin' or 'Api/V1' or alias後（例: '01.admin'）
                    'controller' => $controller,   // 'Users'
                    'action' => $action,           // 'index'
                ];
            }
        }

        // 安定ソート（差分管理しやすい）
        usort($rows, function (array $a, array $b) {
            return strcmp(
                ($a['prefix'] ?? '') . ':' . $a['controller'] . ':' . $a['action'],
                ($b['prefix'] ?? '') . ':' . $b['controller'] . ':' . $b['action']
            );
        });

        return $rows;
    }

    /**
     * ファイルパスから “namespace用prefix” と controller 名を推定
     * - src/Controller/UsersController.php => [null, 'Users']
     * - src/Controller/Admin/UsersController.php => ['Admin', 'Users']
     * - src/Controller/Api/V1/UsersController.php => ['Api/V1', 'Users']
     *
     * ※ここでは alias は適用しない（FQCN が壊れるため）
     */
    private function inferNamespacePrefixAndController(string $controllerRoot, string $filepath): array
    {
        $relative = str_replace($controllerRoot . DS, '', $filepath);
        $parts = explode(DS, $relative);

        $filename = array_pop($parts); // UsersController.php
        $controller = preg_replace('/Controller\.php$/i', '', $filename);

        $prefix = null;
        if (!empty($parts)) {
            // フォルダ名を CamelCase に揃える（Cakeのprefix慣習）
            $normalizedParts = array_map(static fn(string $p): string => Inflector::camelize($p), $parts);
            $prefix = implode('/', $normalizedParts);
        }

        return [$prefix, $controller];
    }

    /**
     * namespace用prefix/controller から FQCN を組み立て（alias適用しない）
     */
    private function inferFqcn(?string $namespacePrefix, string $controller): string
    {
        $ns = 'App\\Controller';

        if ($namespacePrefix) {
            // 念のため安全化（namespaceに不正文字が入らないように）
            $safePrefix = preg_replace('/[^A-Za-z0-9\/]/', '', $namespacePrefix) ?? $namespacePrefix;
            $ns .= '\\' . str_replace('/', '\\', $safePrefix);
        }

        return $ns . '\\' . $controller . 'Controller';
    }

    /**
     * Controllerクラスから action 一覧を抽出
     */
    private function extractActions(string $controllerFqcn): array
    {
        $ref = new ReflectionClass($controllerFqcn);

        $exclude = [
            '__construct',
            'initialize',
            'beforeFilter',
            'afterFilter',
            'beforeRender',
            'startupProcess',
            'shutdownProcess',
            'redirect',
        ];

        $actions = [];

        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->getDeclaringClass()->getName() !== $controllerFqcn) {
                continue;
            }

            $name = $m->getName();

            if (in_array($name, $exclude, true)) {
                continue;
            }
            if (str_starts_with($name, '_')) {
                continue;
            }

            $actions[] = $name;
        }

        sort($actions);

        return $actions;
    }
}
