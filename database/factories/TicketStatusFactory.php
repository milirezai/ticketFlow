<?php

namespace Database\Factories;

use App\Models\Model;
use App\Models\Ticket\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class TicketStatusFactory extends Factory
{
    protected $model = TicketStatus::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->title(),
            'description' => fake()->text(40),
            'status' => fake()->boolean(),
            'created_at' => fake()->time(),
            'updated_at' => now()
        ];
    }
}
