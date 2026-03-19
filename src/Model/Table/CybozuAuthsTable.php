<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\CybozuAuth;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use RuntimeException;

/**
 * CybozuAuths Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\CybozuAuth newEmptyEntity()
 * @method \App\Model\Entity\CybozuAuth newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CybozuAuth patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CybozuAuth|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CybozuAuth saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CybozuAuthsTable extends AppTable
{
    /**
     * @return array<string>
     */
    public function restrictDeleteAssociations(): array
    {
        return [];
    }

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('cybozu_auths');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('user_id')
            ->notEmptyString('user_id');

        $validator
            ->scalar('access_token')
            ->notEmptyString('access_token');

        $validator
            ->scalar('refresh_token')
            ->notEmptyString('refresh_token');

        $validator
            ->dateTime('expires_at')
            ->notEmptyDateTime('expires_at');

        $validator
            ->scalar('scope')
            ->maxLength('scope', 512)
            ->allowEmptyString('scope');

        $validator
            ->uuid('created_by')
            ->notEmptyString('created_by');

        $validator
            ->uuid('modified_by')
            ->notEmptyString('modified_by');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['user_id']), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * user_id で cybozu_auth を upsert（なければ作成、あれば更新）する。
     *
     * @param string $userId
     * @param array{access_token:string, refresh_token:string, expires_at:\Cake\I18n\DateTime, scope?:string} $data
     * @return \App\Model\Entity\CybozuAuth
     * @throws \RuntimeException
     */
    public function upsertForUser(string $userId, array $data): CybozuAuth
    {
        /** @var \App\Model\Entity\CybozuAuth|null $existing */
        $existing = $this->find()
            ->where(['user_id' => $userId])
            ->first();

        if ($existing !== null) {
            $auth = $this->patchEntity($existing, $data);
        } else {
            $auth = $this->newEntity(array_merge(['user_id' => $userId], $data));
        }

        if (!$this->save($auth)) {
            throw new RuntimeException('Failed to save CybozuAuth: ' . json_encode($auth->getErrors()));
        }

        return $auth;
    }

    /**
     * user_id でレコードを取得する（なければ null）
     *
     * @param string $userId
     * @return \App\Model\Entity\CybozuAuth|null
     */
    public function findByUserId(string $userId): ?CybozuAuth
    {
        /** @var \App\Model\Entity\CybozuAuth|null */
        return $this->find()
            ->where(['user_id' => $userId])
            ->first();
    }
}
