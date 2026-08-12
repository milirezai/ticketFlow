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

class TicketPriorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['ticket.priority.create', 'ticket.priority.update', 'ticket.priority.delete'] as $name) {
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

    public function test_user_with_permission_can_create_priority(): void
    {
        $user = $this->userWithPermission('ticket.priority.create');
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-priorities.store'), ['name' => 'high'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'high')
            ->assertJsonPath('data.slug', 'high');
        $this->assertDatabaseHas('ticket_priorities', ['name' => 'high', 'slug' => 'high']);
    }

    public function test_user_with_permission_can_update_priority(): void
    {
        $user = $this->userWithPermission('ticket.priority.update');
        $priority = TicketPriority::factory()->create(['name' => 'medium']);
        Sanctum::actingAs($user);
        $this->patchJson(route('ticket-priorities.update', [$priority->id]), ['name' => 'high'])
            ->assertOk()
            ->assertJsonPath('data.name', 'high');
        $this->assertDatabaseHas('ticket_priorities', ['id' => $priority->id, 'name' => 'high']);
    }

    public function test_user_with_permission_can_update_priority_keeping_its_own_slug(): void
    {
        $user = $this->userWithPermission('ticket.priority.update');
        $priority = TicketPriority::factory()->create(['name' => 'medium']);
        Sanctum::actingAs($user);
        $this->patchJson(route('ticket-priorities.update', [$priority->id]), [
            'name' => 'medium',
            'slug' => $priority->slug,
        ])->assertOk();
        $this->assertDatabaseHas('ticket_priorities', ['id' => $priority->id, 'slug' => $priority->slug]);
    }

    public function test_user_with_permission_can_delete_priority(): void
    {
        $user = $this->userWithPermission('ticket.priority.delete');
        $priority = TicketPriority::factory()->create();
        Sanctum::actingAs($user);
        $this->deleteJson(route('ticket-priorities.destroy', [$priority->id]))->assertStatus(204);
        $this->assertSoftDeleted('ticket_priorities', ['id' => $priority->id]);
    }

    public function test_cannot_delete_priority_referenced_by_tickets(): void
    {
        $user = $this->userWithPermission('ticket.priority.delete');
        $priority = TicketPriority::factory()->create();
        Ticket::create([
            'subject' => 'Test subject',
            'user_id' => User::factory()->create()->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => $priority->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
        Sanctum::actingAs($user);
        $this->deleteJson(route('ticket-priorities.destroy', [$priority->id]))->assertStatus(409);
        $this->assertDatabaseHas('ticket_priorities', ['id' => $priority->id]);
    }

    public function test_validation_requires_name(): void
    {
        $user = $this->userWithPermission('ticket.priority.create');
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-priorities.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_validation_rejects_duplicate_slug(): void
    {
        $user = $this->userWithPermission('ticket.priority.create');
        TicketPriority::factory()->create(['slug' => 'high']);
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-priorities.store'), [
            'name' => 'high',
            'slug' => 'high',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_user_without_permission_cannot_create_priority(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('ticket-priorities.store'), ['name' => 'high'])->assertStatus(403);
    }

    public function test_user_without_permission_cannot_update_priority(): void
    {
        $priority = TicketPriority::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->patchJson(route('ticket-priorities.update', [$priority->id]), ['name' => 'high'])->assertStatus(403);
    }

    public function test_user_without_permission_cannot_delete_priority(): void
    {
        $priority = TicketPriority::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson(route('ticket-priorities.destroy', [$priority->id]))->assertStatus(403);
    }

    public function test_authenticated_user_can_index_priorities(): void
    {
        TicketPriority::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('ticket-priorities.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_show_priority(): void
    {
        $priority = TicketPriority::factory()->create(['name' => 'high']);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('ticket-priorities.show', [$priority->id]))
            ->assertOk()
            ->assertJsonPath('data.name', 'high');
    }
}
