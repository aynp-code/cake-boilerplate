<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * KintoneWebhookRecords Table
 *
 * kintone webhook 経由で同期されたレコード。
 * システム生成テーブルのため AppTable は継承せず、Timestamp のみ付与する。
 * JSON カラム (checkbox_value / attachments) は保存時に json_encode 済みの文字列を渡す。
 */
class KintoneWebhookRecordsTable extends Table
{
    /**
     * @param array<string, mixed> $config テーブル設定
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('kintone_webhook_records');
        $this->setEntityClass('App\Model\Entity\KintoneWebhookRecord');
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
            ->maxLength('kintone_record_number', 50)
            ->requirePresence('kintone_record_number', 'create')
            ->notEmptyString('kintone_record_number');

        $validator
            ->allowEmptyString('single_line_text')
            ->maxLength('single_line_text', 255);

        $validator
            ->allowEmptyString('multi_line_text');

        $validator
            ->allowEmptyString('dropdown_value')
            ->maxLength('dropdown_value', 255);

        $validator
            ->allowEmptyString('radio_button_value')
            ->maxLength('radio_button_value', 255);

        $validator
            ->allowEmptyString('kintone_creator')
            ->maxLength('kintone_creator', 255);

        $validator
            ->allowEmptyString('kintone_modifier')
            ->maxLength('kintone_modifier', 255);

        return $validator;
    }
}
