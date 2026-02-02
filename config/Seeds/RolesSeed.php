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
            [
                'id' => 'faa5ab22-2178-4833-b8e9-4db2f023e38f',
                'display_name' => 'admin',
                'description' => 'Has access to most system features.',
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
            [
                'id' => 'cb5f91fc-df15-422b-9d4a-f87fda893b77',
                'display_name' => 'user',
                'description' => 'Standard user role.',
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
            [
                'id' => '2b072d8e-f9a7-4e79-9e6d-2bfbe04a2074',
                'display_name' => 'guest',
                'description' => 'Limited access role.',
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ],
        ];

        $rolesTable = TableRegistry::getTableLocator()->get('Roles');
        foreach ($roles as $roleData) {
            $entity = $rolesTable->newEntity(
                $roleData,
                ['accessibleFields' => ['id' => true]]
            );
            $rolesTable->saveOrFail($entity);
        }
    }
}
