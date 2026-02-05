<?php
declare(strict_types=1);

use Migrations\BaseSeed;
use Cake\ORM\TableRegistry;

/**
 * Initial seed.
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
        $data = [
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
        $model = TableRegistry::getTableLocator()->get('Roles');
        foreach ($data as $record) {
            $entity = $model->newEntity(
                $record,
                ['accessibleFields' => ['id' => true]]
            );
            $model->saveOrFail($entity);
        }

        $data = [
            [
                'role_id' => 'faa5ab22-2178-4833-b8e9-4db2f023e38f',
                'plugin' => null,
                'prefix' => null,
                'controller' => '*',
                'action' => '*',
                'allowed' => 1,
                'created_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
                'modified_by' => '61b448bd-7611-4a2a-ab1f-fa35319465ac',
            ]               
        ];
        $model = TableRegistry::getTableLocator()->get('RolePermissions');
        foreach ($data as $record) {
            $entity = $model->newEntity($record);
            $model->saveOrFail($entity);
        }


        $data = [
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
        $model = TableRegistry::getTableLocator()->get('Users');
        foreach ($data as $record) {
            $entity = $model->newEntity(
                $record,
                ['accessibleFields' => ['id' => true]]
            );
            $model->saveOrFail($entity);
        }
    }
}
