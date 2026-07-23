<?php

namespace Database\Factories;

use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Ward '.fake()->unique()->numberBetween(1, 999),
            'department' => fake()->randomElement(['General Ward', 'Medical Ward', 'Surgical Ward', 'ICU']),
            'bed_capacity' => 20,
            'color' => 'blue',
        ];
    }
}
