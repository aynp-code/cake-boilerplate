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
            ->addColumn('display_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'string', ['limit' => 512, 'null' => true])       

            // 監査用（作成・更新）
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addColumn('modified_by', 'uuid', ['null' => false])

            // ユニーク制約
            ->addIndex(['display_name'], ['unique' => true, 'name' => 'UQ_ROLES_NAME'])
            ->create();

        $table = $this->table('users', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table
            ->addColumn('id', 'uuid')
            ->addColumn('username', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ログインユーザー名'])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ログインパスワード'])
            ->addColumn('display_name', 'string', ['limit' => 255, 'null' => false, 'comment' => '表示名'])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('kintone_username', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'comment' => 'kintone連携用のユーザー名'])

            // ロール（管理者・一般など）
            ->addColumn('role_id', 'uuid', ['null' => false])

            // 有効/無効（業務用だと便利）
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])

            // 監査用（作成・更新）
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addColumn('modified_by', 'uuid', ['null' => false])

            // ユニーク制約
            ->addIndex(['username'], ['unique' => true, 'name' => 'UQ_USERS_USERNAME'])
            ->addIndex(['email'], ['unique' => true, 'name' => 'UQ_USERS_EMAIL'])
            ->addIndex(['kintone_username'], ['unique' => true, 'name' => 'UQ_USERS_KINTONE_USERNAME'])
            ->create();

        $table = $this->table('role_permissions', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table
            ->addColumn('id', 'uuid')
            ->addColumn('role_id', 'uuid', ['null' => false])

            // 将来の拡張用（今は全部NULLでOK）
            ->addColumn('plugin', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('prefix', 'string', ['limit' => 120, 'null' => true])

            // ここが本題：controller/action 単位
            ->addColumn('controller', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('action', 'string', ['limit' => 120, 'null' => false])

            // 許可・不許可
            ->addColumn('allowed', 'boolean', ['default' => true, 'null' => false])
            
            // 監査用（作成・更新）
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addColumn('modified_by', 'uuid', ['null' => false])

            // 1 role につき 1 controller/action を一意に
            ->addIndex(
                ['role_id', 'plugin', 'prefix', 'controller', 'action'],
                ['unique' => true, 'name' => 'UQ_ROLE_PERMISSIONS_RULE']
            )
            ->addIndex(['role_id'], ['name' => 'IDX_ROLE_PERMISSIONS_ROLE'])
            ->create();
    }
}