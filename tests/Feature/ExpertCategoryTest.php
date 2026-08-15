<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Ticket\TicketCategory;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpertCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'expert.manage', 'description' => 'expert.manage', 'status' => 1]);
        Gate::define('expert.manage', fn(User $user) => $user->hasPermissionTo('expert.manage'));
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'expert.manage')->first());
        return $user;
    }

    private function expert(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::factory()->create(['name' => 'expert']));
        return $user;
    }

    public function test_manager_can_sync_categories_to_expert(): void
    {
        $manager = $this->manager();
        $expert = $this->expert();
        $categories = TicketCategory::factory()->count(2)->create();
        Sanctum::actingAs($manager);
        $this->postJson(route('experts.syncCategories', [$expert->id]), [
            'category_ids' => $categories->pluck('id')->all(),
        ])->assertOk();
        foreach ($categories as $category) {
            $this->assertDatabaseHas('expert_categories', [
                'user_id' => $expert->id,
                'ticket_category_id' => $category->id,
            ]);
        }
    }

    public function test_sync_replaces_previous_categories(): void
    {
        $manager = $this->manager();
        $expert = $this->expert();
        $old = TicketCategory::factory()->create();
        $new = TicketCategory::factory()->count(2)->create();
        $expert->expertCategories()->attach($old->id);
        Sanctum::actingAs($manager);
        $this->postJson(route('experts.syncCategories', [$expert->id]), [
            'category_ids' => $new->pluck('id')->all(),
        ])->assertOk();
        $this->assertDatabaseMissing('expert_categories', [
            'user_id' => $expert->id,
            'ticket_category_id' => $old->id,
        ]);
        foreach ($new as $category) {
            $this->assertDatabaseHas('expert_categories', [
                'user_id' => $expert->id,
                'ticket_category_id' => $category->id,
            ]);
        }
    }

    public function test_validation_requires_category_ids(): void
    {
        Sanctum::actingAs($this->manager());
        $this->postJson(route('experts.syncCategories', [$this->expert()->id]), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_ids');
    }

    public function test_validation_rejects_invalid_category_id(): void
    {
        Sanctum::actingAs($this->manager());
        $this->postJson(route('experts.syncCategories', [$this->expert()->id]), ['category_ids' => [999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_ids.0');
    }

    public function test_regular_user_cannot_sync_categories(): void
    {
        $category = TicketCategory::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('experts.syncCategories', [$this->expert()->id]), ['category_ids' => [$category->id]])
            ->assertStatus(403);
    }

    public function test_expert_cannot_sync_own_categories(): void
    {
        $expert = $this->expert();
        $category = TicketCategory::factory()->create();
        Sanctum::actingAs($expert);
        $this->postJson(route('experts.syncCategories', [$expert->id]), ['category_ids' => [$category->id]])
            ->assertStatus(403);
    }

    public function test_manager_can_view_expert_categories(): void
    {
        $expert = $this->expert();
        $category = TicketCategory::factory()->create();
        $expert->expertCategories()->attach($category->id);
        Sanctum::actingAs($this->manager());
        $this->getJson(route('experts.categories', [$expert->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $category->id);
    }

    public function test_expert_can_view_own_categories(): void
    {
        $expert = $this->expert();
        TicketCategory::factory()->count(2)->create()
            ->each(fn(TicketCategory $category) => $expert->expertCategories()->attach($category->id));
        Sanctum::actingAs($expert);
        $this->getJson(route('experts.categories', [$expert->id]))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_regular_user_cannot_view_expert_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('experts.categories', [$this->expert()->id]))->assertStatus(403);
    }

    public function test_experts_index_returns_only_expert_role_users(): void
    {
        $this->expert();
        User::factory()->count(3)->create();
        Sanctum::actingAs($this->manager());
        $this->getJson(route('experts.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
