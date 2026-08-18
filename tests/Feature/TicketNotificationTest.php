<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
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
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'expert.manage', 'description' => 'expert.manage', 'status' => 1]);
        Gate::define('expert.manage', fn(User $user) => $user->hasPermissionTo('expert.manage'));
        foreach (['conversation.viewAny', 'conversation.view', 'conversation.create', 'conversation.update', 'conversation.delete'] as $name) {
            Permission::create(['name' => $name, 'description' => $name, 'status' => 1]);
            Gate::define($name, fn(User $user) => $user->hasPermissionTo($name));
        }
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

    public function test_admins_receive_notification_when_ticket_created_without_expert(): void
    {
        Notification::fake();
        $admin = $this->userWithRole('admin');
        $category = TicketCategory::factory()->create();
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.store'), [
            'subject' => 'Help me with login',
            'content' => 'I cannot login to my account',
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ])->assertStatus(201);
        Notification::assertSentTo($admin, TicketCreatedNotification::class);
    }

    public function test_owner_notified_when_expert_replies(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $expert->permissions()->attach(Permission::where('name', 'conversation.create')->first());
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        Sanctum::actingAs($expert);
        $this->postJson(route('tickets.messages.store', [$ticket->id]), ['content' => 'Reply from expert'])
            ->assertStatus(201);
        Notification::assertSentTo($owner, TicketMessageCreatedNotification::class);
    }

    public function test_expert_notified_when_owner_replies(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.messages.store', [$ticket->id]), ['content' => 'Owner reply here'])
            ->assertStatus(201);
        Notification::assertSentTo($expert, TicketMessageCreatedNotification::class);
    }

    public function test_new_expert_notified_on_assignment(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, category: $category);
        $support = $this->userWithRole('support-specialist');
        Sanctum::actingAs($support);
        $this->postJson(route('experts.assign', [$expert->id]), ['ticket_id' => $ticket->id])
            ->assertOk();
        Notification::assertSentTo($expert, TicketAssignedNotification::class);
    }

    public function test_owner_notified_on_status_change(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner);
        $newStatus = TicketStatus::factory()->create();
        Sanctum::actingAs($owner);
        $this->putJson(route('tickets.update', [$ticket->id]), [
            'ticket_status_id' => $newStatus->id,
        ])->assertOk();
        Notification::assertSentTo($owner, TicketStatusChangedNotification::class);
    }

    public function test_sender_does_not_receive_own_message_notification(): void
    {
        Notification::fake();
        $category = TicketCategory::factory()->create();
        $expert = $this->expert($category);
        $owner = User::factory()->create();
        $ticket = $this->makeTicket($owner, $expert);
        Sanctum::actingAs($owner);
        $this->postJson(route('tickets.messages.store', [$ticket->id]), ['content' => 'Owner sends message'])
            ->assertStatus(201);
        Notification::assertNotSentTo($owner, TicketMessageCreatedNotification::class);
        Notification::assertSentTo($expert, TicketMessageCreatedNotification::class);
    }
}
