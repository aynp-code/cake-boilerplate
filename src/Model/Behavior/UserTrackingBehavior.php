<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use ArrayObject;

/**
 * UserTracking behavior
 */
class UserTrackingBehavior extends Behavior
{
    protected array $_defaultConfig = [];

    public function implementedEvents(): array
    {
        return [
            // patchEntity の前に data 配列を書き換え → validation 前に値が入る
            'Model.beforeMarshal' => 'beforeMarshal',
            // 念のため Entity 経由の保存にも備える
            'Model.beforeSave' => 'beforeSave',
        ];
    }

    /**
     * beforeMarshal: バリデーション前に送信データを補完する
     *
     * @param \Cake\Event\EventInterface $event
     * @param \ArrayObject $data
     * @param \ArrayObject $options
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $userId = Configure::read('Auth.User.id');
        if (!$userId) {
            return;
        }

        // 新規作成時のみ created_by を補完（フォームに無くてもOK）
        if ((!isset($data['created_by']) || $data['created_by'] === '') && $this->_table->hasField('created_by')) {
            $data['created_by'] = $userId;
        }

        // 常に modified_by をセット（フォームに無くてもDBに入る）
        if ($this->_table->hasField('modified_by')) {
            $data['modified_by'] = $userId;
        }
    }

    /**
     * beforeSave: 予備措置（Entityが直接作られたケース）
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        $userId = Configure::read('Auth.User.id');
        if (!$userId) {
            return;
        }

        if ($entity->isNew() && $entity->has('created_by') && !$entity->get('created_by')) {
            $entity->set('created_by', $userId);
        }

        if ($entity->has('modified_by')) {
            $entity->set('modified_by', $userId);
        }
    }
}
