<?php

namespace Tests\Feature;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use App\Notifications\Ticket\Activity\TicketAssignedNotification;
use App\Notifications\Ticket\Activity\TicketCreatedNotification;
use App\Notifications\Ticket\Activity\TicketMessageCreatedNotification;
use App\Notifications\Ticket\Activity\TicketStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
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

    private function makeTicket(User $owner, ?User $expert = null, ?TicketCategory $category = null): Ticket
    {
        if ($category === null) {
            $category = TicketCategory::factory()->create();
        }
        if ($expert) {
            $expert->expertCategories()->syncWithoutDetaching($category->id);
        }
        return Ticket::create([
            'subject' => 'Test subject',
            'user_id' => $owner->id,
            'assigned_to' => $expert?->id,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
    }

    private function expertCanMessage(TicketCategory $category): User
    {
        $expert = $this->makeExpert($category);
        $expert->permissions()->attach($this->definePermissionGate('conversation.create'));
        return $expert;
    }

    private function storeTicketPayload(TicketCategory $category, User $owner): void
    {
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.store'), [
            'subject' => 'Help me with login',
            'content' => 'I cannot login to my account',
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ])->assertStatus(201);
    }

    public function test_admins_receive_notification_when_ticket_created_without_expert(): void
    {
        Notification::fake();
        $admin = $this->userWithRole('admin');
        $owner = User::factory()->create();
        $this->storeTicketPayload(TicketCategory::factory()->create(), $owner);
        Notification::assertSentTo($admin, TicketCreatedNotification::class);
    }

    public function test_all_admins_and_support_notified_when_ticket_created_without_expert(): void
    {
        Notification::fake();
        $adminA = $this->userWithRole('admin');
        $adminB = $this->userWithRole('admin');
        $support = $this->userWithRole('support-specialist');
        $owner = User::factory()->create();
        $this->storeTicketPayload(TicketCategory::factory()->create(), $owner);
        Notification::assertSentTo($adminA, TicketCreatedNotification::class);
        Notification::assertSentTo($adminB, TicketCreatedNotification::class);
        Notification::assertSentTo($support, TicketCreatedNotification::class);
        Notification::assertNotSentTo($owner, TicketCreatedNotification::class);
    }

    public function test_expert_notified_of_automatic_assignment_when_ticket_created(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $admin = $this->userWithRole('admin');
        $owner = User::factory()->create();
        $this->storeTicketPayload($category, $owner);
        Notification::assertSentTo($expert, TicketAssignedNotification::class);
        Notification::assertNotSentTo($expert, TicketCreatedNotification::class);
        Notification::assertSentTo($admin, TicketCreatedNotification::class);
    }

    public function test_owner_notified_when_expert_replies(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expertCanMessage($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        Sanctum::actingAs($expert);
        $this->postJson(route('tickets.messages.store', $ticket->id), ['content' => 'Reply from expert'])
            ->assertStatus(201);
        Notification::assertSentTo($owner, TicketMessageCreatedNotification::class);
    }

    public function test_expert_notified_when_owner_replies(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expertCanMessage($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.messages.store', $ticket->id), ['content' => 'Owner reply here'])
            ->assertStatus(201);
        Notification::assertSentTo($expert, TicketMessageCreatedNotification::class);
    }

    public function test_only_ticket_participants_notified_of_messages(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expertCanMessage($category);
        $owner = User::factory()->create();
        $staff = $this->userWithRole('support-specialist');
        $ticket = $this->makeTicket($owner, $expert);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.messages.store', $ticket->id), ['content' => 'Owner reply here'])
            ->assertStatus(201);
        Notification::assertSentTo($expert, TicketMessageCreatedNotification::class);
        Notification::assertNotSentTo($owner, TicketMessageCreatedNotification::class);
        Notification::assertNotSentTo($staff, TicketMessageCreatedNotification::class);
    }

    public function test_new_expert_notified_on_assignment(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, category: $category);
        $support = $this->userWithRole('support-specialist');
        Sanctum::actingAs($support);
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])
            ->assertOk();
        Notification::assertSentTo($expert, TicketAssignedNotification::class);
    }

    public function test_old_expert_not_notified_after_reassignment(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $oldExpert = $this->makeExpert($category);
        $newExpert = $this->makeExpert($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $oldExpert, $category);
        $support = $this->userWithRole('support-specialist');
        Sanctum::actingAs($support);
        $this->postJson(route('experts.assign', $newExpert->id), ['ticket_id' => $ticket->id])
            ->assertOk();
        Notification::assertSentTo($newExpert, TicketAssignedNotification::class);
        Notification::assertNotSentTo($oldExpert, TicketAssignedNotification::class);
    }

    public function test_owner_notified_on_status_change(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $newStatus = TicketStatus::factory()->create();
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', $ticket->id), ['ticket_status_id' => $newStatus->id])
            ->assertOk();
        Notification::assertSentTo($owner, TicketStatusChangedNotification::class);
    }

    public function test_status_change_notifies_owner_but_not_assigned_expert(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        $newStatus = TicketStatus::factory()->create();
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', $ticket->id), ['ticket_status_id' => $newStatus->id])
            ->assertOk();
        Notification::assertSentTo($owner, TicketStatusChangedNotification::class);
        Notification::assertNotSentTo($expert, TicketStatusChangedNotification::class);
    }

    public function test_sender_does_not_receive_own_message_notification(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expertCanMessage($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.messages.store', $ticket->id), ['content' => 'Owner sends message'])
            ->assertStatus(201);
        Notification::assertNotSentTo($owner, TicketMessageCreatedNotification::class);
        Notification::assertSentTo($expert, TicketMessageCreatedNotification::class);
    }
}
