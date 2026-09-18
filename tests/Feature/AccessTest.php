<?php

namespace Tests\Feature;

use App\Models\Access\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['manage.roles', 'manage.permissions', 'assign.permissions'] as $permission) {
            $this->definePermissionGate($permission);
        }
    }

    public function test_user_with_permission_can_index_roles(): void
    {
        $this->createRole('admin');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->getJson(route('access.roles.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'admin')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_user_with_permission_can_create_role(): void
    {
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->postJson(route('access.roles.store'), [
            'name' => 'support',
            'description' => 'Support specialists',
            'status' => 1,
        ])->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'status', 'permissions', 'created_at'],
            ]);
        $this->assertDatabaseHas('roles', ['name' => 'support', 'description' => 'Support specialists', 'status' => 1]);
    }

    public function test_user_with_permission_can_create_role_with_permissions(): void
    {
        $permission = $this->createPermission('manage.tickets');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->postJson(route('access.roles.store'), [
            'name' => 'support',
            'description' => 'Support specialists',
            'status' => 1,
            'permissions' => [$permission->id],
        ])->assertCreated()
            ->assertJsonPath('data.permissions.0.name', 'manage.tickets');
        $this->assertDatabaseHas('permission_role', ['role_id' => Role::where('name', 'support')->first()->id, 'permission_id' => $permission->id]);
    }

    public function test_user_with_permission_can_show_role(): void
    {
        $role = $this->createRole('admin');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->getJson(route('access.roles.show', $role))
            ->assertOk()
            ->assertJsonPath('data.name', 'admin')
            ->assertJsonPath('data.status', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'description', 'status', 'permissions', 'created_at']]);
    }

    public function test_user_with_permission_can_update_role(): void
    {
        $role = $this->createRole('admin');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->putJson(route('access.roles.update', $role), [
            'name' => 'manager',
            'description' => 'Manages teams',
            'status' => 1,
        ])->assertOk()
            ->assertJsonPath('data.name', 'manager');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'manager', 'description' => 'Manages teams']);
    }

    public function test_updating_role_can_keep_its_own_name(): void
    {
        $role = $this->createRole('admin');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->putJson(route('access.roles.update', $role), [
            'name' => 'admin',
            'description' => 'Admin role',
            'status' => 1,
        ])->assertOk()
            ->assertJsonPath('data.name', 'admin');
    }

    public function test_user_with_permission_can_delete_role(): void
    {
        $role = $this->createRole('admin');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->deleteJson(route('access.roles.destroy', $role))->assertNoContent();
        $this->assertSoftDeleted($role);
        $this->getJson(route('access.roles.show', $role))->assertNotFound();
    }

    public function test_role_store_rejects_duplicate_name(): void
    {
        $this->createRole('admin');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->postJson(route('access.roles.store'), [
            'name' => 'admin',
            'description' => 'Duplicate',
            'status' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_role_store_requires_name_with_min_length(): void
    {
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->postJson(route('access.roles.store'), [
            'name' => 'ab',
            'description' => 'Too short',
            'status' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_role_store_rejects_non_existing_permission(): void
    {
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->postJson(route('access.roles.store'), [
            'name' => 'support',
            'description' => 'Support specialists',
            'status' => 1,
            'permissions' => [999],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0']);
    }

    public function test_sync_permissions_replaces_role_permissions(): void
    {
        $role = $this->createRole('support');
        $first = $this->createPermission('manage.tickets');
        $second = $this->createPermission('manage.users');
        $user = $this->userWithPermission('manage.roles');
        Sanctum::actingAs($user);
        $this->postJson(route('access.roles.syncPermission', $role), [
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => [$first->id, $second->id],
        ])->assertOk()
            ->assertJsonCount(2, 'data.permissions');
        $this->assertDatabaseHas('permission_role', ['role_id' => $role->id, 'permission_id' => $first->id]);
        $this->assertDatabaseHas('permission_role', ['role_id' => $role->id, 'permission_id' => $second->id]);
        $this->postJson(route('access.roles.syncPermission', $role), [
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => [$first->id],
        ])->assertOk()
            ->assertJsonCount(1, 'data.permissions');
        $this->assertDatabaseMissing('permission_role', ['role_id' => $role->id, 'permission_id' => $second->id]);
    }

    public function test_user_without_permission_cannot_manage_roles(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('access.roles.index'))->assertForbidden();
        $this->postJson(route('access.roles.store'), ['name' => 'support', 'description' => 'x'])
            ->assertForbidden();
        $this->deleteJson(route('access.roles.destroy', $this->createRole('admin')))->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_roles(): void
    {
        $this->getJson(route('access.roles.index'))->assertUnauthorized();
    }

    public function test_user_with_permission_can_create_permission(): void
    {
        $user = $this->userWithPermission('manage.permissions');
        Sanctum::actingAs($user);
        $this->postJson(route('access.permissions.store'), [
            'name' => 'manage.tickets',
            'description' => 'Manage tickets',
            'status' => 1,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'manage.tickets')
            ->assertJsonStructure(['data' => ['id', 'name', 'description', 'status', 'created_at']]);
        $this->assertDatabaseHas('permissions', ['name' => 'manage.tickets', 'status' => 1]);
    }

    public function test_user_with_permission_can_index_permissions(): void
    {
        $this->createPermission('manage.tickets');
        $user = $this->userWithPermission('manage.permissions');
        Sanctum::actingAs($user);
        $this->getJson(route('access.permissions.index'))
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['name' => 'manage.tickets']);
    }

    public function test_user_with_permission_can_show_permission(): void
    {
        $permission = $this->createPermission('manage.tickets');
        $user = $this->userWithPermission('manage.permissions');
        Sanctum::actingAs($user);
        $this->getJson(route('access.permissions.show', $permission))
            ->assertOk()
            ->assertJsonPath('data.name', 'manage.tickets');
    }

    public function test_user_with_permission_can_update_permission(): void
    {
        $permission = $this->createPermission('manage.tickets');
        $user = $this->userWithPermission('manage.permissions');
        Sanctum::actingAs($user);
        $this->putJson(route('access.permissions.update', $permission), [
            'name' => 'manage.all',
            'description' => 'Manage everything',
            'status' => 1,
        ])->assertOk()
            ->assertJsonPath('data.name', 'manage.all');
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'manage.all']);
    }

    public function test_user_with_permission_can_delete_permission(): void
    {
        $permission = $this->createPermission('manage.tickets');
        $user = $this->userWithPermission('manage.permissions');
        Sanctum::actingAs($user);
        $this->deleteJson(route('access.permissions.destroy', $permission))->assertNoContent();
        $this->assertSoftDeleted($permission);
    }

    public function test_permission_store_rejects_duplicate_name(): void
    {
        $this->createPermission('manage.tickets');
        $user = $this->userWithPermission('manage.permissions');
        Sanctum::actingAs($user);
        $this->postJson(route('access.permissions.store'), [
            'name' => 'manage.tickets',
            'description' => 'Duplicate',
            'status' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_without_permission_cannot_manage_permissions(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('access.permissions.index'))->assertForbidden();
        $this->postJson(route('access.permissions.store'), ['name' => 'manage.tickets', 'description' => 'x'])
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_permissions(): void
    {
        $this->getJson(route('access.permissions.index'))->assertUnauthorized();
    }

    public function test_user_with_permission_can_assign_roles_to_user(): void
    {
        $support = $this->createRole('support');
        $manager = $this->createRole('manager');
        $target = User::factory()->create();
        $user = $this->userWithPermission('assign.permissions');
        Sanctum::actingAs($user);
        $this->postJson(route('access.assignRoles', $target), ['role_ids' => [$support->id, $manager->id]])
            ->assertOk()
            ->assertJsonCount(2, 'data.roles')
            ->assertJsonPath('data.roles.0.name', 'support');
        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role_id' => $support->id]);
        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role_id' => $manager->id]);
    }

    public function test_assign_roles_replaces_existing_roles(): void
    {
        $support = $this->createRole('support');
        $manager = $this->createRole('manager');
        $target = User::factory()->create();
        $target->roles()->attach($manager);
        $user = $this->userWithPermission('assign.permissions');
        Sanctum::actingAs($user);
        $this->postJson(route('access.assignRoles', $target), ['role_ids' => [$support->id]])->assertOk();
        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role_id' => $support->id]);
        $this->assertDatabaseMissing('role_user', ['user_id' => $target->id, 'role_id' => $manager->id]);
    }

    public function test_user_with_permission_can_assign_permissions_to_user(): void
    {
        $first = $this->createPermission('manage.tickets');
        $second = $this->createPermission('manage.users');
        $target = User::factory()->create();
        $user = $this->userWithPermission('assign.permissions');
        Sanctum::actingAs($user);
        $this->postJson(route('access.assignPermissions', $target), ['permission_ids' => [$first->id, $second->id]])
            ->assertOk();
        $this->assertDatabaseHas('permission_user', ['user_id' => $target->id, 'permission_id' => $first->id]);
        $this->assertDatabaseHas('permission_user', ['user_id' => $target->id, 'permission_id' => $second->id]);
    }

    public function test_assign_roles_rejects_non_existing_role(): void
    {
        $target = User::factory()->create();
        $user = $this->userWithPermission('assign.permissions');
        Sanctum::actingAs($user);
        $this->postJson(route('access.assignRoles', $target), ['role_ids' => [999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_ids.0']);
    }

    public function test_assign_permissions_rejects_non_existing_permission(): void
    {
        $target = User::factory()->create();
        $user = $this->userWithPermission('assign.permissions');
        Sanctum::actingAs($user);
        $this->postJson(route('access.assignPermissions', $target), ['permission_ids' => [999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permission_ids.0']);
    }

    public function test_user_without_permission_cannot_assign_access(): void
    {
        $target = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('access.assignRoles', $target), ['role_ids' => [1]])->assertForbidden();
        $this->postJson(route('access.assignPermissions', $target), ['permission_ids' => [1]])->assertForbidden();
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->createRole('super-admin'));
        Sanctum::actingAs($user);
        $this->getJson(route('access.roles.index'))->assertOk();
        $this->postJson(route('access.permissions.store'), [
            'name' => 'manage.tickets',
            'description' => 'Manage tickets',
            'status' => 1,
        ])->assertCreated();
    }
}
