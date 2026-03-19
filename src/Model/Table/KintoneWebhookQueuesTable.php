<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * KintoneWebhookQueues Table
 *
 * システム生成テーブルのため AppTable は継承せず、
 * Timestamp のみ付与する（UserTracking は不要）。
 */
class KintoneWebhookQueuesTable extends Table
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public const MAX_ATTEMPTS = 3;

    /**
     * @param array<string, mixed> $config テーブル設定
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('kintone_webhook_queues');
        $this->setEntityClass('App\Model\Entity\KintoneWebhookQueue');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * @param \Cake\Validation\Validator $validator バリデーター
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->requirePresence('id', 'create')
            ->notEmptyString('id');

        $validator
            ->integer('app_id')
            ->requirePresence('app_id', 'create')
            ->greaterThan('app_id', 0);

        $validator
            ->integer('record_id')
            ->requirePresence('record_id', 'create')
            ->greaterThan('record_id', 0);

        $validator
            ->maxLength('event_type', 100)
            ->requirePresence('event_type', 'create')
            ->notEmptyString('event_type');

        $validator
            ->requirePresence('payload', 'create')
            ->notEmptyString('payload');

        $validator
            ->inList('status', [
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_DONE,
                self::STATUS_FAILED,
            ])
            ->notEmptyString('status');

        $validator
            ->integer('attempts')
            ->greaterThanOrEqual('attempts', 0);

        return $validator;
    }
}
