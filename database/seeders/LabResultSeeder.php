<?php

namespace Database\Seeders;

use App\Models\LabResult;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class LabResultSeeder extends Seeder
{
    /**
     * Test panel => [unit, reference range, min value, max value, decimals].
     *
     * @var array<string, array{0: string, 1: string, 2: float, 3: float, 4: int}>
     */
    private const TESTS = [
        'Hemoglobin' => ['g/dL', '13.0–17.0', 8.0, 18.0, 1],
        'Creatinine' => ['mg/dL', '0.6–1.3', 0.4, 3.5, 2],
        'Potassium' => ['mmol/L', '3.5–5.0', 2.8, 6.2, 1],
        'Sodium' => ['mmol/L', '135–145', 125, 150, 0],
        'WBC' => ['x10^9/L', '4.0–11.0', 2.0, 18.0, 1],
    ];

    /**
     * Seed 2-4 lab draws per patient, each with several tests taken at the same time.
     */
    public function run(): void
    {
        Patient::all()->each(function (Patient $patient) {
            $testNames = array_keys(self::TESTS);

            foreach (range(1, fake()->numberBetween(2, 4)) as $draw) {
                $takenAt = now()
                    ->subDays(fake()->numberBetween(0, 60))
                    ->setTime(fake()->numberBetween(6, 10), fake()->randomElement([0, 15, 30, 45]));

                foreach (fake()->randomElements($testNames, fake()->numberBetween(3, 5)) as $testName) {
                    [$unit, $range, $min, $max, $decimals] = self::TESTS[$testName];

                    LabResult::create([
                        'patient_id' => $patient->id,
                        'test_name' => $testName,
                        'result_value' => (string) fake()->randomFloat($decimals, $min, $max),
                        'unit' => $unit,
                        'reference_range' => $range,
                        'taken_at' => $takenAt,
                        'created_by' => $patient->created_by,
                    ]);
                }
            }
        });
    }
}
