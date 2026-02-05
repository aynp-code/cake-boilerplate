<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

class RolePermissionMatrixService
{
    use LocatorAwareTrait;

    public function __construct(
        private readonly ControllerActionCatalog $catalog = new ControllerActionCatalog()
    ) {
    }

    /**
     * 画面表示用の ViewModel を返す
     *
     * @return array{roles: \Cake\Datasource\ResultSetInterface, actionRows: array<int,array>, allowedMap: array<string,array<string,bool>>}
     */
    public function buildViewModel(): array
    {
        $Roles = $this->fetchTable('Roles');
        $RolePermissions = $this->fetchTable('RolePermissions');

        $roles = $Roles->find()
            ->select(['id', 'display_name'])
            ->orderBy(['display_name' => 'ASC'])
            ->all();

        // 縦軸：ControllerActionCatalog から収集（将来拡張しやすい）
        $actionRows = $this->catalog->collect();

        // 既存許可を map 化：[$jsonKey][$roleId] = true
        $allowedMap = [];
        $existing = $RolePermissions->find()
            ->select(['role_id', 'plugin', 'prefix', 'controller', 'action', 'allowed'])
            ->where(['allowed' => true])
            ->all();

        foreach ($existing as $p) {
            $key = $this->jsonKey($p->plugin, $p->prefix, (string)$p->controller, (string)$p->action);
            $allowedMap[$key][(string)$p->role_id] = true;
        }

        return compact('roles', 'actionRows', 'allowedMap');
    }

    /**
     * マトリクス保存（差分更新）
     *
     * @param array $posted perm[rowKey][roleId]=1 の形
     */
    public function save(array $posted): void
    {
        $Roles = $this->fetchTable('Roles');
        $RolePermissions = $this->fetchTable('RolePermissions');

        $roles = $Roles->find()
            ->select(['id'])
            ->all()
            ->toList();

        $actionRows = $this->catalog->collect();

        // 既存（allowed=true）をセット化
        $existing = $RolePermissions->find()
            ->select(['role_id', 'plugin', 'prefix', 'controller', 'action'])
            ->where(['allowed' => true])
            ->all();

        $existingSet = [];
        foreach ($existing as $p) {
            $cell = $this->cellKey((string)$p->role_id, $p->plugin, $p->prefix, (string)$p->controller, (string)$p->action);
            $existingSet[$cell] = true;
        }

        // POST（チェックON）をセット化
        $postedSet = [];
        foreach ($actionRows as $row) {
            $rowKey = $this->rowKey($row['plugin'], $row['prefix'], $row['controller'], $row['action']);

            foreach ($roles as $role) {
                $roleId = (string)$role->id;
                if (empty($posted[$rowKey][$roleId])) {
                    continue;
                }

                $cell = $this->cellKey($roleId, $row['plugin'], $row['prefix'], $row['controller'], $row['action']);
                $postedSet[$cell] = [
                    'role_id' => $roleId,
                    'plugin' => $row['plugin'],
                    'prefix' => $row['prefix'],
                    'controller' => $row['controller'],
                    'action' => $row['action'],
                    'allowed' => true,
                ];
            }
        }

        $toInsert = array_diff_key($postedSet, $existingSet);
        $toDelete = array_diff_key($existingSet, $postedSet);

        // INSERT（必要な分だけ）
        if ($toInsert) {
            // id はテーブル/Entity側で UUID 自動採番にしていないならここで付与
            $rows = [];
            foreach ($toInsert as $row) {
                $row['id'] = \Cake\Utility\Text::uuid();
                $rows[] = $row;
            }

            // validate は有効のまま（created_by/modified_by 必須を守る）
            // UserTrackingBehavior(beforeMarshal) が埋める前提
            $entities = $RolePermissions->newEntities($rows);
            $RolePermissions->saveManyOrFail($entities);
        }

        // DELETE（必要な分だけ）
        if ($toDelete) {
            $or = [];
            foreach (array_keys($toDelete) as $cellKey) {
                [$roleId, $plugin, $prefix, $controller, $action] = explode('||', $cellKey, 5);

                $or[] = [
                    'role_id' => $roleId,
                    'plugin IS' => ($plugin !== '' ? $plugin : null),
                    'prefix IS' => ($prefix !== '' ? $prefix : null),
                    'controller' => $controller,
                    'action' => $action,
                ];
            }

            $RolePermissions->deleteAll(['OR' => $or]);
        }
    }

    private function rowKey($plugin, $prefix, string $controller, string $action): string
    {
        return implode('||', [
            $plugin ?? '',
            $prefix ?? '',
            $controller,
            $action,
        ]);
    }

    private function cellKey(string $roleId, $plugin, $prefix, string $controller, string $action): string
    {
        return implode('||', [
            $roleId,
            $plugin ?? '',
            $prefix ?? '',
            $controller,
            $action,
        ]);
    }

    private function jsonKey($plugin, $prefix, string $controller, string $action): string
    {
        return json_encode([$plugin, $prefix, $controller, $action], JSON_UNESCAPED_UNICODE);
    }
}
