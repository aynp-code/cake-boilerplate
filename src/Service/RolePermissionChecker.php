<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

class RolePermissionChecker
{
    use LocatorAwareTrait;

    /**
     * @param string $roleId
     * @param array{plugin?:?string,prefix?:?string,controller:string,action:string} $target
     */
    public function can(string $roleId, array $target): bool
    {
        $plugin = $target['plugin'] ?? null;
        $prefix = $target['prefix'] ?? null;
        $controller = $target['controller'];
        $action = $target['action'];

        $RolePermissions = $this->fetchTable('RolePermissions');

        // 具体→汎用（ワイルドカード）で探索
        $candidates = [
            [$controller, $action],
            ['*', $action],
            [$controller, '*'],
            ['*', '*'],
        ];

        foreach ($candidates as [$c, $a]) {
            $perm = $RolePermissions->find()
                ->select(['allowed'])
                ->where([
                    'role_id' => $roleId,
                    'plugin IS' => $plugin,
                    'prefix IS' => $prefix,
                    'controller' => $c,
                    'action' => $a,
                ])
                ->first();

            if ($perm !== null) {
                return (bool)$perm->allowed;
            }
        }

        // デフォルト拒否（安全側）
        return false;
    }
}
