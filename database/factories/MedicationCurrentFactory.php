<?php

namespace Database\Factories;

use App\Enums\MedicationRoute;
use App\Enums\SafetyCheckStatus;
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

    /**
     * @param  array<int, array<string, mixed>>  $flags
     */
    public function safetyComplete(array $flags = []): static
    {
        return $this->state(fn (array $attributes) => [
            'safety_check_status' => SafetyCheckStatus::Complete,
            'safety_label_data' => [
                'boxed_warning' => null,
                'contraindications' => null,
                'warnings_and_precautions' => null,
                'drug_interactions' => null,
                'dosage_and_administration' => null,
            ],
            'safety_flags' => $flags,
            'safety_checked_at' => now(),
        ]);
    }

    public function safetyUnavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'safety_check_status' => SafetyCheckStatus::Unavailable,
            'safety_label_data' => null,
            'safety_flags' => null,
            'safety_checked_at' => now(),
        ]);
    }

    public function safetyFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'safety_check_status' => SafetyCheckStatus::Failed,
            'safety_label_data' => null,
            'safety_flags' => null,
            'safety_checked_at' => now(),
        ]);
    }
}
