<?php
declare(strict_types=1);

namespace App\Service;

interface RolePermissionCheckerInterface
{
    /**
     * @param array{plugin:?string,prefix:?string,controller:string,action:string} $target
     */
    public function can(string $roleId, array $target): bool;
}
