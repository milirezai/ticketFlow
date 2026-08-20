<?php

namespace Tests\Feature;

use App\Events\Activity\TicketAssigned;
use App\Models\Access\Role;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use App\Services\ResponseTime\ResponseTimer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketAutoAssignTest extends TestCase
{
    use RefreshDatabase;

    private Role $expertRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->expertRole = Role::create(['name' => 'expert', 'description' => 'Expert', 'status' => 1]);
        Role::create(['name' => 'regular-user', 'description' => 'Regular User', 'status' => 1]);
        $this->mock(ResponseTimer::class, function ($mock) {
            $mock->shouldReceive('evaluateTime')->once();
        });
    }

    private function makeExpert(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->expertRole);
        return $user;
    }

    private function createTicketAs(User $user, int $categoryId): void
    {
        $this->postJson(route('tickets.store'), [
            'subject' => 'Test subject for ticket',
            'content' => 'This is a test message content for the ticket',
            'ticket_category_id' => $categoryId,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create(['name' => 'open'])->id,
        ]);
    }

    public function test_ticket_auto_assigned_to_expert_with_matching_category(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert();
        $expert->expertCategories()->attach($category->id);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->createTicketAs($user, $category->id);
        $ticket = Ticket::latest()->first();
        $this->assertEquals($expert->id, $ticket->assigned_to);
    }

    public function test_ticket_stays_unassigned_when_no_expert_has_category(): void
    {
        $category = TicketCategory::factory()->create();
        $otherCategory = TicketCategory::factory()->create();
        $expert = $this->makeExpert();
        $expert->expertCategories()->attach($otherCategory->id);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->createTicketAs($user, $category->id);
        $ticket = Ticket::latest()->first();
        $this->assertNull($ticket->assigned_to);
    }

    public function test_ticket_assigned_to_least_loaded_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $busyExpert = $this->makeExpert();
        $busyExpert->expertCategories()->attach($category->id);
        $freeExpert = $this->makeExpert();
        $freeExpert->expertCategories()->attach($category->id);
        $owner = User::factory()->create();
        for ($i = 0; $i < 3; $i++) {
            Ticket::factory()->create([
                'user_id' => $owner->id,
                'assigned_to' => $busyExpert->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => TicketPriority::factory()->create()->id,
                'ticket_status_id' => TicketStatus::factory()->create(['name' => 'open'])->id,
            ]);
        }
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->createTicketAs($user, $category->id);
        $ticket = Ticket::latest('id')->first();
        $this->assertEquals($freeExpert->id, $ticket->assigned_to);
    }

    public function test_ticket_assigned_event_fires_on_auto_assignment(): void
    {
        Event::fake([TicketAssigned::class]);
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert();
        $expert->expertCategories()->attach($category->id);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->createTicketAs($user, $category->id);
        Event::assertDispatched(TicketAssigned::class);
    }

    public function test_ticket_not_assigned_to_non_expert_user(): void
    {
        $category = TicketCategory::factory()->create();
        $regularUser = User::factory()->create();
        $regularUser->expertCategories()->attach($category->id);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->createTicketAs($user, $category->id);
        $ticket = Ticket::latest()->first();
        $this->assertNull($ticket->assigned_to);
    }

    public function test_ticket_assigned_to_only_matching_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $otherCategory = TicketCategory::factory()->create();
        $matchingExpert = $this->makeExpert();
        $matchingExpert->expertCategories()->attach($category->id);
        $wrongExpert = $this->makeExpert();
        $wrongExpert->expertCategories()->attach($otherCategory->id);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->createTicketAs($user, $category->id);
        $ticket = Ticket::latest()->first();
        $this->assertEquals($matchingExpert->id, $ticket->assigned_to);
        $this->assertNotEquals($wrongExpert->id, $ticket->assigned_to);
    }
}
