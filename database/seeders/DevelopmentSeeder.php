<?php

namespace Database\Seeders;

use App\Models\Access\Permission;
use App\Models\Setting\Setting;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketFile;
use App\Models\Ticket\TicketMessage;
use App\Models\Ticket\TicketPriority;
use App\Models\Ticket\TicketStatus;
use App\Models\User\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Access\Role;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->count(10)->create();
        foreach (['Technical', 'Payment', 'Support', 'General'] as $name) {
            TicketCategory::firstOrCreate([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => null,
                'status' => 1
            ]);
        }
        foreach (['low', 'medium', 'high', 'critical'] as $name) {
            TicketPriority::firstOrCreate([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => null,
                'status' => 1
            ]);
        }
        foreach (['open', 'pending', 'answered', 'closed'] as $name) {
            TicketStatus::firstOrCreate([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => null,
                'status' => 1
            ]);
        }
        Setting::factory()->count(10)->create();

        Ticket::factory()->count(5)
            ->has(

                TicketFile::factory()->count(3)
                    ->for($user->random()),
                'files'

            )
            ->has(

                TicketMessage::factory()->count(4)
                    ->for($user->random()),
                'messages'

            )->create([
                'user_id' => fn() => $user->random(),
                'assigned_to' => fn() => $user->random(),
                'ticket_category_id' => fn() => TicketCategory::inRandomOrder()->first()->id,
                'ticket_priority_id' => fn() => TicketPriority::inRandomOrder()->first()->id,
                'ticket_status_id' => fn() => TicketStatus::inRandomOrder()->first()->id
            ]);


        if (!Role::exists()) {
            $rolesNames = collect(['manager', 'support-specialist', 'regular-user', 'expert']);
            $roles =  $rolesNames->map(fn($name) => Role::factory()->create(['name' => $name]));
        }

        $entities = collect(['ticket', 'setting', 'users', 'service', 'access', 'conversation', 'ticket.category', 'ticket.priority', 'ticket.status']);
        $operations = collect(['create', 'view', 'viewAny', 'update', 'delete']);
        $entities->when(
            fn() => !Permission::exists()
        )->map(function ($entity) use ($operations) {
            $operations->map(function ($operation)  use ($entity) {
                Permission::factory()->create([
                    'name' => $entity . '.' . $operation
                ]);
            });
        });
    }
}
