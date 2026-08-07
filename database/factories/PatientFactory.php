<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\HepaticFunction;
use App\Enums\PatientStatus;
use App\Enums\PregnancyStatus;
use App\Enums\RenalFunction;
use App\Models\Patient;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * @var array<int, string>
     */
    private const DIAGNOSES = [
        'Hypertension',
        'Type 2 Diabetes Mellitus',
        'Chest Pain',
        'Community-Acquired Pneumonia',
        'Acute Kidney Injury',
        'Chronic Obstructive Pulmonary Disease',
        'Congestive Heart Failure',
        'Urinary Tract Infection',
        'Ischaemic Stroke',
        'Gastroenteritis',
        'Sepsis',
        'Atrial Fibrillation',
        'Osteoarthritis',
        'Post-Operative Recovery',
        'Dengue Fever',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-90 years', '-18 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'contact_primary' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address_street' => fake()->streetAddress(),
            'address_city' => fake()->city(),
            'address_postcode' => fake()->postcode(),
            'address_state' => fake()->state(),
            'admission_date' => now(),
            'ward_id' => null,
            'primary_diagnosis' => fake()->randomElement(self::DIAGNOSES),
            'allergies' => null,
            'renal_function' => RenalFunction::Normal,
            'egfr' => fake()->randomFloat(2, 60, 120),
            'hepatic_function' => HepaticFunction::Normal,
            'pregnancy_status' => PregnancyStatus::Unknown,
            'status' => PatientStatus::Active,
        ];
    }

    /**
     * Guarantee a High risk classification (age alone is sufficient).
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-95 years', '-70 years'),
            'egfr' => fake()->randomFloat(2, 20, 55),
        ]);
    }

    /**
     * Guarantee a Low risk classification.
     */
    public function lowRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-40 years', '-25 years'),
            'allergies' => null,
            'renal_function' => RenalFunction::Normal,
            'egfr' => fake()->randomFloat(2, 80, 120),
            'hepatic_function' => HepaticFunction::Normal,
            'pregnancy_status' => PregnancyStatus::NotPregnant,
        ]);
    }

    /**
     * Indicate the patient has been discharged.
     */
    public function discharged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PatientStatus::Discharged,
            'discharge_date' => now(),
        ]);
    }

    /**
     * Assign the patient to the given ward.
     */
    public function inWard(Ward $ward): static
    {
        return $this->state(fn (array $attributes) => [
            'ward_id' => $ward->id,
        ]);
    }
}
