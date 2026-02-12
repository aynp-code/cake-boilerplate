<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\RolePermissionsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\RolePermissionsController Test Case
 *
 * @link \App\Controller\RolePermissionsController
 */
class RolePermissionsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.RolePermissions',
        'app.Users',
        'app.Roles',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // テストユーザーとしてログイン
        $this->session([
            'Auth' => [
                'id' => 'a64e238c-86dd-4d28-afca-b407993cdb24',
                'username' => 'test_user',
                'role_id' => 'd72b07bd-019d-4ccb-a7f7-17f887f8fba1',
            ]
        ]);
    }

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\RolePermissionsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/role-permissions');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\RolePermissionsController::view()
     */
    public function testView(): void
    {
        $this->get('/role-permissions/view/7e322e99-4f53-4ac3-b41e-c522f4cbfd58');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\RolePermissionsController::add()
     */
    public function testAdd(): void
    {
        $this->get('/role-permissions/add');
        $this->assertResponseOk();
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\RolePermissionsController::edit()
     */
    public function testEdit(): void
    {
        $this->get('/role-permissions/edit/7e322e99-4f53-4ac3-b41e-c522f4cbfd58');
        $this->assertResponseOk();
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\RolePermissionsController::delete()
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->post('/role-permissions/delete/7e322e99-4f53-4ac3-b41e-c522f4cbfd58');
        // 削除後はリダイレクトされる
        $this->assertResponseSuccess();
    }
}
