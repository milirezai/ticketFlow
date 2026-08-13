<?php

namespace Database\Factories;

use App\Models\Model;
use App\Models\Ticket\TicketPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class TicketPriorityFactory extends Factory
{
    protected $model = TicketPriority::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->words(2, true),
            'slug' => Str::slug($name),
            'description' => fake()->text(40),
            'status' => fake()->boolean(),
            'created_at' => fake()->time(),
            'updated_at' => now()
        ];
    }
}
