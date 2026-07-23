<?php

namespace Database\Factories;

use App\Enums\MedicationRoute;
use App\Models\MedicationCurrent;
use App\Models\Reconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicationCurrent>
 */
class MedicationCurrentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reconciliation_id' => Reconciliation::factory(),
            'medication_name' => fake()->randomElement(['Amlodipine', 'Metformin', 'Lisinopril', 'Aspirin', 'Atorvastatin']),
            'dose' => '10mg',
            'route' => MedicationRoute::PO,
            'frequency' => 'Once Daily',
            'indication' => fake()->words(2, true),
            'ordered_by' => fake()->name(),
            'order_date' => now()->toDateString(),
        ];
    }
}
