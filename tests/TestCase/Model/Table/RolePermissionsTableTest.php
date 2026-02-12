<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RolePermissionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RolePermissionsTable Test Case
 */
class RolePermissionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RolePermissionsTable
     */
    protected $RolePermissions;

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
        $config = $this->getTableLocator()->exists('RolePermissions') ? [] : ['className' => RolePermissionsTable::class];
        $this->RolePermissions = $this->getTableLocator()->get('RolePermissions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->RolePermissions);

        parent::tearDown();
    }

    /**
     * Test restrictDeleteAssociations method
     *
     * @return void
     * @link \App\Model\Table\RolePermissionsTable::restrictDeleteAssociations()
     */
    public function testRestrictDeleteAssociations(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\RolePermissionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationCreate method
     *
     * @return void
     * @link \App\Model\Table\RolePermissionsTable::validationCreate()
     */
    public function testValidationCreate(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\RolePermissionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
