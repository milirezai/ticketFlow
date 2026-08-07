<?php

namespace App\Models\Access\Concerns;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRolesAndPermissions
{
    protected ?Collection $allPermissionsCache = null;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->where('roles.status',1);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->where('permissions.status',1);
    }

    public function allPermissions(): Collection
    {
        if ($this->allPermissionsCache !== null) {
            return $this->allPermissionsCache;
        }

        return $this->allPermissionsCache = $this->roles->flatMap->permissions
            ->concat($this->permissions)
            ->where('status', 1)
            ->unique('name')
            ->values();
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return $this->roles->contains(function (Role $role) {
            return  in_array($role->name, $roles, true);
        });
    }

    public function hasPermission(string|array $permissions): bool
    {
        $permissions = (array) $permissions;
        return $this->allPermissions()->contains(function (Permission $permission) {
            return in_array($permission->name, $permissions, true);
        });
    }

    public function hasAllPermissions(array $permissions): bool
    {
        return $this->allPermissions()->pluck('name')->flip()->has($permissions);
    }
}
