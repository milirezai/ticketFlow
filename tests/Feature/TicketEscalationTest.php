<?php

namespace Tests\Feature;

use App\Events\Activity\TicketAssigned;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use App\Services\Escalation\TicketEscalation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketEscalationTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    private function makeTicket(
        TicketCategory $category,
        string $status = 'open',
        ?User $assigned = null,
        string $priorityName = 'high',
        ?\DateTimeInterface $lastEscalation = null,
    ): Ticket {
        return Ticket::create([
            'subject' => 'Test subject',
            'user_id' => User::factory()->create()->id,
            'assigned_to' => $assigned?->id,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => TicketPriority::factory()->create(['name' => $priorityName])->id,
            'ticket_status_id' => TicketStatus::factory()->create(['name' => $status])->id,
            'last_escalation' => $lastEscalation,
        ]);
    }

    private function escalate(): void
    {
        app(TicketEscalation::class)->escalate();
    }

    public function test_open_ticket_is_escalated_to_another_expert(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $expertB = $this->makeExpert($category);
        $this->userWithRole('support-specialist');
        $ticket = $this->makeTicket($category, 'open', $expertA);
        $this->escalate();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expertB->id]);
        $this->assertNotNull($ticket->fresh()->last_escalation);
    }

    public function test_escalation_fires_ticket_assigned_event(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $expertB = $this->makeExpert($category);
        $this->userWithRole('support-specialist');
        $this->makeTicket($category, 'open', $expertA);
        Event::fake([TicketAssigned::class]);
        $this->escalate();
        Event::assertDispatched(TicketAssigned::class);
    }

    public function test_closed_ticket_is_not_escalated(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $expertB = $this->makeExpert($category);
        $this->userWithRole('support-specialist');
        $ticket = $this->makeTicket($category, 'closed', $expertA);
        $this->escalate();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expertA->id]);
        $this->assertNull($ticket->fresh()->last_escalation);
    }

    public function test_open_ticket_with_future_deadline_is_not_escalated(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $expertB = $this->makeExpert($category);
        $this->userWithRole('support-specialist');
        $future = now()->addDay();
        $ticket = $this->makeTicket($category, 'open', $expertA, 'high', $future);
        $this->escalate();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expertA->id]);
        $this->assertTrue($ticket->fresh()->last_escalation->isSameSecond($future));
    }

    public function test_open_ticket_with_past_deadline_is_escalated_again(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $expertB = $this->makeExpert($category);
        $support = $this->userWithRole('support-specialist');
        $ticket = $this->makeTicket($category, 'open', $expertB, 'high', now()->subDay());
        $this->escalate();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $expertA->id]);
    }

    public function test_open_ticket_without_eligible_expert_falls_back_to_support(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $support = $this->userWithRole('support-specialist');
        $ticket = $this->makeTicket($category, 'open', $expertA);
        $this->escalate();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'assigned_to' => $support->id]);
    }

    public function test_high_priority_escalation_sets_a_12_hour_deadline(): void
    {
        $category = TicketCategory::factory()->create();
        $expertA = $this->makeExpert($category);
        $expertB = $this->makeExpert($category);
        $this->userWithRole('support-specialist');
        $ticket = $this->makeTicket($category, 'open', $expertA);
        $this->escalate();
        $minutes = (int) now()->diffInMinutes($ticket->fresh()->last_escalation);
        $this->assertContains($minutes, [719, 720, 721]);
    }
}
