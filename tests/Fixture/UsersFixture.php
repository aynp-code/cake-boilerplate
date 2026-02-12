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
                'id' => 'a64e238c-86dd-4d28-afca-b407993cdb24',
                'username' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'display_name' => 'Lorem ipsum dolor sit amet',
                'email' => 'Lorem ipsum dolor sit amet',
                'role_id' => 'd72b07bd-019d-4ccb-a7f7-17f887f8fba1',
                'is_active' => 1,
                'created' => '2026-02-05 14:16:12',
                'created_by' => 'fef4c630-fb39-4e19-b87e-753bb035226d',
                'modified' => '2026-02-05 14:16:12',
                'modified_by' => 'ea404e1c-1693-4e8d-a517-d6d3f07d4b53',
            ],
        ];
        parent::init();
    }
}
