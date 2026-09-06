<?php

namespace Tests\Concerns;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Ticket\TicketCategory;
use App\Models\User\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

trait CreatesUsers
{
    protected function createRole(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'description' => ucfirst($name), 'status' => 1]);
    }

    protected function createPermission(string $name): Permission
    {
        return Permission::firstOrCreate(['name' => $name, 'description' => $name, 'status' => 1]);
    }

    protected function definePermissionGate(string $name): Permission
    {
        $permission = $this->createPermission($name);
        Gate::define($name, fn(User $user) => $user->hasPermissionTo($name));
        return $permission;
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->createRole($role));
        return $user;
    }

    protected function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach($this->definePermissionGate($permission));
        return $user;
    }

    protected function makeExpert(?TicketCategory $category = null): User
    {
        $user = $this->userWithRole('expert');
        if ($category) {
            $user->expertCategories()->attach($category->id);
        }
        return $user;
    }

    protected function forgetPermissionsCache(): void
    {
        Cache::forget('permissions');
    }
}
