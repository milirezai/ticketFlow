<?php

namespace Tests\Feature;

use App\Events\Activity\TicketAssigned;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definePermissionGate('expert.manage');
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
        $expert = $this->makeExpert($category);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])
            ->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expert->id]);
    }

    public function test_user_with_expert_manage_permission_can_assign(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs($this->userWithPermission('expert.manage'));
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])
            ->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expert->id]);
    }

    public function test_reassign_changes_the_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $expertB = $this->makeExpert($category);
        $ticket = $this->makeTicket($category, $expertA);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', $expertB->id), ['ticket_id' => $ticket->id])
            ->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expertB->id]);
    }

    public function test_assign_fires_ticket_assigned_event(): void
    {
        Event::fake([TicketAssigned::class]);
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])
            ->assertOk();
        Event::assertDispatched(TicketAssigned::class);
    }

    public function test_cannot_assign_to_non_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $ticket = $this->makeTicket($category);
        $nonExpert = User::factory()->create();
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', $nonExpert->id), ['ticket_id' => $ticket->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_cannot_assign_to_expert_outside_ticket_category(): void
    {
        $category = TicketCategory::factory()->create();
        $otherCategory = TicketCategory::factory()->create();
        $expert = $this->makeExpert($otherCategory);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_regular_user_cannot_assign(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $ticket = $this->makeTicket($category);
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])
            ->assertForbidden();
    }

    public function test_ticket_owner_cannot_assign(): void
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
        Sanctum::actingAs($owner);
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => $ticket->id])
            ->assertForbidden();
    }

    public function test_validation_requires_ticket_id(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', $expert->id), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_cannot_assign_to_nonexistent_ticket(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.assign', $expert->id), ['ticket_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_support_specialist_can_list_experts(): void
    {
        $category = TicketCategory::factory()->create();
        $this->makeExpert($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->getJson(route('experts.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_with_expert_manage_permission_can_list_experts(): void
    {
        $category = TicketCategory::factory()->create();
        $this->makeExpert($category);
        Sanctum::actingAs($this->userWithPermission('expert.manage'));
        $this->getJson(route('experts.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_regular_user_cannot_list_experts(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('experts.index'))->assertForbidden();
    }

    public function test_expert_can_fetch_own_tickets(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $assigned = $this->makeTicket($category, $expert);
        $this->makeTicket($category);
        Sanctum::actingAs($expert);
        $this->getJson(route('experts.tickets', $expert->id))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', $assigned->subject);
    }

    public function test_support_specialist_can_view_expert_tickets(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $this->makeTicket($category, $expert);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->getJson(route('experts.tickets', $expert->id))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_regular_user_cannot_view_expert_tickets(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('experts.tickets', $expert->id))->assertForbidden();
    }

    public function test_another_expert_cannot_view_someone_elses_tickets(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $otherExpert = $this->makeExpert($category);
        $this->makeTicket($category, $expert);
        Sanctum::actingAs($otherExpert);
        $this->getJson(route('experts.tickets', $expert->id))->assertForbidden();
    }

    public function test_expert_can_view_own_categories(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($expert);
        $this->getJson(route('experts.categories', $expert->id))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_support_specialist_can_view_expert_categories(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->getJson(route('experts.categories', $expert->id))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_another_expert_cannot_view_someone_elses_categories(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        $otherExpert = $this->makeExpert($category);
        Sanctum::actingAs($otherExpert);
        $this->getJson(route('experts.categories', $expert->id))->assertForbidden();
    }

    public function test_regular_user_cannot_view_expert_categories(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('experts.categories', $expert->id))->assertForbidden();
    }

    public function test_user_with_expert_manage_permission_can_sync_categories(): void
    {
        $category = TicketCategory::factory()->create();
        $otherCategory = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($this->userWithPermission('expert.manage'));
        $this->postJson(route('experts.syncCategories', $expert->id), ['category_ids' => [$category->id, $otherCategory->id]])
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->assertDatabaseHas('expert_categories', ['user_id' => $expert->id, 'ticket_category_id' => $otherCategory->id]);
    }

    public function test_support_specialist_without_permission_cannot_sync_categories(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($this->userWithRole('support-specialist'));
        $this->postJson(route('experts.syncCategories', $expert->id), ['category_ids' => [$category->id]])
            ->assertForbidden();
    }

    public function test_expert_cannot_sync_own_categories(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($expert);
        $this->postJson(route('experts.syncCategories', $expert->id), ['category_ids' => [$category->id]])
            ->assertForbidden();
    }

    public function test_sync_categories_replaces_existing(): void
    {
        $category = TicketCategory::factory()->create();
        $newCategory = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($this->userWithPermission('expert.manage'));
        $this->postJson(route('experts.syncCategories', $expert->id), ['category_ids' => [$newCategory->id]])
            ->assertOk();
        $this->assertDatabaseMissing('expert_categories', ['user_id' => $expert->id, 'ticket_category_id' => $category->id]);
        $this->assertDatabaseHas('expert_categories', ['user_id' => $expert->id, 'ticket_category_id' => $newCategory->id]);
    }

    public function test_sync_categories_rejects_nonexistent_category(): void
    {
        $category = TicketCategory::factory()->create();
        $expert = $this->makeExpert($category);
        Sanctum::actingAs($this->userWithPermission('expert.manage'));
        $this->postJson(route('experts.syncCategories', $expert->id), ['category_ids' => [999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_ids.0');
    }

    public function test_unauthenticated_user_cannot_access_experts(): void
    {
        $this->getJson(route('experts.index'))->assertUnauthorized();
        $this->postJson(route('experts.assign', 1), ['ticket_id' => 1])->assertUnauthorized();
    }
}
