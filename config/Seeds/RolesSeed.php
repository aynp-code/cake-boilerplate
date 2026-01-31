<?php
declare(strict_types=1);

use Migrations\BaseSeed;
use Cake\ORM\TableRegistry;

/**
 * Roles seed.
 */
class RolesSeed extends BaseSeed
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
        $roles = [
            ['name' => 'admin', 'description' => 'Has access to most system features.'],
            ['name' => 'user', 'description' => 'Standard user role.'],
            ['name' => 'guest', 'description' => 'Limited access role.'],
        ];

        $rolesTable = TableRegistry::getTableLocator()->get('Roles');
        foreach ($roles as $roleData) {
            $entity = $rolesTable->newEntity($roleData);
            $rolesTable->saveOrFail($entity);
        }
    }
}
