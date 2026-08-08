<?php

namespace Database\Factories;

use App\Models\Model;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->title(),
            'created_at' => now(),
            'updated_at' => fake()->time(),
        ];
    }
}
