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
                'id' => '4920d99b-fd02-4ccf-bcb3-d9435fc8aa5c',
                'username' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'name' => 'Lorem ipsum dolor sit amet',
                'email' => 'Lorem ipsum dolor sit amet',
                'role_id' => '603d254b-3319-4944-822d-a0d189cf8365',
                'is_active' => 1,
                'created' => '2026-02-02 06:12:08',
                'created_by' => '7dcbb1fa-3c48-4fed-bac7-39c7f1bc0d4e',
                'modified' => '2026-02-02 06:12:08',
                'modified_by' => '2ec57093-6086-483b-8c36-aff034c92b00',
            ],
        ];
        parent::init();
    }
}
