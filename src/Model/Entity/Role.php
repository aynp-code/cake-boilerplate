<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * @property string $id
 * @property string $display_name
 * @property string|null $description
 * @property \Cake\I18n\DateTime $created
 * @property string $created_by
 * @property \Cake\I18n\DateTime $modified
 * @property string $modified_by
 *
 * @property \App\Model\Entity\User $created_by_user
 * @property \App\Model\Entity\User $modified_by_user
 * @property \App\Model\Entity\RolePermission[] $role_permissions
 * @property \App\Model\Entity\User[] $users
 */
class Role extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'display_name' => true,
        'description' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'created_by_user' => true,
        'modified_by_user' => true,
        'role_permissions' => true,
        'users' => true,
    ];
}
