<?php
declare(strict_types=1);

use Migrations\BaseSeed;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Cake\Utility\Text;

/**
 * Initial seed.
 * 
 * 開発環境（debug=true）の場合は、Fakerを使って100名のテストユーザーを作成します。
 */
class InitialSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/migrations/4/en/seeding.html
     *
     * @return void
     */
    public function run(): void
    {
        // ===================================
        // 1. Roles（ロール）の作成
        // ===================================
        $rolesData = [
            [
                'id' => 'faa5ab22-2178-4833-b8e9-4db2f023e38f',
                'display_name' => '01.admin',
                'description' => 'Has access to most system features.',
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
            [
                'id' => 'cb5f91fc-df15-422b-9d4a-f87fda893b77',
                'display_name' => '02.user',
                'description' => 'Standard user role.',
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
            [
                'id' => '2b072d8e-f9a7-4e79-9e6d-2bfbe04a2074',
                'display_name' => '03.guest',
                'description' => 'Limited access role.',
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
        ];

        $rolesTable = TableRegistry::getTableLocator()->get('Roles');
        foreach ($rolesData as $record) {
            $entity = $rolesTable->newEntity(
                $record,
                ['accessibleFields' => ['id' => true]]
            );
            $rolesTable->saveOrFail($entity);
        }
        $this->io->out('✓ Roles created successfully');

        // ===================================
        // 2. RolePermissions（ロール権限）の作成
        // ===================================
        $permissionsData = [
            [
                'role_id' => 'faa5ab22-2178-4833-b8e9-4db2f023e38f', // admin
                'plugin' => null,
                'prefix' => null,
                'controller' => '*',
                'action' => '*',
                'allowed' => 1,
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
        ];

        $permissionsTable = TableRegistry::getTableLocator()->get('RolePermissions');
        foreach ($permissionsData as $record) {
            $entity = $permissionsTable->newEntity($record);
            $permissionsTable->saveOrFail($entity);
        }
        $this->io->out('✓ Role Permissions created successfully');

        // ===================================
        // 3. Users（ユーザー）の作成
        // ===================================
        $usersTable = TableRegistry::getTableLocator()->get('Users');

        // 基本ユーザー（admin, user）を作成
        $basicUsersData = [
            [
                'id' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'display_name' => 'System Administrator',
                'password' => 'password',
                'role_id' => 'faa5ab22-2178-4833-b8e9-4db2f023e38f',
                'is_active' => true,
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
            [
                'id' => '61b448bd-7611-4a2a-ab1f-fa35319465ad',
                'username' => 'user',
                'email' => 'user@example.com',
                'display_name' => 'Regular User',
                'password' => 'password',
                'role_id' => 'cb5f91fc-df15-422b-9d4a-f87fda893b77',
                'is_active' => true,
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
        ];

        foreach ($basicUsersData as $record) {
            $entity = $usersTable->newEntity(
                $record,
                ['accessibleFields' => ['id' => true]]
            );
            $usersTable->saveOrFail($entity);
        }
        $this->io->out('✓ Basic users (admin, user) created successfully');

        // ===================================
        // 4. 開発環境の場合のみ、Fakerで100名のテストユーザーを作成
        // ===================================
        $isDebugMode = Configure::read('debug');

        if ($isDebugMode) {
            $this->io->out('');
            $this->io->out('🔧 Debug mode detected - Creating 100 test users with Faker...');

            // Fakerインスタンスの作成（日本語ロケール）
            $faker = \Faker\Factory::create('ja_JP');

            // ロールIDの配列（ランダムに割り当て用）
            $roleIds = [
                'faa5ab22-2178-4833-b8e9-4db2f023e38f', // admin
                'cb5f91fc-df15-422b-9d4a-f87fda893b77', // user
                '2b072d8e-f9a7-4e79-9e6d-2bfbe04a2074', // guest
            ];

            // 100名のテストユーザーを作成
            for ($i = 1; $i <= 98; $i++) {
                $userData = [
                    'id' => Text::uuid(),
                    'username' => $faker->unique()->userName(),
                    'email' => $faker->unique()->safeEmail(),
                    'display_name' => $faker->name(),
                    'password' => 'password', // テスト用の共通パスワード
                    'role_id' => $faker->randomElement($roleIds),
                    'is_active' => $faker->boolean(90), // 90%の確率でアクティブ
                    'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                    'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                ];

                $entity = $usersTable->newEntity(
                    $userData,
                    ['accessibleFields' => ['id' => true]]
                );
                $usersTable->saveOrFail($entity);

                // 進捗表示（10件ごと）
                if ($i % 10 === 0) {
                    $this->io->out("  → {$i} users created...");
                }
            }

            $this->io->out('✓ 100 test users created successfully with Faker');
        } else {
            $this->io->out('');
            $this->io->out('ℹ Production mode - Skipping Faker test users');
        }

        $this->io->out('');
        $this->io->out('=================================');
        $this->io->out('✓ Initial seed completed!');
        $this->io->out('=================================');
    }
}
