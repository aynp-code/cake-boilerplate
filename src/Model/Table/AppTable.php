<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class AppTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        // created / modified は既存通り
        $this->addBehavior('Timestamp');

        // created_by / modified_by を自動で入れる
        $this->addBehavior('UserTracking');
    }
}
