<?php
declare(strict_types=1);

use Migrations\BaseSeed;
use Cake\ORM\TableRegistry;

/**
 * Users seed.
 */
class UsersSeed extends BaseSeed
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
        $usersTable = TableRegistry::getTableLocator()->get('Users');

        $user = $usersTable->newEntity([
            'id' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'display_name' => 'System Administrator',
            'password' => 'password',
            'role_id' => 'faa5ab22-2178-4833-b8e9-4db2f023e38f',
            'is_active' => true,
            'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
        ], ['accessibleFields' => ['id' => true]]);
        $usersTable->saveOrFail($user);
    }
}
