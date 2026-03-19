<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RolesTable;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * App\Model\Table\RolesTable Test Case
 */
class RolesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RolesTable
     */
    protected $Roles;

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
        $config = $this->getTableLocator()->exists('Roles') ? [] : ['className' => RolesTable::class];
        $this->Roles = $this->getTableLocator()->get('Roles', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Roles);

        parent::tearDown();
    }

    /**
     * Test restrictDeleteAssociations method
     *
     * @return void
     * @link \App\Model\Table\RolesTable::restrictDeleteAssociations()
     */
    public function testRestrictDeleteAssociations(): void
    {
        $this->assertInstanceOf(RolesTable::class, $this->Roles);
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\RolesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Roles->newEmptyEntity();
        $entity = $this->Roles->patchEntity($entity, [
            'display_name' => 'Test Role',
            'description' => 'Test Description',
            'created_by' => Text::uuid(),
            'modified_by' => Text::uuid(),
        ]);

        $this->assertEmpty($entity->getErrors());
    }

    /**
     * Test validationCreate method
     *
     * @return void
     * @link \App\Model\Table\RolesTable::validationCreate()
     */
    public function testValidationCreate(): void
    {
        $this->assertInstanceOf(RolesTable::class, $this->Roles);
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\RolesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertInstanceOf(RolesTable::class, $this->Roles);
    }
}
