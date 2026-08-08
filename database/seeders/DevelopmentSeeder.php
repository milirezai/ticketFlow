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

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->count(10)->create();
        $ticketCategory = TicketCategory::factory()->count(10)->create();
        $ticketPriority = TicketPriority::factory()->count(10)->create();
        $ticketStatus = TicketStatus::factory()->count(10)->create();
        Setting::factory()->count(10)->create();

        Ticket::factory()->count(20)
            ->has(

                TicketFile::factory()->count(3)
                ->for($user->random()),'files'

            )
            ->has(

                TicketMessage::factory()->count(4)
                    ->for($user->random()),'messages'

            )->create([
                'user_id' => fn() => $user->random(),
                'assigned_to' => fn() => $user->random(),
                'ticket_category_id' => fn() =>$ticketCategory->random(),
                'ticket_priority_id' => fn() => $ticketPriority->random(),
                'ticket_status_id' => fn() => $ticketStatus->random()
            ]);


        if (!Role::exists())
        {
            $roles = collect(['manager','support_specialist','user']);
            $roles = Role::factory()->count($roles->count())
                ->create([
                    'name' => fn() => $roles->random(),
                ]);
        }

            $entities = collect( ['ticket', 'setting', 'users','service','access','conversation']);
            $operations = collect(['create','view','update','delete']);
           $entities->when(
                fn() => !Permission::exists()
            )->map(function ($entity) use ($operations){
                $operations->map(function ($operation)  use ($entity){
                     Permission::factory()->create([
                        'name' => $entity.'.'.$operation
                    ]);
                });
            });



    }
}
