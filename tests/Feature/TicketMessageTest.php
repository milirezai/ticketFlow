<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketMessage;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['conversation.viewAny', 'conversation.view', 'conversation.create', 'conversation.update', 'conversation.delete'] as $name) {
            Permission::create(['name' => $name, 'description' => $name, 'status' => 1]);
        }
        foreach (['conversation.viewAny', 'conversation.view', 'conversation.create', 'conversation.update', 'conversation.delete'] as $name) {
            Gate::define($name, fn(User $user) => $user->hasPermissionTo($name));
        }
    }

    private function makeTicketFor(User $user, ?User $assignedTo = null): Ticket
    {
        return Ticket::create([
            'subject' => 'Test subject',
            'user_id' => $user->id,
            'assigned_to' => $assignedTo?->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
    }

    private function userWithPermission(string $name): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', $name)->first());
        return $user;
    }

    public function test_owner_can_post_a_message(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'Hello there'])
            ->assertStatus(201)
            ->assertJsonPath('data.content', 'Hello there')
            ->assertJsonPath('data.status', true);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'content' => 'Hello there',
        ]);
    }

    public function test_user_with_conversation_create_permission_can_post(): void
    {
        $support = $this->userWithPermission('conversation.create');
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner, $support);
        Sanctum::actingAs($support);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'Reply from support'])
            ->assertStatus(201);
    }

    public function test_random_user_cannot_post_a_message(): void
    {
        $random = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        Sanctum::actingAs($random);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_user_id_sent_in_payload_is_ignored(): void
    {
        $owner = User::factory()->create();
        $hacker = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'Hello', 'user_id' => $hacker->id])
            ->assertStatus(201);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_owner_can_list_ticket_messages(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        TicketMessage::factory()->count(3)->for($ticket)->for($owner)->create();
        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/tickets/{$ticket->id}/messages")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_assigned_supporter_with_permission_can_list_messages(): void
    {
        $support = $this->userWithPermission('conversation.viewAny');
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner, $support);
        Sanctum::actingAs($support);
        $this->getJson("/api/v1/tickets/{$ticket->id}/messages")
            ->assertOk();
    }

    public function test_random_user_cannot_list_messages(): void
    {
        $random = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        Sanctum::actingAs($random);
        $this->getJson("/api/v1/tickets/{$ticket->id}/messages")->assertStatus(403);
    }

    public function test_rejects_an_empty_message(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    public function test_rejects_a_message_longer_than_1000_characters(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => str_repeat('a', 1001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    public function test_returns_404_when_message_belongs_to_another_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $otherTicket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/tickets/{$otherTicket->id}/messages/{$message->id}")->assertStatus(404);
    }
}
