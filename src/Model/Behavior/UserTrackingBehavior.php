<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;

/**
 * UserTracking behavior
 *
 * created_by / modified_by を自動補完する。
 * - beforeMarshal: validation 前に data を補完（重要）
 * - beforeSave: Entity 直保存などの保険
 */
class UserTrackingBehavior extends Behavior
{
    protected array $_defaultConfig = [];

    public function implementedEvents(): array
    {
        return [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.beforeSave' => 'beforeSave',
        ];
    }

    /**
     * beforeMarshal: バリデーション前に送信データを補完する
     *
     * CakePHP の beforeMarshal は `$data` が ArrayObject として渡る想定だが、
     * 実装差異や型の取り扱いで崩れると補完が効かず必須バリデーションで落ちるため、
     * ここは堅く書く。
     *
     * @param \Cake\Event\EventInterface $event
     * @param \ArrayObject $data
     * @param \ArrayObject $options
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $userId = Configure::read('Auth.User.id');

        // 未ログイン / CLI 等
        if (!is_string($userId) || $userId === '') {
            return;
        }

        // newRecord 判定（取れる場合だけ使う。取れない場合は created_by 未指定なら入れる方針）
        $isNew = null;
        if (isset($options['newRecord'])) {
            $isNew = (bool)$options['newRecord'];
        }

        // created_by：新規時だけ（フォームに無くてもOK）
        if ($this->_table->hasField('created_by')) {
            $has = isset($data['created_by']) && $data['created_by'] !== '';
            if (!$has && ($isNew === null || $isNew === true)) {
                $data['created_by'] = $userId;
            }
        }

        // modified_by：常にセット
        if ($this->_table->hasField('modified_by')) {
            $data['modified_by'] = $userId;
        }
    }

    /**
     * beforeSave: 予備措置（Entityが直接作られたケース）
     *
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject $options
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $userId = Configure::read('Auth.User.id');
        if (!is_string($userId) || $userId === '') {
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
