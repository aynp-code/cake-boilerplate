<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolePermissionsFixture
 */
class RolePermissionsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '57078280-23c7-4648-939d-117fe8502b42',
                'role_id' => 'e967ccb2-3102-428f-9360-75713f2ab078',
                'plugin' => 'Lorem ipsum dolor sit amet',
                'prefix' => 'Lorem ipsum dolor sit amet',
                'controller' => 'Lorem ipsum dolor sit amet',
                'action' => 'Lorem ipsum dolor sit amet',
                'allowed' => 1,
                'created' => '2026-02-04 05:26:11',
                'created_by' => 'cedc9416-2995-487e-863e-3237b00a0b02',
                'modified' => '2026-02-04 05:26:11',
                'modified_by' => '40c42b70-793d-4fcd-b802-ec4c3b1d9dfc',
            ],
        ];
        parent::init();
    }
}
