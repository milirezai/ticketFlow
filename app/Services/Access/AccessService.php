<?php

namespace App\Services\Access;

use App\Models\Access\Role;
use App\Models\User\User;

class AccessService
{
    public function createRole(array $data, array $permissionIds = []): Role
    {
        $role = Role::create($data);
        $this->syncRolePermissions($role, $permissionIds);
        return $role;
    }

    public function updateRole(Role $role, array $data, ?array $permissionIds = null): Role
    {
        $role->update($data);
        if ($permissionIds) {
            $this->syncRolePermissions($role, $permissionIds);
        }
        return $role;
    }

    public function syncRolePermissions(Role $role, array $permissionIds = []): void
    {
        $role->permissions()->sync($permissionIds);
    }

    public function assignRolesToUser(User $user, array $roleIds = []): void
    {
        $user->roles()->sync($roleIds);
    }

    public function assignPermissionsToUser(User $user, array $permissionIds = []): void
    {
        $user->permissions()->sync($permissionIds);
    }
}
