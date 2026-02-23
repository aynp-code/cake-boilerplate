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

        $this->table('role_permissions')
            ->addForeignKey('role_id', 'roles', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'FK_ROLE_PERMISSIONS_ROLES',
            ])
            ->update();

        // FK: cybozu_auths.user_id → users.id
        $this->table('cybozu_auths')
            ->addForeignKey('user_id', 'users', 'id', [
                'constraint' => 'FK_CYBOZU_AUTHS_USERS',
                'update'     => 'CASCADE',
                'delete'     => 'CASCADE', // ユーザ削除時に連動削除
            ])
            ->update();
    }
}
