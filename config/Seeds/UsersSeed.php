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
        $rolesTable = TableRegistry::getTableLocator()->get('Roles');
        $usersTable = TableRegistry::getTableLocator()->get('Users');

        $role = $rolesTable->find()
            ->where(['name' => 'admin'])
            ->first();

        if (!$role) {
            throw new \Exception("system admin role not found");
        }

        $user = $usersTable->newEntity([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'name' => 'System Administrator',
            'password' => 'password',
            'role_id' => $role['id'],
            'is_active' => true,
        ]);
        $usersTable->saveOrFail($user);
    }
}
