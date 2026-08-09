<?php

namespace Database\Factories;

use App\Models\Model;
use App\Models\Setting\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->title(),
            'value' => fake()->text(10),
            'created_at' => fake()->time(),
            'updated_at' => now()
        ];
    }
}
