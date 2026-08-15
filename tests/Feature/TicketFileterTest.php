<?php

namespace Tests\Feature;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketFileterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_tickets_by_open_status(): void
    {
        $user = User::factory()->create();
        $status = TicketStatus::factory()->create(['name' => 'open']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => $status->id,
        ]);

        $tickets = Ticket::whereHas('status',function (Builder $query) use ($status){
            $query->where('name','=','open');
        })->get();

        $this->assertDatabaseHas('tickets',[
            'ticket_status_id' => $status->id
        ]);

        $this->assertTrue(
            $tickets->contains($ticket)
        );
    }
    public function test_can_filter_tickets_by_high_priority(): void
    {
        $user = User::factory()->create();
        $priority = TicketPriority::factory()->create(['name' => 'high']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => $priority->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);
        $tickets = Ticket::whereHas('priority',function (Builder $query) use ($priority){
            $query->where('name','=','high');
        })->get();

        $this->assertDatabaseHas('ticket_priorities',[
            'name' => $priority->name
        ]);

        $this->assertTrue($tickets->contains($ticket));
    }
    public function test_ticket_filter_with_subject(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'subject' => 'payment failed',
            'user_id' => $user->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);

        $tickets = Ticket::where('subject','payment failed')->get();

        $this->assertDatabaseHas('tickets',[
            'id' => $ticket->id
        ]);

        $this->assertTrue($tickets->contains($ticket));
    }
    public function test_ticket_filter_with_date_today()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'subject' => 'payment failed',
            'user_id' => $user->id,
            'created_at' => today(),
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);

        $tickets = Ticket::whereDate('created_at',today())->get();

        $this->assertDatabaseHas('tickets',[
            'id' => $ticket->id
        ]);

        $this->assertTrue($tickets->contains($ticket));
    }
    public function test_count_ticket_open_status()
    {
        $user = User::factory()->create();
        $status = TicketStatus::factory()->create(['name' => 'open']);
        $len = 0;
        while ($len < 2){
            $ticket = Ticket::factory()->create([
                'user_id' => $user->id,
                'ticket_category_id' => TicketCategory::factory()->create()->id,
                'ticket_priority_id' => TicketPriority::factory()->create()->id,
                'ticket_status_id' => $status->id,
            ]);
            $len++;
        }
        $tickets = Ticket::whereHas('status',function (Builder $query) use ($status){
            $query->where('name','=','open');
        })->get();

        $this->assertCount(2,$tickets);
    }
    public function test_ticket_database_missing(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => TicketStatus::factory()->create()->id,
        ]);

        $this->assertTrue($ticket->delete());

        $this->assertSoftDeleted('tickets',[
            'id' => $ticket->id
        ]);
    }
}
