<?php

namespace Database\Factories;

use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bed>
 */
class BedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ward_id' => Ward::factory(),
            // Starts well above any realistic bed_capacity to avoid colliding with the
            // beds a ward auto-creates for itself via WardObserver.
            'bed_no' => fake()->unique()->numberBetween(1000, 9000),
        ];
    }
}
