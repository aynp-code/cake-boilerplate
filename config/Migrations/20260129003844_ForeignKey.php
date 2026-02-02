<?php
declare(strict_types=1);

use Migrations\BaseMigration;

final class ForeignKey extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addIndex(['role_id'], [
                'name' => 'IDX_USERS_ROLE_ID',
            ])
            ->addForeignKey('role_id', 'roles', 'id', [
                'constraint' => 'FK_USERS_ROLES',
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
            ])
            ->update();
    }
}
