<?php

namespace Database\Factories;

use App\Models\LabResult;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabResult>
 */
class LabResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'test_name' => fake()->randomElement(['Hemoglobin', 'Creatinine', 'Potassium', 'Sodium', 'WBC']),
            'result_value' => (string) fake()->randomFloat(1, 1, 200),
            'unit' => fake()->randomElement(['g/dL', 'mmol/L', 'mg/dL']),
            'reference_range' => '—',
            'taken_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
