<?php

namespace Tests\Feature;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketActivityTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definePermissionGate('ticket.view');
        Storage::fake('local');
    }

    private function makeTicket(?User $owner = null, ?User $expert = null): Ticket
    {
        return Ticket::create([
            'subject' => 'Test subject',
            'user_id' => $owner?->id ?? User::factory()->create()->id,
            'assigned_to' => $expert?->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
    }

    private function storeTicket(User $owner, TicketCategory $category): Ticket
    {
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.store'), [
            'subject' => 'Help me with login',
            'content' => 'I cannot login to my account',
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ])->assertStatus(201);

        return Ticket::latest('id')->firstOrFail();
    }

    private function viewer(): User
    {
        return $this->userWithPermission('ticket.view');
    }

    private function assertLogged(string $action, int $subjectId, int $userId): void
    {
        $this->assertDatabaseHas('activity_logs', [
            'action' => $action,
            'subject_type' => Ticket::class,
            'subject_id' => $subjectId,
            'user_id' => $userId,
        ]);
    }

    public function test_user_with_ticket_view_permission_can_view_activities(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->storeTicket($owner, TicketCategory::factory()->create());
        Sanctum::actingAs($this->viewer());
        $this->getJson(route('tickets.activities', $ticket->id))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.action', 'ticket.create')
            ->assertJsonPath('data.1.action', 'ticket.message_add')
            ->assertJsonStructure(['data' => [['user_id', 'action', 'description', 'properties', 'created_at', 'updated_at']]]);
    }

    public function test_owner_without_ticket_view_permission_cannot_view_activities(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs($owner);
        $this->getJson(route('tickets.activities', $ticket->id))->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_activities(): void
    {
        $this->getJson(route('tickets.activities', 1))->assertUnauthorized();
    }

    public function test_ticket_create_action_is_logged(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->storeTicket($owner, TicketCategory::factory()->create());
        $this->assertLogged('ticket.create', $ticket->id, $owner->id);
        Sanctum::actingAs($this->viewer());
        $this->getJson(route('tickets.activities', $ticket->id))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'ticket.create')
            ->assertJsonPath('data.0.properties.category', $ticket->category->name)
            ->assertJsonPath('data.0.properties.priority', $ticket->priority->name)
            ->assertJsonPath('data.0.properties.status', $ticket->status->name);
    }

    public function test_ticket_status_change_is_logged(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $oldStatus = $ticket->status->name;
        $newStatus = TicketStatus::factory()->create();
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', $ticket->id), ['ticket_status_id' => $newStatus->id])->assertOk();
        $this->assertLogged('ticket.status_change', $ticket->id, $owner->id);
        Sanctum::actingAs($this->viewer());
        $this->getJson(route('tickets.activities', $ticket->id))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'ticket.status_change')
            ->assertJsonPath('data.0.properties.old', $oldStatus)
            ->assertJsonPath('data.0.properties.new', $newStatus->name);
    }

    public function test_ticket_priority_change_is_logged(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $newPriority = TicketPriority::factory()->create();
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', $ticket->id), ['ticket_priority_id' => $newPriority->id])->assertOk();
        $this->assertLogged('ticket.priority_change', $ticket->id, $owner->id);
    }

    public function test_ticket_category_change_is_logged(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $newCategory = TicketCategory::factory()->create();
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', $ticket->id), ['ticket_category_id' => $newCategory->id])->assertOk();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'ticket.catefory_change',
            'subject_type' => Ticket::class,
            'subject_id' => $ticket->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_ticket_assignment_is_logged(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $owner = User::factory()->create();
        $ticket = Ticket::create([
            'subject' => 'Test subject',
            'user_id' => $owner->id,
            'assigned_to' => null,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
        $support = $this->userWithRole('support-specialist');
        Sanctum::actingAs($support);
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])->assertOk();
        $this->assertLogged('ticket.assigned', $ticket->id, $support->id);
    }

    public function test_ticket_message_add_is_logged(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.messages.store', $ticket->id), ['content' => 'First message'])
            ->assertStatus(201);
        $this->assertLogged('ticket.message_add', $ticket->id, $owner->id);
    }

    public function test_ticket_file_attach_is_logged(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.files.store', $ticket->id), [
            'files' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
        ])->assertStatus(201);
        $this->assertLogged('ticket.file_attach', $ticket->id, $owner->id);
    }

    public function test_activities_are_scoped_to_the_ticket(): void
    {
        $owner = User::factory()->create();
        $ticketA = $this->storeTicket($owner, TicketCategory::factory()->create());
        $ticketB = $this->storeTicket($owner, TicketCategory::factory()->create());
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.messages.store', $ticketB->id), ['content' => 'Message on B'])
            ->assertStatus(201);
        Sanctum::actingAs($this->viewer());
        $this->getJson(route('tickets.activities', $ticketA->id))->assertOk()->assertJsonCount(2, 'data');
        $this->getJson(route('tickets.activities', $ticketB->id))->assertOk()->assertJsonCount(3, 'data');
    }
}
