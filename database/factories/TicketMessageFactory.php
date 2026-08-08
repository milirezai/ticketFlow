<?php

namespace Database\Factories;

use App\Models\Model;
use App\Models\Ticket\TicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class TicketMessageFactory extends Factory
{
    protected $model = TicketMessage::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->text(30),
            'status' => fake()->boolean(),
            'created_at' => fake()->time(),
            'updated_at' => now()
        ];
    }
}
