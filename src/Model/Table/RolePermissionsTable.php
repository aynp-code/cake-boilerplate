<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * RolePermissions Model
 *
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RolePermissionsTable extends AppTable
{
    private const CACHE_CONFIG = '_cake_permissions';

    /**
     * bulkDeleteAllowedTrueByCells() で OR 条件を分割するサイズ
     * （DBによっては OR が長すぎると落ちるので保険）
     */
    private const BULK_DELETE_CHUNK = 300;

    /**
     * bulkSyncAllowedTrue() が saveManyOrFail() を呼ぶと afterSave が走る。
     * トランザクション中に invalidate してしまうのを避けたいので、
     * bulk のときだけ callback で無効化できるようにする。
     */
    private const OPT_SKIP_ROLE_CACHE_INVALIDATION = 'skipRoleCacheInvalidation';

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('role_permissions');
        $this->setDisplayField('controller');
        $this->setPrimaryKey('id');

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('role_id')
            ->notEmptyString('role_id');

        $validator
            ->scalar('plugin')
            ->maxLength('plugin', 120)
            ->allowEmptyString('plugin');

        $validator
            ->scalar('prefix')
            ->maxLength('prefix', 120)
            ->allowEmptyString('prefix');

        $validator
            ->scalar('controller')
            ->maxLength('controller', 120)
            ->requirePresence('controller', 'create')
            ->notEmptyString('controller');

        $validator
            ->scalar('action')
            ->maxLength('action', 120)
            ->requirePresence('action', 'create')
            ->notEmptyString('action');

        $validator
            ->boolean('allowed')
            ->notEmptyString('allowed');

        // created_by / modified_by は UserTrackingBehavior(beforeMarshal) が埋める前提でも、
        // DB的に必須なら validate も必須のままにしておく方が安全。
        $validator
            ->uuid('created_by')
            ->requirePresence('created_by', 'create')
            ->notEmptyString('created_by');

        $validator
            ->uuid('modified_by')
            ->requirePresence('modified_by', 'create')
            ->notEmptyString('modified_by');

        return $validator;
    }

    public function validationCreate(Validator $validator): Validator
    {
        return $this->validationDefault($validator);
    }

    /**
     * Integrity rules.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(
            $rules->isUnique(
                ['role_id', 'plugin', 'prefix', 'controller', 'action'],
                ['allowMultipleNulls' => true]
            ),
            [
                'errorField' => 'role_id',
                'message' => __('This combination of role_id, plugin, prefix, controller and action already exists'),
            ]
        );

        $rules->add($rules->existsIn(['role_id'], 'Roles'), ['errorField' => 'role_id']);

        return $rules;
    }

    /**
     * ===== Cache Invalidation =====
     */

    /**
     * 権限キャッシュの無効化（role_id 単位）
     */
    public function invalidateRoleCache(?string $roleId): void
    {
        if (!is_string($roleId) || $roleId === '') {
            return;
        }
        Cache::delete("perm:role:{$roleId}", self::CACHE_CONFIG);
    }

    /**
     * 複数ロールまとめて無効化（重複は caller 側で潰してOK / ここでも潰す）
     *
     * @param array<int,string> $roleIds
     */
    public function invalidateRolesCache(array $roleIds): void
    {
        $uniq = [];
        foreach ($roleIds as $id) {
            if (is_string($id) && $id !== '') {
                $uniq[$id] = true;
            }
        }

        foreach (array_keys($uniq) as $roleId) {
            $this->invalidateRoleCache($roleId);
        }
    }

    public function afterSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        // bulkSyncAllowedTrue() では transaction 後にまとめて invalidate したいので抑止可能にする
        if (!empty($options[self::OPT_SKIP_ROLE_CACHE_INVALIDATION])) {
            return;
        }

        $roleId = $entity->get('role_id');
        $this->invalidateRoleCache(is_string($roleId) ? $roleId : null);
    }

    public function afterDelete(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if (!empty($options[self::OPT_SKIP_ROLE_CACHE_INVALIDATION])) {
            return;
        }

        $roleId = $entity->get('role_id');
        $this->invalidateRoleCache(is_string($roleId) ? $roleId : null);
    }

    /**
     * ===== Bulk Operations for Matrix =====
     * deleteAll/updateAll 系は afterDelete/afterSave が走らないので、
     * Matrix 更新の “DB反映” はここを通すのが安全。
     */

    /**
     * allowed=true のレコードを差分で INSERT / DELETE する（Matrix向け）
     *
     * - $rowsToInsert: role_permissions に insert する行（allowed=true 前提）
     * - $cellsToDelete: 削除対象セル（role_id/plugin/prefix/controller/action の組）
     *
     * @param array<int,array<string,mixed>> $rowsToInsert
     * @param array<int,array{role_id:string,plugin:?string,prefix:?string,controller:string,action:string}> $cellsToDelete
     */
    public function bulkSyncAllowedTrue(array $rowsToInsert, array $cellsToDelete): void
    {
        $conn = ConnectionManager::get('default');

        // 変更があった roleId だけ invalidate する（重複除去）
        $touchedRoleIds = [];

        foreach ($rowsToInsert as $r) {
            if (isset($r['role_id']) && is_string($r['role_id']) && $r['role_id'] !== '') {
                $touchedRoleIds[$r['role_id']] = true;
            }
        }
        foreach ($cellsToDelete as $c) {
            if (isset($c['role_id']) && is_string($c['role_id']) && $c['role_id'] !== '') {
                $touchedRoleIds[$c['role_id']] = true;
            }
        }

        $conn->transactional(function () use ($rowsToInsert, $cellsToDelete): void {
            // INSERT（saveManyOrFail は afterSave が動くので、bulk では invalidation を抑止）
            if ($rowsToInsert) {
                $entities = $this->newEntities($rowsToInsert);
                $this->saveManyOrFail($entities, [
                    self::OPT_SKIP_ROLE_CACHE_INVALIDATION => true,
                ]);
            }

            // DELETE（deleteAll は afterDelete が動かないので、invalidate は外で必ずやる）
            if ($cellsToDelete) {
                $this->bulkDeleteAllowedTrueByCells($cellsToDelete);
            }
        });

        // ★ transaction 完了後に touched role のキャッシュを落とす（重要）
        if ($touchedRoleIds) {
            $this->invalidateRolesCache(array_keys($touchedRoleIds));
        }
    }

    /**
     * Matrix差分DELETE用：指定されたセル（role_id/plugin/prefix/controller/action）をまとめて消す
     *
     * @param array<int,array{role_id:string,plugin:?string,prefix:?string,controller:string,action:string}> $cells
     */
    public function bulkDeleteAllowedTrueByCells(array $cells): void
    {
        if (!$cells) {
            return;
        }

        // OR が巨大になるのを防ぐ
        $chunks = array_chunk($cells, self::BULK_DELETE_CHUNK);

        foreach ($chunks as $chunk) {
            $or = [];
            foreach ($chunk as $c) {
                $or[] = [
                    'role_id' => $c['role_id'],
                    'plugin IS' => $c['plugin'],
                    'prefix IS' => $c['prefix'],
                    'controller' => $c['controller'],
                    'action' => $c['action'],
                    'allowed' => true,
                ];
            }

            $this->deleteAll(['OR' => $or]);
        }
    }
}
