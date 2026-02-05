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
                'id' => '80177e89-fd74-4c9b-bdc3-b99679ec7354',
                'role_id' => '50bc1d92-dbd2-413a-b1c2-ea07cc24b5a8',
                'plugin' => 'Lorem ipsum dolor sit amet',
                'prefix' => 'Lorem ipsum dolor sit amet',
                'controller' => 'Lorem ipsum dolor sit amet',
                'action' => 'Lorem ipsum dolor sit amet',
                'allowed' => 1,
                'created' => '2026-02-05 02:17:28',
                'created_by' => 'bfcc6876-fc83-4153-a6b2-ef935446930d',
                'modified' => '2026-02-05 02:17:28',
                'modified_by' => '4a331188-1a0a-4de7-9f0c-a2002f8ee58b',
            ],
        ];
        parent::init();
    }
}
