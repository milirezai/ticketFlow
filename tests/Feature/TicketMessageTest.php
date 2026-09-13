<?php

namespace Tests\Feature;

use App\Events\Activity\TicketMessageAdded;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketMessage;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketMessageTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['conversation.viewAny', 'conversation.view', 'conversation.create', 'conversation.update', 'conversation.delete'] as $name) {
            $this->definePermissionGate($name);
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

    public function test_store_dispatches_message_added_event(): void
    {
        Event::fake([TicketMessageAdded::class]);
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'Event please'])
            ->assertStatus(201);
        Event::assertDispatched(TicketMessageAdded::class);
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

    public function test_owner_can_show_ticket_message(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create(['content' => 'View me please']);
        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('data.content', 'View me please');
    }

    public function test_assigned_supporter_with_conversation_view_can_show_message(): void
    {
        $support = $this->userWithPermission('conversation.view');
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner, $support);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($support);
        $this->getJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}")->assertOk();
    }

    public function test_random_user_cannot_show_ticket_message(): void
    {
        $random = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($random);
        $this->getJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}")->assertStatus(403);
    }

    public function test_owner_can_update_ticket_message(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create(['content' => 'Before']);
        Sanctum::actingAs($owner);
        $this->putJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}", ['content' => 'After edit'])
            ->assertOk()
            ->assertJsonPath('data.content', 'After edit');
        $this->assertDatabaseHas('ticket_messages', ['id' => $message->id, 'content' => 'After edit']);
    }

    public function test_assigned_supporter_with_update_permission_can_update_message(): void
    {
        $support = $this->userWithPermission('conversation.update');
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner, $support);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($support);
        $this->putJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}", ['content' => 'Edited by support'])
            ->assertOk();
    }

    public function test_random_user_cannot_update_ticket_message(): void
    {
        $random = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($random);
        $this->putJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}", ['content' => 'Hacked'])
            ->assertStatus(403);
    }

    public function test_update_requires_some_content(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($owner);
        $this->putJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}", ['content' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    public function test_update_returns_404_when_message_belongs_to_another_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $otherTicket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($owner);
        $this->putJson("/api/v1/tickets/{$otherTicket->id}/messages/{$message->id}", ['content' => 'X'])
            ->assertStatus(404);
    }

    public function test_owner_can_delete_ticket_message(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        $this->assertDatabaseHas(TicketMessage::class, ['id' => $message->id]);
        Sanctum::actingAs($owner);
        $this->deleteJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}")->assertStatus(204);
        $this->assertDatabaseMissing(TicketMessage::class, ['id' => $message->id]);
    }

    public function test_assigned_supporter_with_delete_permission_can_delete_message(): void
    {
        $support = $this->userWithPermission('conversation.delete');
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner, $support);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($support);
        $this->deleteJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}")->assertStatus(204);
    }

    public function test_random_user_cannot_delete_ticket_message(): void
    {
        $random = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $message = TicketMessage::factory()->for($ticket)->for($owner)->create();
        Sanctum::actingAs($random);
        $this->deleteJson("/api/v1/tickets/{$ticket->id}/messages/{$message->id}")->assertStatus(403);
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

    public function test_unauthenticated_user_cannot_access_messages(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicketFor($owner);
        $this->getJson("/api/v1/tickets/{$ticket->id}/messages")->assertStatus(401);
        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", ['content' => 'Nope'])->assertStatus(401);
    }
}
