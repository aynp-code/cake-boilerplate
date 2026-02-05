<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * RolePermissions Model
 *
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 *
 * @method \App\Model\Entity\RolePermission newEmptyEntity()
 * @method \App\Model\Entity\RolePermission newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\RolePermission> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\RolePermission get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\RolePermission findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\RolePermission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\RolePermission> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\RolePermission|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\RolePermission saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\RolePermission>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\RolePermission>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\RolePermission>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\RolePermission> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\RolePermission>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\RolePermission>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\RolePermission>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\RolePermission> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RolePermissionsTable extends AppTable
{
    /**
     * 関連データがある場合は削除を禁止する
     * DeleteGuardBehavior が beforeDelete で参照し、関連レコードが残っていれば削除を止める。
     *
     * @return string[]
     */
    public function restrictDeleteAssociations(): array
    {
        return [
            // 関連（hasMany / hasOne / belongsToMany 等）をここに追加します。

        ];
    }

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
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
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
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

    
    /**
     * Validation rules for create.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationCreate(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['role_id', 'plugin', 'prefix', 'controller', 'action'], ['allowMultipleNulls' => true]), ['errorField' => 'role_id', 'message' => __('This combination of role_id, plugin, prefix, controller and action already exists')]);
        $rules->add($rules->existsIn(['role_id'], 'Roles'), ['errorField' => 'role_id']);

        return $rules;
    }
}
