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

        // created_by があれば作成者ユーザ関連を自動で張る
        if ($this->hasField('created_by')) {
            $this->belongsTo('CreatedByUser', [
                'className' => 'Users',
                'foreignKey' => 'created_by',
                'joinType' => 'LEFT',
            ]);
        }

        // modified_by があれば更新者ユーザ関連を自動で張る
        if ($this->hasField('modified_by')) {
            $this->belongsTo('ModifiedByUser', [
                'className' => 'Users',
                'foreignKey' => 'modified_by',
                'joinType' => 'LEFT',
            ]);
        }
    }
}
