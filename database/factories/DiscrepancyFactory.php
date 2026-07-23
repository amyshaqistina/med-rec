<?php

namespace Database\Factories;

use App\Enums\ClinicalSignificance;
use App\Enums\DiscrepancySeverity;
use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Models\Discrepancy;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discrepancy>
 */
class DiscrepancyFactory extends Factory
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
            'type' => DiscrepancyType::Omission,
            'severity' => DiscrepancySeverity::Major,
            'clinical_significance' => ClinicalSignificance::Unknown,
            'status' => DiscrepancyStatus::Identified,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate a critical-severity discrepancy.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => DiscrepancySeverity::Critical,
        ]);
    }

    /**
     * Indicate the discrepancy has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscrepancyStatus::Resolved,
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
