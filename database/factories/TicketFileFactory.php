<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class TicketFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => fake()->filePath(),
            'type' => fake()->fileExtension(),
            'size' => fake()->numberBetween(0,3000),
            'status' => fake()->boolean(),
            'created_at' => fake()->time(),
            'updated_at' => now()
        ];
    }
}
