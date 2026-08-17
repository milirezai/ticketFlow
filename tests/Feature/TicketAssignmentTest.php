<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'expert.manage', 'description' => 'expert.manage', 'status' => 1]);
        Gate::define('expert.manage', fn(User $user) => $user->hasPermissionTo('expert.manage'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role), 'status' => 1]));
        return $user;
    }

    private function expert(TicketCategory $category): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'expert'], ['description' => 'Expert', 'status' => 1]));
        $user->expertCategories()->attach($category->id);
        return $user;
    }

    private function makeTicket(TicketCategory $category, ?User $expert = null): Ticket
    {
        return Ticket::create([
            'subject' => 'Test subject',
            'user_id' => User::factory()->create()->id,
            'assigned_to' => $expert?->id,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
    }

    public function test_support_specialist_can_assign_ticket_to_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', [$expert->id]), ['ticket_id' => $ticket->id])
            ->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expert->id]);
    }

    public function test_reassign_changes_the_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->expert($category);
        $expertB = $this->expert($category);
        $ticket = $this->makeTicket($category, $expertA);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', [$expertB->id]), ['ticket_id' => $ticket->id])
            ->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expertB->id]);
    }

    public function test_cannot_assign_to_non_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $ticket = $this->makeTicket($category);
        $nonExpert = User::factory()->create();
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', [$nonExpert->id]), ['ticket_id' => $ticket->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_cannot_assign_to_expert_outside_ticket_category(): void
    {
        $category = TicketCategory::factory()->create();
        $otherCategory = TicketCategory::factory()->create();
        $expert = $this->expert($otherCategory);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', [$expert->id]), ['ticket_id' => $ticket->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_regular_user_cannot_assign(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('experts.assign', [$expert->id]), ['ticket_id' => $ticket->id])
            ->assertStatus(403);
    }

    public function test_ticket_owner_cannot_assign(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $owner = User::factory()->create();
        $ticket = Ticket::create([
            'subject' => 'Test subject',
            'user_id' => $owner->id,
            'assigned_to' => null,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
        Sanctum::actingAs($owner);
        $this->postJson(route('experts.assign', [$expert->id]), ['ticket_id' => $ticket->id])
            ->assertStatus(403);
    }

    public function test_validation_requires_ticket_id(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', [$expert->id]), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_cannot_assign_to_nonexistent_ticket(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', [$expert->id]), ['ticket_id' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_expert_can_fetch_own_tickets(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $assigned = $this->makeTicket($category, $expert);
        $this->makeTicket($category);
        Sanctum::actingAs($expert);
        $this->getJson(route('experts.tickets', [$expert->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', $assigned->subject);
    }

    public function test_support_specialist_can_view_expert_tickets(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $this->makeTicket($category, $expert);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->getJson(route('experts.tickets', [$expert->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_regular_user_cannot_view_expert_tickets(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('experts.tickets', [$expert->id]))->assertStatus(403);
    }
}
