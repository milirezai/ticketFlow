<?php

namespace Tests\Feature;

use App\Models\Access\Role;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->managerRole = Role::create(['name' => 'manager', 'description' => 'Manager', 'status' => 1]);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->managerRole);
        return $user;
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson(route('dashboard.index'))->assertStatus(401);
    }

    public function test_regular_user_cannot_access_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('dashboard.index'))->assertStatus(403);
    }

    public function test_manager_can_access_dashboard(): void
    {
        Sanctum::actingAs($this->manager());
        $this->getJson(route('dashboard.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_tickets',
                    'open_tickets' => ['count', 'tickets'],
                    'pending_tickets' => ['count', 'tickets'],
                    'closed_tickets' => ['count', 'tickets'],
                    'by_priority',
                    'by_category',
                    'per_expert',
                ],
            ]);
    }

    public function test_total_ticket_count_is_accurate(): void
    {
        Sanctum::actingAs($this->manager());
        for ($i = 0; $i < 5; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => User::factory()->create()->id,
                'ticket_category_id' => TicketCategory::factory()->create()->id,
                'ticket_priority_id' => TicketPriority::factory()->create()->id,
                'ticket_status_id' => TicketStatus::factory()->create(['name' => 'open'])->id,
            ]);
        }
        $this->getJson(route('dashboard.index'))
            ->assertOk()
            ->assertJsonPath('data.total_tickets', 5);
    }

    public function test_open_tickets_count_and_pagination(): void
    {
        $status = TicketStatus::factory()->create(['name' => 'open']);
        $category = TicketCategory::factory()->create();
        $priority = TicketPriority::factory()->create();
        for ($i = 0; $i < 15; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => User::factory()->create()->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => $priority->id,
                'ticket_status_id' => $status->id,
            ]);
        }
        Sanctum::actingAs($this->manager());
        $response = $this->getJson(route('dashboard.index'))->assertOk();
        $response->assertJsonPath('data.open_tickets.count', 15)
            ->assertJsonCount(10, 'data.open_tickets.tickets');
    }

    public function test_pending_tickets_count(): void
    {
        $pendingStatus = TicketStatus::factory()->create(['name' => 'pending']);
        $category = TicketCategory::factory()->create();
        $priority = TicketPriority::factory()->create();
        for ($i = 0; $i < 3; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => User::factory()->create()->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => $priority->id,
                'ticket_status_id' => $pendingStatus->id,
            ]);
        }
        Ticket::factory()->create([
            'user_id' => User::factory()->create()->id,
            'assigned_to' => User::factory()->create()->id,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => $priority->id,
            'ticket_status_id' => TicketStatus::factory()->create(['name' => 'open'])->id,
        ]);
        Sanctum::actingAs($this->manager());
        $this->getJson(route('dashboard.index'))
            ->assertOk()
            ->assertJsonPath('data.pending_tickets.count', 3);
    }

    public function test_closed_tickets_count(): void
    {
        $closedStatus = TicketStatus::factory()->create(['name' => 'closed']);
        $category = TicketCategory::factory()->create();
        $priority = TicketPriority::factory()->create();
        for ($i = 0; $i < 7; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => User::factory()->create()->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => $priority->id,
                'ticket_status_id' => $closedStatus->id,
            ]);
        }
        Sanctum::actingAs($this->manager());
        $this->getJson(route('dashboard.index'))
            ->assertOk()
            ->assertJsonPath('data.closed_tickets.count', 7);
    }

    public function test_count_by_priority_is_accurate(): void
    {
        $lowPriority = TicketPriority::factory()->create(['name' => 'low']);
        $highPriority = TicketPriority::factory()->create(['name' => 'high']);
        $category = TicketCategory::factory()->create();
        $status = TicketStatus::factory()->create(['name' => 'open']);
        for ($i = 0; $i < 4; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => User::factory()->create()->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => $lowPriority->id,
                'ticket_status_id' => $status->id,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => User::factory()->create()->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => $highPriority->id,
                'ticket_status_id' => $status->id,
            ]);
        }
        Sanctum::actingAs($this->manager());
        $response = $this->getJson(route('dashboard.index'))->assertOk();
        $byPriority = $response->json('data.by_priority');
        $lowCount = collect($byPriority)->firstWhere('priority.name', 'low')['count'];
        $highCount = collect($byPriority)->firstWhere('priority.name', 'high')['count'];
        $this->assertEquals(4, $lowCount);
        $this->assertEquals(2, $highCount);
    }

    public function test_count_by_category_is_accurate(): void
    {
        $technicalCategory = TicketCategory::factory()->create(['name' => 'Technical']);
        $paymentCategory = TicketCategory::factory()->create(['name' => 'Payment']);
        $priority = TicketPriority::factory()->create();
        $status = TicketStatus::factory()->create(['name' => 'open']);
        for ($i = 0; $i < 3; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => User::factory()->create()->id,
                'ticket_category_id' => $technicalCategory->id,
                'ticket_priority_id' => $priority->id,
                'ticket_status_id' => $status->id,
            ]);
        }
        Ticket::factory()->create([
            'user_id' => User::factory()->create()->id,
            'assigned_to' => User::factory()->create()->id,
            'ticket_category_id' => $paymentCategory->id,
            'ticket_priority_id' => $priority->id,
            'ticket_status_id' => $status->id,
        ]);
        Sanctum::actingAs($this->manager());
        $response = $this->getJson(route('dashboard.index'))->assertOk();
        $byCategory = $response->json('data.by_category');
        $techCount = collect($byCategory)->firstWhere('category.name', 'Technical')['count'];
        $payCount = collect($byCategory)->firstWhere('category.name', 'Payment')['count'];
        $this->assertEquals(3, $techCount);
        $this->assertEquals(1, $payCount);
    }

    public function test_count_per_expert_is_accurate(): void
    {
        $expert1 = User::factory()->create();
        $expert2 = User::factory()->create();
        $category = TicketCategory::factory()->create();
        $priority = TicketPriority::factory()->create();
        $status = TicketStatus::factory()->create(['name' => 'open']);
        for ($i = 0; $i < 3; $i++) {
            Ticket::factory()->create([
                'user_id' => User::factory()->create()->id,
                'assigned_to' => $expert1->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => $priority->id,
                'ticket_status_id' => $status->id,
            ]);
        }
        Ticket::factory()->create([
            'user_id' => User::factory()->create()->id,
            'assigned_to' => $expert2->id,
            'ticket_category_id' => $category->id,
            'ticket_priority_id' => $priority->id,
            'ticket_status_id' => $status->id,
        ]);
        Sanctum::actingAs($this->manager());
        $response = $this->getJson(route('dashboard.index'))->assertOk();
        $perExpert = $response->json('data.per_expert');
        $this->assertCount(2, $perExpert);
        $expert1Count = collect($perExpert)->firstWhere('expert.email', $expert1->email)['count'];
        $expert2Count = collect($perExpert)->firstWhere('expert.email', $expert2->email)['count'];
        $this->assertEquals(3, $expert1Count);
        $this->assertEquals(1, $expert2Count);
    }

    public function test_empty_database_returns_zero_counts(): void
    {
        Sanctum::actingAs($this->manager());
        $this->getJson(route('dashboard.index'))
            ->assertOk()
            ->assertJsonPath('data.total_tickets', 0)
            ->assertJsonPath('data.open_tickets.count', 0)
            ->assertJsonPath('data.pending_tickets.count', 0)
            ->assertJsonPath('data.closed_tickets.count', 0)
            ->assertJsonCount(0, 'data.by_priority')
            ->assertJsonCount(0, 'data.by_category')
            ->assertJsonCount(0, 'data.per_expert');
    }

    public function test_paginated_tickets_match_ticket_resource_shape(): void
    {
        $status = TicketStatus::factory()->create(['name' => 'open']);
        Ticket::factory()->create([
            'user_id' => User::factory()->create()->id,
            'assigned_to' => User::factory()->create()->id,
            'ticket_category_id' => TicketCategory::factory()->create()->id,
            'ticket_priority_id' => TicketPriority::factory()->create()->id,
            'ticket_status_id' => $status->id,
        ]);
        Sanctum::actingAs($this->manager());
        $this->getJson(route('dashboard.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'open_tickets' => [
                        'tickets' => [
                            '*' => ['subject', 'owner', 'category', 'priority', 'status', 'created_at', 'updated_at'],
                        ],
                    ],
                ],
            ]);
    }
}
