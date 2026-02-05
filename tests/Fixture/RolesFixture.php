<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolesFixture
 */
class RolesFixture extends TestFixture
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
                'id' => 'd72b07bd-019d-4ccb-a7f7-17f887f8fba1',
                'display_name' => 'Lorem ipsum dolor sit amet',
                'description' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-02-05 14:19:57',
                'created_by' => '43ac863a-db3b-4ed8-93d5-3e7ecfecbf14',
                'modified' => '2026-02-05 14:19:57',
                'modified_by' => 'e29e19c0-0512-442a-9d4e-d1cc07b052e2',
            ],
        ];
        parent::init();
    }
}
