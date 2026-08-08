<?php

namespace Database\Factories;

use App\Models\Model;
use App\Models\Ticket\TicketFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class TicketFileFactory extends Factory
{
    protected $model = TicketFile::class;
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
            'size' => fake()->numberBetween(0,2000),
            'status' => fake()->boolean(),
            'created_at' => fake()->time(),
            'updated_at' => now()
        ];
    }
}
