<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use App\Services\Access\AccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;


class TicketTest extends TestCase
{
    use RefreshDatabase;
    private function makeTicket(User $user)
    {
        $ticketCategory = TicketCategory::factory()->create();
        $ticketPriority = TicketPriority::factory()->create();
        $ticketStatus = TicketStatus::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'ticket_category_id' => $ticketCategory->id,
            'ticket_priority_id' => $ticketPriority->id,
            'ticket_status_id' => $ticketStatus->id
        ]);
        return $ticket;
    }


//    public function test_user_can_view_tickets(): void
//    {
//        $user = User::factory()->has(
//            Permission::factory()->count(1)->create(['name' =>'ticket.view'])
//        )->create();
//        Sanctum::actingAs($user);
//
//        $response = $this->get(route('tickets.index'));
//        $response->assertStatus(200);
//    }

    public function test_user_can_create_ticket()
    {
        $user = User::factory()->create();
        $data = [
            'subject' => fake()->title(10),
            'content' => fake()->text(256),
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' =>  TicketStatus::factory()->create()->id
        ];

        Sanctum::actingAs($user);

        $response = $this->post(route('tickets.store'),$data);
        $response->assertStatus(201);
    }

    public function test_user_can_destroy_ticket()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->delete(route('tickets.destroy',$this->makeTicket($user)));
        $response->assertStatus(204);
    }
    public function test_user_can_update_ticket()
    {
        $data = ['subject' => fake()->title()];
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->put(route('tickets.update',$this->makeTicket($user)),$data);
        $response->assertStatus(200);
    }

}
