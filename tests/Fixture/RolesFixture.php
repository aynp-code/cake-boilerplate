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
                'id' => 'c21893b5-afef-453e-af93-0f4311769fc7',
                'name' => 'Lorem ipsum dolor sit amet',
                'description' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-01-30 05:04:19',
                'created_by' => 'b55a563f-b772-4df4-bb7d-bbcc9f879174',
                'modified' => '2026-01-30 05:04:19',
                'modified_by' => '498c8904-8d5c-4d54-aaeb-2b9c00f84c80',
            ],
        ];
        parent::init();
    }
}
