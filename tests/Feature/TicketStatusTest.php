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

class TicketStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['ticket.status.create', 'ticket.status.update', 'ticket.status.delete'] as $name) {
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

    public function test_user_with_permission_can_create_status(): void
    {
        $user = $this->userWithPermission('ticket.status.create');
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-statuses.store'), ['name' => 'open'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'open')
            ->assertJsonPath('data.slug', 'open');
        $this->assertDatabaseHas('ticket_statuses', ['name' => 'open', 'slug' => 'open']);
    }

    public function test_user_with_permission_can_update_status(): void
    {
        $user = $this->userWithPermission('ticket.status.update');
        $status = TicketStatus::factory()->create(['name' => 'open']);
        Sanctum::actingAs($user);
        $this->patchJson(route('ticket-statuses.update', [$status->id]), ['name' => 'closed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'closed');
        $this->assertDatabaseHas('ticket_statuses', ['id' => $status->id, 'name' => 'closed']);
    }

    public function test_user_with_permission_can_update_status_keeping_its_own_slug(): void
    {
        $user = $this->userWithPermission('ticket.status.update');
        $status = TicketStatus::factory()->create(['name' => 'open']);
        Sanctum::actingAs($user);
        $this->patchJson(route('ticket-statuses.update', [$status->id]), [
            'name' => 'open',
            'slug' => $status->slug,
        ])->assertOk();
        $this->assertDatabaseHas('ticket_statuses', ['id' => $status->id, 'slug' => $status->slug]);
    }

    public function test_user_with_permission_can_delete_status(): void
    {
        $user = $this->userWithPermission('ticket.status.delete');
        $status = TicketStatus::factory()->create();
        Sanctum::actingAs($user);
        $this->deleteJson(route('ticket-statuses.destroy', [$status->id]))->assertStatus(204);
        $this->assertSoftDeleted('ticket_statuses', ['id' => $status->id]);
    }

    public function test_cannot_delete_status_referenced_by_tickets(): void
    {
        $user = $this->userWithPermission('ticket.status.delete');
        $status = TicketStatus::factory()->create();
        Ticket::create([
            'subject' => 'Test subject',
            'user_id' => User::factory()->create()->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => $status->id,
        ]);
        Sanctum::actingAs($user);
        $this->deleteJson(route('ticket-statuses.destroy', [$status->id]))->assertStatus(409);
        $this->assertDatabaseHas('ticket_statuses', ['id' => $status->id]);
    }

    public function test_validation_requires_name(): void
    {
        $user = $this->userWithPermission('ticket.status.create');
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-statuses.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_validation_rejects_duplicate_slug(): void
    {
        $user = $this->userWithPermission('ticket.status.create');
        TicketStatus::factory()->create(['slug' => 'open']);
        Sanctum::actingAs($user);
        $this->postJson(route('ticket-statuses.store'), [
            'name' => 'open',
            'slug' => 'open',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_user_without_permission_cannot_create_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('ticket-statuses.store'), ['name' => 'open'])->assertStatus(403);
    }

    public function test_user_without_permission_cannot_update_status(): void
    {
        $status = TicketStatus::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->patchJson(route('ticket-statuses.update', [$status->id]), ['name' => 'closed'])->assertStatus(403);
    }

    public function test_user_without_permission_cannot_delete_status(): void
    {
        $status = TicketStatus::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson(route('ticket-statuses.destroy', [$status->id]))->assertStatus(403);
    }

    public function test_authenticated_user_can_index_statuses(): void
    {
        TicketStatus::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('ticket-statuses.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_show_status(): void
    {
        $status = TicketStatus::factory()->create(['name' => 'open']);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('ticket-statuses.show', [$status->id]))
            ->assertOk()
            ->assertJsonPath('data.name', 'open');
    }
}
