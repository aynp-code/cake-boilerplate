<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Behavior;
use Cake\ORM\Table;

/**
 * DeleteGuardBehavior
 *
 * Delete前に関連レコードが残っている場合、削除を中止します。
 *
 * 使い方（推奨）:
 * - 各Tableに restrictDeleteAssociations(): array を実装し、
 *   ['Users', 'RolePermissions'] のように「削除禁止判定に使う関連名」を返す。
 * - AppTableで method_exists なら addBehavior('DeleteGuard') する。
 */
class DeleteGuardBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        // 監視する関連名の配列。未指定の場合は Table::restrictDeleteAssociations() を見に行く
        'associations' => null,

        // エラーをセットするフィールド名（UIで拾いやすい場所にしたい場合に変更可）
        'errorField' => '_delete',

        // 関連が見つからない（未定義）時に例外にせずスキップするか
        'skipMissingAssociation' => true,
    ];

    /**
     * Guard against deletion when related records exist.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event.
     * @param \Cake\Datasource\EntityInterface $entity The entity being deleted.
     * @param \ArrayObject<array-key, mixed> $options The options passed to delete.
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        /** @var \Cake\ORM\Table $table */
        $table = $this->table();

        $associations = $this->resolveAssociationsToCheck($table);
        if (!$associations) {
            return;
        }

        $pk = $table->getPrimaryKey();
        $pkField = is_array($pk) ? ($pk[0] ?? null) : $pk;
        if (!$pkField) {
            return; // 複合PKなどはここでは対象外
        }

        $id = $entity->get($pkField);
        if ($id === null || $id === '') {
            return;
        }

        $blocked = [];
        foreach ($associations as $alias) {
            if (!$table->hasAssociation($alias)) {
                if ($this->getConfig('skipMissingAssociation')) {
                    continue;
                }
                $entity->setError((string)$this->getConfig('errorField'), "Missing association: {$alias}");
                $event->stopPropagation();

                return;
            }

            $assoc = $table->getAssociation($alias);

            // HasMany / HasOne: target の foreignKey = 自分のID
            if ($assoc instanceof HasMany || $assoc instanceof HasOne) {
                $fk = $assoc->getForeignKey();
                if (!is_string($fk)) {
                    continue;
                }

                $count = $assoc->getTarget()->find()
                    ->where([$fk => $id])
                    ->count();

                if ($count > 0) {
                    $blocked[$alias] = $count;
                }
                continue;
            }

            // BelongsToMany: junction の foreignKey = 自分のID（中間テーブルの行数で判定）
            if ($assoc instanceof BelongsToMany) {
                $junction = $assoc->junction();
                $fk = $assoc->getForeignKey();
                if (!is_string($fk)) {
                    continue;
                }

                $count = $junction->find()
                    ->where([$fk => $id])
                    ->count();

                if ($count > 0) {
                    $blocked[$alias] = $count;
                }
                continue;
            }

            // その他（BelongsTo等）は「削除禁止判定の対象」としては通常使わないのでスキップ
        }

        if ($blocked) {
            $msgParts = [];
            foreach ($blocked as $alias => $count) {
                $msgParts[] = sprintf('%s(%d)', $alias, $count);
            }

            $entity->setError(
                (string)$this->getConfig('errorField'),
                __('This record cannot be deleted because it is in use: {0}', implode(', ', $msgParts)),
            );

            // 削除を中止
            $event->stopPropagation();

            return;
        }
    }

    /**
     * @return array<string>
     */
    private function resolveAssociationsToCheck(Table $table): array
    {
        $configured = $this->getConfig('associations');

        if (is_array($configured)) {
            // ['Users', 'RolePermissions'] のような形を想定
            return array_values(array_filter($configured, 'is_string'));
        }

        // Table側で宣言している場合（推奨）
        if (method_exists($table, 'restrictDeleteAssociations')) {
            /** @var mixed $declared */
            $declared = $table->restrictDeleteAssociations();
            if (is_array($declared)) {
                return array_values(array_filter($declared, 'is_string'));
            }
        }

        return [];
    }
}
