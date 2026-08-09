<?php

namespace Database\Seeders;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['super-admin', 'manager', 'support-specialist', 'regular-user'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName], [
                'description' => ucfirst(str_replace('-', ' ', $roleName)),
                'status' => 1,
            ]);
        }

        $permissions = ['manage.roles', 'manage.permissions', 'assign.permissions'];
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName], [
                'description' => ucfirst(str_replace('.', ' ', $permissionName)),
                'status' => 1,
            ]);
        }

        Role::where('name', 'super-admin')
            ->first()
            ->permissions()
            ->sync(Permission::all()->pluck('id'));

        Cache::forget('permissions');
    }
}
