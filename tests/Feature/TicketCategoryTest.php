<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketCategoryTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['ticket.category.create', 'ticket.category.update', 'ticket.category.delete'] as $name) {
            Permission::create(['name' => $name, 'description' => $name, 'status' => 1]);
            Gate::define($name, fn(User $user) => $user->hasPermissionTo($name));
        }
    }

    private function userWithPermission(string $name): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', $name)->first());
        return $user;
    }

    public function test_user_with_permission_can_create_category(): void
    {
        $user = $this->userWithPermission('ticket.category.create');
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-categories.store'), ['name' => 'Technical'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Technical')
            ->assertJsonPath('data.slug', 'technical');
        $this->assertDatabaseHas('ticket_categories', ['name' => 'Technical', 'slug' => 'technical']);
    }

    public function test_user_with_permission_can_update_category(): void
    {
        $user = $this->userWithPermission('ticket.category.update');
        $category = TicketCategory::factory()->create(['name' => 'Technical']);
        Sanctum::actingAs($user);
        $this->patchJson(route('ticket-categories.update', [$category->id]), ['name' => 'Payment'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Payment');
        $this->assertDatabaseHas('ticket_categories', ['id' => $category->id, 'name' => 'Payment']);
    }

    public function test_user_with_permission_can_update_category_keeping_its_own_slug(): void
    {
        $user = $this->userWithPermission('ticket.category.update');
        $category = TicketCategory::factory()->create(['name' => 'Technical']);
        Sanctum::actingAs($user);
        $this->patchJson(route('ticket-categories.update', [$category->id]), [
            'name' => 'Technical',
            'slug' => $category->slug,
        ])->assertOk();
        $this->assertDatabaseHas('ticket_categories', ['id' => $category->id, 'slug' => $category->slug]);
    }

    public function test_user_with_permission_can_delete_category(): void
    {
        $user = $this->userWithPermission('ticket.category.delete');
        $category = TicketCategory::factory()->create();
        Sanctum::actingAs($user);
        $this->deleteJson(route('ticket-categories.destroy', [$category->id]))->assertStatus(204);
        $this->assertSoftDeleted('ticket_categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_referenced_by_tickets(): void
    {
        $user = $this->userWithPermission('ticket.category.delete');
        $category = TicketCategory::factory()->create();
        Ticket::create([
            'subject' => 'Test subject',
            'user_id' => User::factory()->create()->id,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
        Sanctum::actingAs($user);
        $this->deleteJson(route('ticket-categories.destroy', [$category->id]))->assertStatus(409);
        $this->assertDatabaseHas('ticket_categories', ['id' => $category->id]);
    }

    public function test_validation_requires_name(): void
    {
        $user = $this->userWithPermission('ticket.category.create');
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-categories.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_validation_rejects_duplicate_slug(): void
    {
        $user = $this->userWithPermission('ticket.category.create');
        TicketCategory::factory()->create(['slug' => 'technical']);
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-categories.store'), [
            'name' => 'Technical',
            'slug' => 'technical',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_user_without_permission_cannot_create_category(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('ticket-categories.store'), ['name' => 'Technical'])->assertStatus(403);
    }

    public function test_user_without_permission_cannot_update_category(): void
    {
        $category = TicketCategory::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->patchJson(route('ticket-categories.update', [$category->id]), ['name' => 'Payment'])->assertStatus(403);
    }

    public function test_user_without_permission_cannot_delete_category(): void
    {
        $category = TicketCategory::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson(route('ticket-categories.destroy', [$category->id]))->assertStatus(403);
    }

    public function test_authenticated_user_can_index_categories(): void
    {
        TicketCategory::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('ticket-categories.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_show_category(): void
    {
        $category = TicketCategory::factory()->create(['name' => 'Technical']);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('ticket-categories.show', [$category->id]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Technical');
    }
}
