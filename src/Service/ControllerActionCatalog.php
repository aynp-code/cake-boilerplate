<?php
declare(strict_types=1);

namespace App\Service;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;

class ControllerActionCatalog
{
    /**
     * 収集したアクション行の配列を返す
     *
     * 返却形式（RolePermissions の縦軸想定）:
     * [
     *   ['plugin' => null, 'prefix' => 'Api', 'controller' => 'Users', 'action' => 'index'],
     *   ...
     * ]
     *
     * - plugin: 今は null 固定（将来プラグインにも拡張可能）
     * - prefix: src/Controller 配下のサブフォルダを prefix として扱う（例: Api/Admin）
     */
    public function collect(): array
    {
        $controllerRoot = ROOT . DS . 'src' . DS . 'Controller';

        // Controllerファイルを再帰的に列挙（*Controller.php）
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllerRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $files = new RegexIterator($it, '/^.+Controller\.php$/i', RegexIterator::GET_MATCH);

        $rows = [];

        foreach ($files as $match) {
            $filepath = $match[0];

            // AppController / ErrorController は除外（好みで追加）
            $base = basename($filepath);
            if (in_array($base, ['AppController.php', 'ErrorController.php'], true)) {
                continue;
            }

            [$prefix, $controller] = $this->inferPrefixAndController($controllerRoot, $filepath);
            $fqcn = $this->inferFqcn($prefix, $controller);

            if (!class_exists($fqcn)) {
                // composer dump-autoload 不要な設計が理想だが、存在しなければスキップ
                //（必要ならログ出しに変更）
                continue;
            }

            foreach ($this->extractActions($fqcn) as $action) {
                $rows[] = [
                    'plugin' => null,
                    'prefix' => $prefix,          // null or 'Api' etc
                    'controller' => $controller,  // 'Users'
                    'action' => $action,          // 'index'
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
     * ファイルパスから prefix と controller 名を推定
     * - src/Controller/UsersController.php => [null, 'Users']
     * - src/Controller/Api/UsersController.php => ['Api', 'Users']
     */
    private function inferPrefixAndController(string $controllerRoot, string $filepath): array
    {
        $relative = str_replace($controllerRoot . DS, '', $filepath); // e.g. Api/UsersController.php
        $parts = explode(DS, $relative);

        $filename = array_pop($parts); // UsersController.php
        $controller = preg_replace('/Controller\.php$/i', '', $filename);

        // 残りが prefix（サブフォルダ階層）: Api/V1 のような階層にも対応
        // Cakeのprefixは通常 CamelCase だが、ここではフォルダ名をそのまま Camelize せず使う（統一したいなら後で変える）
        $prefix = null;
        if (!empty($parts)) {
            // Api/V1 => 'Api/V1' をprefixとして保持する案もあるが、Cakeのprefix paramは配列になることもある
            // 今回は "Api" のような1階層運用を想定し、複数階層は 'Api/V1' として保持
            $prefix = implode('/', $parts);
        }

        return [$prefix, $controller];
    }

    /**
     * prefix/controller から FQCN を組み立て
     */
    private function inferFqcn(?string $prefix, string $controller): string
    {
        // namespace は App\Controller\[Prefix\]FooController
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
     * public メソッドのうち、Cakeのアクションとして不適切なものを除外
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
            // 自クラスで宣言されていないもの（親のpublic）は除外
            if ($m->getDeclaringClass()->getName() !== $controllerFqcn) {
                continue;
            }

            $name = $m->getName();

            // 除外
            if (in_array($name, $exclude, true)) {
                continue;
            }
            if (str_starts_with($name, '_')) {
                continue;
            }

            // Cakeの慣習上、actionは lowerCamel が多いが、ここではそのまま返す
            $actions[] = $name;
        }

        sort($actions);

        return $actions;
    }
}
