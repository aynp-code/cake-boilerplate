<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CybozuAuthsFixture
 */
class CybozuAuthsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            // テスト用サンプルレコード（access_token / refresh_token は平文で OK、
            // テスト実行時は Security::encrypt() が salt を使って暗号化します）
        ];
        parent::init();
    }
}
