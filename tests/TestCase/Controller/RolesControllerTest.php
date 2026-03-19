<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\RolesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\RolesController Test Case
 *
 * @link \App\Controller\RolesController
 */
class RolesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Roles',
        'app.RolePermissions',
        'app.Users',
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
     * @link \App\Controller\RolesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/roles');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\RolesController::view()
     */
    public function testView(): void
    {
        $this->get('/roles/view/d72b07bd-019d-4ccb-a7f7-17f887f8fba1');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\RolesController::add()
     */
    public function testAdd(): void
    {
        $this->get('/roles/add');
        $this->assertResponseOk();
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\RolesController::edit()
     */
    public function testEdit(): void
    {
        $this->get('/roles/edit/d72b07bd-019d-4ccb-a7f7-17f887f8fba1');
        $this->assertResponseOk();
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\RolesController::delete()
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->post('/roles/delete/ffffffff-ffff-ffff-ffff-ffffffffffff');
        $this->assertResponseSuccess();
    }
}