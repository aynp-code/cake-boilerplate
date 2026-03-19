<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;

class RolePermissionMatrixService
{
    use LocatorAwareTrait;

    private const SEP = '||';

    /**
     * @param \App\Service\ControllerActionCatalog $catalog The controller action catalog.
     */
    public function __construct(
        private readonly ControllerActionCatalog $catalog = new ControllerActionCatalog(),
    ) {
    }

    /**
     * 画面表示用の ViewModel を返す
     *
     * @return array{
     *   roles: \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Role>,
     *   actionRows: array<int, array{plugin:?string,prefix:?string,controller:string,action:string}>,
     *   allowedMap: array<string, array<string, bool>>
     * }
     */
    public function buildViewModel(): array
    {
        $Roles = $this->fetchTable('Roles');
        /** @var \App\Model\Table\RolePermissionsTable $RolePermissions */
        $RolePermissions = $this->fetchTable('RolePermissions');

        /** @var \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Role> $roles */
        $roles = $Roles->find()
            ->select(['id', 'display_name'])
            ->orderBy(['display_name' => 'ASC'])
            ->all();

        // 縦軸：ControllerActionCatalog から収集
        $actionRows = $this->catalog->collect();

        // 既存許可を map 化：[$jsonKey][$roleId] = true
        $allowedMap = [];
        $existing = $RolePermissions->find()
            ->select(['role_id', 'plugin', 'prefix', 'controller', 'action'])
            ->where(['allowed' => true])
            ->all();

        foreach ($existing as $p) {
            if (!($p instanceof EntityInterface)) {
                continue;
            }

            $pPlugin = $p->get('plugin');
            $pPrefix = $p->get('prefix');
            $key = $this->jsonKey(
                is_string($pPlugin) ? $pPlugin : null,
                is_string($pPrefix) ? $pPrefix : null,
                (string)$p->get('controller'),
                (string)$p->get('action'),
            );
            $allowedMap[$key][(string)$p->get('role_id')] = true;
        }

        return compact('roles', 'actionRows', 'allowedMap');
    }

    /**
     * マトリクス保存（差分更新）
     *
     * @param array<string, array<string, mixed>> $posted perm[rowKey][roleId]=1 の形
     */
    public function save(array $posted): void
    {
        $Roles = $this->fetchTable('Roles');
        /** @var \App\Model\Table\RolePermissionsTable $RolePermissions */
        $RolePermissions = $this->fetchTable('RolePermissions');

        // roleId 一覧（表示順は不要）
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

        /** @var array<string, true> $existingSet */
        $existingSet = [];
        foreach ($existing as $p) {
            if (!($p instanceof EntityInterface)) {
                continue;
            }

            $pPlugin = $p->get('plugin');
            $pPrefix = $p->get('prefix');
            $cell = $this->cellKey(
                (string)$p->get('role_id'),
                is_string($pPlugin) ? $pPlugin : null,
                is_string($pPrefix) ? $pPrefix : null,
                (string)$p->get('controller'),
                (string)$p->get('action'),
            );
            $existingSet[$cell] = true;
        }

        // POST（チェックON）をセット化（insert row を持つ）
        /** @var array<string, array{role_id:string,plugin:?string,prefix:?string,controller:string,action:string,allowed:bool}> $postedSet */
        $postedSet = [];

        foreach ($actionRows as $row) {
            $rowKey = $this->rowKey($row['plugin'], $row['prefix'], $row['controller'], $row['action']);

            foreach ($roles as $role) {
                if (!($role instanceof EntityInterface)) {
                    continue;
                }

                $roleId = (string)$role->get('id');

                // チェックONだけ採用（HTMLフォーム由来なので truthy 判定でOK）
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

        // INSERT用 rows（uuid付与）
        /** @var array<int, array<string, mixed>> $rowsToInsert */
        $rowsToInsert = [];
        foreach ($toInsert as $row) {
            $row['id'] = Text::uuid();
            $rowsToInsert[] = $row;
        }

        // DELETE用 cells
        // RolePermissionsTable::bulkDeleteAllowedTrueByCells() の形式に合わせる
        /** @var array<int, array{role_id:string,plugin:?string,prefix:?string,controller:string,action:string}> $cellsToDelete */
        $cellsToDelete = [];
        foreach (array_keys($toDelete) as $cellKey) {
            [$roleId, $plugin, $prefix, $controller, $action] = explode(self::SEP, $cellKey, 5);

            $cellsToDelete[] = [
                'role_id' => $roleId,
                'plugin' => ($plugin !== '' ? $plugin : null),
                'prefix' => ($prefix !== '' ? $prefix : null),
                'controller' => $controller,
                'action' => $action,
            ];
        }

        // ★ DB反映 + キャッシュ無効化は Table に寄せる（責務の分離）
        // deleteAll はイベントが走らないため、Table経由で invalidate を確実にする
        $RolePermissions->bulkSyncAllowedTrue($rowsToInsert, $cellsToDelete);
    }

    /**
     * Build a row key for the permission matrix.
     *
     * @param string|null $plugin The plugin name.
     * @param string|null $prefix The prefix.
     * @param string $controller The controller name.
     * @param string $action The action name.
     * @return string
     */
    private function rowKey(?string $plugin, ?string $prefix, string $controller, string $action): string
    {
        return implode(self::SEP, [
            $plugin ?? '',
            $prefix ?? '',
            $controller,
            $action,
        ]);
    }

    /**
     * Build a cell key for the permission matrix.
     *
     * @param string $roleId The role ID.
     * @param string|null $plugin The plugin name.
     * @param string|null $prefix The prefix.
     * @param string $controller The controller name.
     * @param string $action The action name.
     * @return string
     */
    private function cellKey(
        string $roleId,
        ?string $plugin,
        ?string $prefix,
        string $controller,
        string $action,
    ): string {
        return implode(self::SEP, [
            $roleId,
            $plugin ?? '',
            $prefix ?? '',
            $controller,
            $action,
        ]);
    }

    /**
     * Build a JSON key for the allowed map.
     *
     * @param string|null $plugin The plugin name.
     * @param string|null $prefix The prefix.
     * @param string $controller The controller name.
     * @param string $action The action name.
     * @return string
     */
    private function jsonKey(?string $plugin, ?string $prefix, string $controller, string $action): string
    {
        // json_encode が false を返すケースを避ける（UTF-8前提だが保険）
        $json = json_encode([$plugin, $prefix, $controller, $action], JSON_UNESCAPED_UNICODE);

        return is_string($json) ? $json : '';
    }
}
