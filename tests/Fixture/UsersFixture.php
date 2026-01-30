<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
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
                'id' => '44c1b5c9-402e-4b63-992a-4cd548a2ce2e',
                'username' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'name' => 'Lorem ipsum dolor sit amet',
                'email' => 'Lorem ipsum dolor sit amet',
                'role_id' => 'a984c9e9-e0a1-44ff-b46a-a44d8a7096f5',
                'is_active' => 1,
                'created' => '2026-01-30 05:04:24',
                'created_by' => '90056d50-791b-45d3-808e-01b017d73b78',
                'modified' => '2026-01-30 05:04:24',
                'modified_by' => '34996435-8521-4d3a-af8c-c09cba2a582c',
            ],
        ];
        parent::init();
    }
}
