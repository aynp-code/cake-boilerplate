<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Datasource\EntityInterface;

/**
 * UserTracking behavior
 */
class UserTrackingBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        $userId = Configure::read('Auth.User.id');
        if (!$userId) {
            return; // 未ログイン or CLI等
        }

        // 新規作成時のみ created_by
        if ($entity->isNew() && $entity->has('created_by') && !$entity->get('created_by')) {
            $entity->set('created_by', $userId);
        }

        // 更新時は常に modified_by
        if ($entity->has('modified_by')) {
            $entity->set('modified_by', $userId);
        }
    }
}
