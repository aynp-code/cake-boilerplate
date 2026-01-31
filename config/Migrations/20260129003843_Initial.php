<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class Initial extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('roles', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table
            ->addColumn('id', 'uuid')
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'string', ['limit' => 512, 'null' => true])       

            // 監査用（作成・更新）
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('created_by', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified_by', 'uuid', ['null' => true, 'default' => null])

            // ユニーク制約
            ->addIndex(['name'], ['unique' => true, 'name' => 'UQ_ROLES_NAME'])
            ->create();

        $table = $this->table('users', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table
            ->addColumn('id', 'uuid')
            ->addColumn('username', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])

            // ロール（管理者・一般など）
            ->addColumn('role_id', 'uuid', ['null' => false])

            // 有効/無効（業務用だと便利）
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])

            // 監査用（作成・更新）
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('created_by', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified_by', 'uuid', ['null' => true, 'default' => null])

            // ユニーク制約
            ->addIndex(['username'], ['unique' => true, 'name' => 'UQ_USERS_USERNAME'])
            ->addIndex(['email'], ['unique' => true, 'name' => 'UQ_USERS_EMAIL'])

            // 外部キー制約
            ->addForeignKey('role_id', 'roles', 'id', [
                'constraint' => 'FK_USERS_ROLES',
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
            ])
            ->create();
    }
}
