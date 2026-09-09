<?php

namespace Tests\Feature;

use App\Events\Activity\TicketStatusChanged;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['ticket.view', 'ticket.update'] as $permission) {
            $this->definePermissionGate($permission);
        }
    }

    private function ticketAttributes(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'New ticket subject here',
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create(['name' => 'open'])->id,
        ], $overrides);
    }

    private function createTicket(array $attributes = []): Ticket
    {
        return Ticket::factory()->create([
            'user_id' => User::factory()->create()->id,
            'assigned_to' => null,
            ...$this->ticketAttributes($attributes),
        ]);
    }

    public function test_user_with_permission_can_index_tickets(): void
    {
        $this->createTicket(['subject' => 'First subject here']);
        $this->createTicket(['subject' => 'Second subject here']);
        $user = $this->userWithPermission('ticket.view');
        Sanctum::actingAs($user);
        $this->getJson(route('tickets.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.subject', 'First subject here');
    }

    public function test_index_requires_ticket_view_permission(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('tickets.index'))->assertForbidden();
    }

    public function test_store_creates_ticket_and_initial_message(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = array_merge($this->ticketAttributes(), ['content' => 'This is a detailed message content for the ticket.']);
        $this->postJson(route('tickets.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.subject', $payload['subject']);
        $this->assertDatabaseHas('tickets', [
            'subject' => $payload['subject'],
            'user_id' => $user->id,
            'ticket_category_id' => $payload['ticket_category_id'],
            'ticket_priority_id' => $payload['ticket_priority_id'],
            'ticket_status_id' => $payload['ticket_status_id'],
        ]);
        $ticket = Ticket::latest('id')->first();
        $this->assertDatabaseHas('ticket_messages', [
            'content' => $payload['content'],
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_store_requires_subject_content_and_lookups(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('tickets.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'subject',
                'content',
                'ticket_category_id',
                'ticket_priority_id',
                'ticket_status_id',
            ]);
    }

    public function test_store_rejects_non_existing_category(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('tickets.store'), $this->ticketAttributes(['ticket_category_id' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ticket_category_id']);
    }

    public function test_store_rejects_invalid_file_type(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('tickets.store'), array_merge(
            $this->ticketAttributes(),
            ['files' => [UploadedFile::fake()->create('malware.exe')]]
        ))->assertUnprocessable()
            ->assertJsonValidationErrors(['files.0']);
    }

    public function test_user_with_permission_can_show_ticket(): void
    {
        $ticket = $this->createTicket(['subject' => 'Show me this subject']);
        $user = $this->userWithPermission('ticket.view');
        Sanctum::actingAs($user);
        $this->getJson(route('tickets.show', $ticket))
            ->assertOk()
            ->assertJsonPath('data.subject', 'Show me this subject');
    }

    public function test_show_requires_ticket_view_permission(): void
    {
        $ticket = $this->createTicket();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_owner_can_update_own_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->createTicket(['user_id' => $owner->id, 'subject' => 'Before subject']);
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', $ticket), ['subject' => 'After subject'])
            ->assertOk()
            ->assertJsonPath('data.subject', 'After subject');
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'subject' => 'After subject']);
    }

    public function test_user_with_permission_can_update_any_ticket(): void
    {
        $ticket = $this->createTicket(['subject' => 'Before subject']);
        $user = $this->userWithPermission('ticket.update');
        Sanctum::actingAs($user);
        $this->putJson(route('tickets.update', $ticket), ['subject' => 'Updated by manager'])
            ->assertOk()
            ->assertJsonPath('data.subject', 'Updated by manager');
    }

    public function test_update_with_new_status_fires_status_changed_event(): void
    {
        Event::fake([TicketStatusChanged::class]);
        $owner = User::factory()->create();
        $ticket = $this->createTicket(['user_id' => $owner->id]);
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', $ticket), [
            'ticket_status_id' => TicketStatus::factory()->create(['name' => 'closed'])->id,
        ])->assertOk();
        Event::assertDispatched(TicketStatusChanged::class);
    }

    public function test_user_without_permission_cannot_update_someone_elses_ticket(): void
    {
        $ticket = $this->createTicket();
        Sanctum::actingAs(User::factory()->create());
        $this->putJson(route('tickets.update', $ticket), ['subject' => 'Hacked'])->assertForbidden();
    }

    public function test_owner_can_delete_own_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->createTicket(['user_id' => $owner->id]);
        Sanctum::actingAs($owner);
        $this->deleteJson(route('tickets.destroy', $ticket))->assertNoContent();
        $this->assertSoftDeleted($ticket);
    }

    public function test_user_with_permission_can_delete_any_ticket(): void
    {
        $ticket = $this->createTicket();
        $user = $this->userWithPermission('ticket.update');
        Sanctum::actingAs($user);
        $this->deleteJson(route('tickets.destroy', $ticket))->assertNoContent();
        $this->assertSoftDeleted($ticket);
    }

    public function test_user_without_permission_cannot_delete_someone_elses_ticket(): void
    {
        $ticket = $this->createTicket();
        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson(route('tickets.destroy', $ticket))->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_tickets(): void
    {
        $this->getJson(route('tickets.index'))->assertUnauthorized();
        $this->postJson(route('tickets.store'), $this->ticketAttributes())->assertUnauthorized();
    }
}
