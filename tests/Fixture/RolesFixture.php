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
                'id' => 'cd5c0c5a-7744-462c-9d37-70d7b33323eb',
                'display_name' => 'Lorem ipsum dolor sit amet',
                'description' => 'Lorem ipsum dolor sit amet',
                'created' => '2026-02-05 01:34:14',
                'created_by' => '7c837bd5-467f-43e4-81a3-cd5c5fa7dc69',
                'modified' => '2026-02-05 01:34:14',
                'modified_by' => '95c72933-6a61-4bb7-8253-5e06f4ff0af1',
            ],
        ];
        parent::init();
    }
}
