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
                'id' => '7e322e99-4f53-4ac3-b41e-c522f4cbfd58',
                'role_id' => '3052638e-e46d-4d74-b7aa-56d32cb2231c',
                'plugin' => 'Lorem ipsum dolor sit amet',
                'prefix' => 'Lorem ipsum dolor sit amet',
                'controller' => 'Lorem ipsum dolor sit amet',
                'action' => 'Lorem ipsum dolor sit amet',
                'allowed' => 1,
                'created' => '2026-02-05 14:20:37',
                'created_by' => '898c13d6-cde2-4eac-b8c8-70550772696b',
                'modified' => '2026-02-05 14:20:37',
                'modified_by' => '2a469635-f97b-474a-9f04-933e539562f5',
            ],
        ];
        parent::init();
    }
}
