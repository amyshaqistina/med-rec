<?php

namespace Database\Factories;

use App\Enums\AdherenceLevel;
use App\Enums\MedicationRoute;
use App\Enums\SourceType;
use App\Enums\TakingStatus;
use App\Models\MedicationHistory;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicationHistory>
 */
class MedicationHistoryFactory extends Factory
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
            'medication_name' => fake()->randomElement(['Amlodipine', 'Metformin', 'Lisinopril', 'Aspirin', 'Atorvastatin']),
            'strength' => '10mg',
            'dose_amount' => 10,
            'dose_unit' => 'mg',
            'route' => MedicationRoute::PO,
            'frequency' => 'Once Daily',
            'timing' => 'Morning',
            'indication' => fake()->words(2, true),
            'start_date' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'prescriber_name' => fake()->name(),
            'is_patient_taking' => TakingStatus::Yes,
            'adherence_level' => AdherenceLevel::Full,
            'source_type' => SourceType::PatientReport,
        ];
    }

    /**
     * Indicate the patient is no longer taking this medication.
     */
    public function notTaking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_patient_taking' => TakingStatus::No,
        ]);
    }

    /**
     * Indicate non-adherence with a reason.
     */
    public function nonAdherent(): static
    {
        return $this->state(fn (array $attributes) => [
            'adherence_level' => fake()->randomElement([AdherenceLevel::Partial, AdherenceLevel::None]),
            'non_adherence_reason' => 'Reports frequently forgetting doses.',
        ]);
    }
}
