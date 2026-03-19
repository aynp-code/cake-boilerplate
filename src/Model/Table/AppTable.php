<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

class AppTable extends Table
{
    /**
     * Initialize method.
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        // created / modified は既存通り
        $this->addBehavior('Timestamp');

        // created_by / modified_by を自動で入れる
        $this->addBehavior('UserTracking');

        // created_by があれば作成者ユーザ関連を自動で張る（既存定義があればスキップ）
        if ($this->hasField('created_by') && !$this->hasAssociation('CreatedByUser')) {
            $this->belongsTo('CreatedByUser', [
                'className' => 'Users',
                'foreignKey' => 'created_by',
                'joinType' => 'LEFT',
            ]);
        }

        // modified_by があれば更新者ユーザ関連を自動で張る（既存定義があればスキップ）
        if ($this->hasField('modified_by') && !$this->hasAssociation('ModifiedByUser')) {
            $this->belongsTo('ModifiedByUser', [
                'className' => 'Users',
                'foreignKey' => 'modified_by',
                'joinType' => 'LEFT',
            ]);
        }

        // ===== DeleteGuard（削除ガード） =====
        // 各Table側で restrictDeleteAssociations(): array を実装しているものだけ有効化する（誤爆防止）
        if (method_exists($this, 'restrictDeleteAssociations')) {
            $this->addBehavior('DeleteGuard');
        }
    }

    /**
     * contain 配列に、CreatedByUser / ModifiedByUser を
     * - 自テーブル
     * - related（HasMany / BelongsToMany）の target テーブル（1段）
     * へ付与して返します。
     *
     * Controller 側では $contain = $this->Table->withAuditUsersContain([...]);
     * のように使えます。
     *
     * @param array<int|string, mixed> $contain
     * @param array<int, string> $relatedTypes ネスト付与対象の関連タイプ
     * @return array<int|string, mixed>
     */
    public function withAuditUsersContain(
        array $contain = [],
        array $relatedTypes = ['HasMany', 'BelongsToMany'],
    ): array {
        // 自テーブル側の監査ユーザ
        $contain = $this->addAuditUsersToContain($contain);

        // related（HasMany / BelongsToMany）側にもネスト付与
        foreach ($relatedTypes as $type) {
            foreach ($this->associations()->getByType($type) as $assoc) {
                $alias = $assoc->getName();

                // まず related 自体を contain に入れる（未指定なら）
                if (!array_key_exists($alias, $contain) && !in_array($alias, $contain, true)) {
                    $contain[] = $alias;
                }

                // すでに alias が配列指定になっていたらそれをベースにする
                $nested = [];
                if (array_key_exists($alias, $contain) && is_array($contain[$alias])) {
                    $nested = $contain[$alias];
                }

                $target = $assoc->getTarget();

                // 関連先テーブルが監査ユーザ関連を持っていればネスト contain に足す
                if ($target->hasAssociation('CreatedByUser') && !in_array('CreatedByUser', $nested, true)) {
                    $nested[] = 'CreatedByUser';
                }
                if ($target->hasAssociation('ModifiedByUser') && !in_array('ModifiedByUser', $nested, true)) {
                    $nested[] = 'ModifiedByUser';
                }

                if ($nested) {
                    // 例: 'Users' => ['CreatedByUser', 'ModifiedByUser']
                    $contain[$alias] = $nested;
                }
            }
        }

        return $contain;
    }

    /**
     * Finder: find('withAuditUsers', contain: [...]) で使えます。
     * view などで監査ユーザの表示名を関連側も含めて取る用途向け。
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @param array<string, mixed> $options
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findWithAuditUsers(SelectQuery $query, array $options): SelectQuery
    {
        /** @var array<int|string, mixed> $contain */
        $contain = $options['contain'] ?? [];

        /** @var array<int, string> $relatedTypes */
        $relatedTypes = $options['relatedTypes'] ?? ['HasMany', 'BelongsToMany'];

        $query->contain($this->withAuditUsersContain($contain, $relatedTypes));

        return $query;
    }

    /**
     * 自テーブルの contain に CreatedByUser / ModifiedByUser を追加します（存在する場合のみ）。
     *
     * @param array<int|string, mixed> $contain
     * @return array<int|string, mixed>
     */
    protected function addAuditUsersToContain(array $contain): array
    {
        if (
            $this->hasAssociation('CreatedByUser')
            && !in_array('CreatedByUser', $contain, true)
            && !array_key_exists('CreatedByUser', $contain)
        ) {
            $contain[] = 'CreatedByUser';
        }

        if (
            $this->hasAssociation('ModifiedByUser')
            && !in_array('ModifiedByUser', $contain, true)
            && !array_key_exists('ModifiedByUser', $contain)
        ) {
            $contain[] = 'ModifiedByUser';
        }

        return $contain;
    }
}
