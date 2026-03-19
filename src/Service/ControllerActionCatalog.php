<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Utility\Inflector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RegexIterator;

class ControllerActionCatalog
{
    /**
     * 収集したアクション行の配列を返す
     *
     * 返却形式（RolePermissions の縦軸想定）:
     * [
     *   ['plugin' => null, 'prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index'],
     *   ...
     * ]
     *
     * @return array<int, array{plugin:?string,prefix:?string,controller:string,action:string}>
     */
    public function collect(): array
    {
        $controllerRoot = ROOT . DS . 'src' . DS . 'Controller';

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllerRoot, RecursiveDirectoryIterator::SKIP_DOTS),
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

            [$prefix, $controller] = $this->inferPrefixAndController($controllerRoot, $filepath);
            $fqcn = $this->inferFqcn($prefix, $controller);

            if (!class_exists($fqcn)) {
                continue;
            }

            foreach ($this->extractActions($fqcn) as $action) {
                $rows[] = [
                    'plugin' => null,
                    'prefix' => $prefix, // null or 'Admin' or 'Api/V1'
                    'controller' => $controller, // 'Users'
                    'action' => $action, // 'index'
                ];
            }
        }

        // 安定ソート（差分管理しやすい）
        usort($rows, function (array $a, array $b) {
            return strcmp(
                ($a['prefix'] ?? '') . ':' . $a['controller'] . ':' . $a['action'],
                ($b['prefix'] ?? '') . ':' . $b['controller'] . ':' . $b['action'],
            );
        });

        return $rows;
    }

    /**
     * ファイルパスから prefix と controller 名を推定
     * - src/Controller/UsersController.php => [null, 'Users']
     * - src/Controller/Admin/UsersController.php => ['Admin', 'Users']
     * - src/Controller/Api/V1/UsersController.php => ['Api/V1', 'Users']
     *
     * @param string $controllerRoot The root directory for controllers.
     * @param string $filepath The full file path.
     * @return array{0:?string,1:string}
     */
    private function inferPrefixAndController(string $controllerRoot, string $filepath): array
    {
        $relative = str_replace($controllerRoot . DS, '', $filepath);
        $parts = explode(DS, $relative);

        $filename = array_pop($parts) ?? ''; // UsersController.php
        $controller = preg_replace('/Controller\.php$/i', '', $filename) ?? $filename;

        $prefix = null;
        if (!empty($parts)) {
            // フォルダ名を Cake の prefix 想定（CamelCase）に正規化
            // 例: admin_panel -> AdminPanel
            $normalizedParts = array_map(
                fn(string $p): string => Inflector::camelize($p),
                $parts,
            );

            $prefix = implode('/', $normalizedParts);
        }

        return [$prefix, $controller];
    }

    /**
     * prefix/controller から FQCN を組み立て
     *
     * @param string|null $prefix The prefix.
     * @param string $controller The controller name.
     * @return string
     */
    private function inferFqcn(?string $prefix, string $controller): string
    {
        $ns = 'App\\Controller';

        if ($prefix) {
            // 'Api/V1' => 'Api\V1'
            $ns .= '\\' . str_replace('/', '\\', $prefix);
        }

        return $ns . '\\' . $controller . 'Controller';
    }

    /**
     * Controllerクラスから action 一覧を抽出
     *
     * @param string $controllerFqcn The fully qualified class name.
     * @return array<int, string>
     */
    private function extractActions(string $controllerFqcn): array
    {
        assert(class_exists($controllerFqcn));
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

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
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
