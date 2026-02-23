<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property string $id
 * @property string $username
 * @property string $password
 * @property string $display_name
 * @property string $email
 * @property string|null $kintone_username
 * @property string $role_id
 * @property bool $is_active
 * @property \Cake\I18n\DateTime $created
 * @property string $created_by
 * @property \Cake\I18n\DateTime $modified
 * @property string $modified_by
 *
 * @property \App\Model\Entity\User $created_by_user
 * @property \App\Model\Entity\User $modified_by_user
 * @property \App\Model\Entity\Role $role
 */
class User extends Entity
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
        'username' => true,
        'password' => true,
        'display_name' => true,
        'email' => true,
        'kintone_username' => true,
        'role_id' => true,
        'is_active' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'created_by_user' => true,
        'modified_by_user' => true,
        'role' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
    ];

    protected function _setPassword($password)
    {
        if ($password === null || $password === '') {
            return $password;
        }
        return (new \Authentication\PasswordHasher\DefaultPasswordHasher())->hash($password);
    }
}
