<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
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
        $config = $this->getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = $this->getTableLocator()->get('Users', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Users);

        parent::tearDown();
    }

    /**
     * Test restrictDeleteAssociations method
     *
     * @return void
     * @link \App\Model\Table\UsersTable::restrictDeleteAssociations()
     */
    public function testRestrictDeleteAssociations(): void
    {
        $this->assertInstanceOf(UsersTable::class, $this->Users);
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\UsersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Users->newEmptyEntity();
        $entity = $this->Users->patchEntity($entity, [
            'username' => 'newtestuser',
            'email' => 'newtest@example.com',
            'password' => 'Password123!',
            'display_name' => 'New Test User',
            'role_id' => 'd72b07bd-019d-4ccb-a7f7-17f887f8fba1',
            'created_by' => '898c13d6-cde2-4eac-b8c8-70550772696b',
            'modified_by' => '898c13d6-cde2-4eac-b8c8-70550772696b',
        ]);

        $this->assertEmpty($entity->getErrors());
    }

    /**
     * Test validationCreate method
     *
     * @return void
     * @link \App\Model\Table\UsersTable::validationCreate()
     */
    public function testValidationCreate(): void
    {
        $this->assertInstanceOf(UsersTable::class, $this->Users);
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\UsersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertInstanceOf(UsersTable::class, $this->Users);
    }
}
