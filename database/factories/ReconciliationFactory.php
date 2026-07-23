<?php

namespace Database\Factories;

use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use App\Models\Patient;
use App\Models\Reconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reconciliation>
 */
class ReconciliationFactory extends Factory
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
            'type' => ReconciliationType::Admission,
            'status' => ReconciliationStatus::Draft,
            'started_at' => now(),
            'bpmh_finalized' => false,
        ];
    }

    /**
     * Indicate an admission reconciliation.
     */
    public function admission(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ReconciliationType::Admission,
        ]);
    }

    /**
     * Indicate the reconciliation has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReconciliationStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
