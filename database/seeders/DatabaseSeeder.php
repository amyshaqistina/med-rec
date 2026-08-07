<?php

namespace Database\Seeders;

use App\Enums\MedicationRoute;
use App\Enums\ReconciliationType;
use App\Enums\RenalFunction;
use App\Enums\TakingStatus;
use App\Enums\UserRole;
use App\Models\MedicationCurrent;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
use App\Models\Ward;
use App\Services\DiscrepancyDetectionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    /**
     * Ward name => [department, color, bed capacity].
     *
     * @var array<string, array{0: string, 1: string, 2: int}>
     */
    private const WARDS = [
        'Ward 1' => ['Emergency Ward', 'red', 12],
        'Ward 2' => ['General Ward', 'emerald', 12],
        'Ward 3' => ['Pediatric Ward', 'blue', 12],
        'Ward 4' => ['Surgical Ward', 'purple', 12],
        'Ward 5' => ['Cardiology Ward', 'amber', 12],
        'Ward 6' => ['Medical Ward', 'teal', 12],
        'Ward 7' => ['Orthopedic Ward', 'orange', 12],
        'Ward 8' => ['Oncology Ward', 'pink', 12],
        'Ward 9' => ['Intensive Care Unit', 'cyan', 12],
        'Ward 10' => ['Maternity Ward', 'lime', 12],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::Admin,
        ]);

        $usersByRole = [];

        foreach (UserRole::cases() as $role) {
            $usersByRole[$role->value] = User::factory()->create([
                'name' => $role->label(),
                'email' => $role->value.'@medrec.test',
                'role' => $role,
            ]);
        }

        if (app()->environment('local')) {
            $technician = $usersByRole[UserRole::Technician->value];
            $pharmacist = $usersByRole[UserRole::Pharmacist->value];

            $wards = $this->seedWards();
            $this->seedWardPatients($wards, $technician);
            $this->seedDemoReconciliationScenario($technician, $pharmacist, $wards->firstWhere('name', 'Ward 6'));
        }
    }

    /**
     * Create the 10 hospital wards shown on the Ward Dashboard.
     *
     * @return Collection<int, Ward>
     */
    private function seedWards()
    {
        return collect(self::WARDS)->map(function (array $details, string $name) {
            [$department, $color, $bedCapacity] = $details;

            return Ward::create([
                'name' => $name,
                'department' => $department,
                'color' => $color,
                'bed_capacity' => $bedCapacity,
            ]);
        })->values();
    }

    /**
     * Seed 10 complete patient records into each ward.
     *
     * @param  Collection<int, Ward>  $wards
     */
    private function seedWardPatients($wards, User $technician): void
    {
        $medications = [
            ['name' => 'Amlodipine', 'strength' => '5mg', 'dose' => 5, 'unit' => 'mg', 'freq' => 'Once Daily', 'indication' => 'Hypertension'],
            ['name' => 'Metformin', 'strength' => '500mg', 'dose' => 500, 'unit' => 'mg', 'freq' => 'Twice Daily', 'indication' => 'Diabetes'],
            ['name' => 'Atorvastatin', 'strength' => '20mg', 'dose' => 20, 'unit' => 'mg', 'freq' => 'Once Daily', 'indication' => 'Hyperlipidemia'],
            ['name' => 'Aspirin', 'strength' => '75mg', 'dose' => 75, 'unit' => 'mg', 'freq' => 'Once Daily', 'indication' => 'Cardioprotection'],
            ['name' => 'Omeprazole', 'strength' => '20mg', 'dose' => 20, 'unit' => 'mg', 'freq' => 'Once Daily', 'indication' => 'GERD'],
            ['name' => 'Paracetamol', 'strength' => '500mg', 'dose' => 500, 'unit' => 'mg', 'freq' => 'PRN', 'indication' => 'Pain'],
        ];

        foreach ($wards as $ward) {
            preg_match('/\d+/', $ward->name, $wardNumberMatch);
            $wardNumber = $wardNumberMatch[0] ?? $ward->id;

            Patient::factory()
                ->count(10)
                ->sequence(fn ($sequence) => ['bed_no' => sprintf('%s-%02d', $wardNumber, $sequence->index + 1)])
                ->create([
                    'ward_id' => $ward->id,
                    'created_by' => $technician->id,
                ])
                ->each(function (Patient $patient) use ($medications, $technician) {
                    foreach (fake()->randomElements($medications, fake()->numberBetween(2, 4)) as $medication) {
                        MedicationHistory::create([
                            'patient_id' => $patient->id,
                            'medication_name' => $medication['name'],
                            'strength' => $medication['strength'],
                            'dose_amount' => $medication['dose'],
                            'dose_unit' => $medication['unit'],
                            'route' => MedicationRoute::PO,
                            'frequency' => $medication['freq'],
                            'indication' => $medication['indication'],
                            'start_date' => now()->subYears(fake()->numberBetween(1, 5)),
                            'is_patient_taking' => TakingStatus::Yes,
                            'source_type' => 'Patient_Report',
                            'reliability_rating' => 'Probable',
                            'created_by' => $technician->id,
                        ]);
                    }
                });
        }
    }

    /**
     * Seed a single high-risk patient with a BPMH, a current medication list, and an
     * auto-detected discrepancy for every discrepancy type, so the whole reconciliation
     * workflow can be exercised manually in the browser without any data entry.
     */
    private function seedDemoReconciliationScenario(User $technician, User $pharmacist, ?Ward $ward): void
    {
        $patient = Patient::create([
            'first_name' => 'Ahmad',
            'last_name' => 'Bin Ali',
            'date_of_birth' => now()->subYears(72),
            'gender' => 'Male',
            'contact_primary' => '+60 12-345 6789',
            'email' => 'ahmad.bin.ali@example.com',
            'admission_date' => now()->subDay(),
            'ward_id' => $ward?->id,
            'bed_no' => '6-11',
            'primary_diagnosis' => 'Hypertension with acute kidney injury',
            'allergies' => 'Penicillin (anaphylaxis), NSAIDs (rash)',
            'renal_function' => RenalFunction::MildImpairment,
            'egfr' => 55,
            'created_by' => $technician->id,
        ]);

        $reconciliation = Reconciliation::create([
            'patient_id' => $patient->id,
            'type' => ReconciliationType::Admission,
            'started_at' => now()->subHours(2),
            'technician_id' => $technician->id,
            'pharmacist_id' => $pharmacist->id,
            'bpmh_finalized' => true,
        ]);

        $bpmh = [
            ['medication_name' => 'Amlodipine', 'strength' => '5mg', 'dose_amount' => 5, 'dose_unit' => 'mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Hypertension'],
            ['medication_name' => 'Lisinopril', 'strength' => '10mg', 'dose_amount' => 10, 'dose_unit' => 'mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Hypertension / renal protection'],
            ['medication_name' => 'Aspirin', 'strength' => '75mg', 'dose_amount' => 75, 'dose_unit' => 'mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Cardioprotection'],
            ['medication_name' => 'Metformin', 'strength' => '500mg', 'dose_amount' => 500, 'dose_unit' => 'mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Diabetes'],
            ['medication_name' => 'Furosemide', 'strength' => '40mg', 'dose_amount' => 40, 'dose_unit' => 'mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Fluid overload'],
        ];

        foreach ($bpmh as $medication) {
            MedicationHistory::create([
                ...$medication,
                'patient_id' => $patient->id,
                'reconciliation_id' => $reconciliation->id,
                'start_date' => now()->subYears(2),
                'prescriber_name' => 'Dr. Ramli, Klinik Kesihatan',
                'is_patient_taking' => TakingStatus::Yes,
                'source_type' => 'Patient_Report',
                'reliability_rating' => 'Probable',
                'created_by' => $technician->id,
            ]);
        }

        $current = [
            // Unchanged — no discrepancy.
            ['medication_name' => 'Amlodipine', 'dose' => '5mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Hypertension'],
            // Lisinopril intentionally omitted -> Omission.
            // Dose increased >50% -> Dose_Change (Major).
            ['medication_name' => 'Aspirin', 'dose' => '150mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Cardioprotection'],
            // Frequency changed -> Frequency_Change (Minor).
            ['medication_name' => 'Metformin', 'dose' => '500mg', 'route' => MedicationRoute::PO, 'frequency' => 'Twice Daily', 'indication' => 'Diabetes'],
            // Route changed -> Route_Change (Minor).
            ['medication_name' => 'Furosemide', 'dose' => '40mg', 'route' => MedicationRoute::IV, 'frequency' => 'Once Daily', 'indication' => 'Fluid overload'],
            // Not in BPMH -> Commission.
            ['medication_name' => 'Clopidogrel', 'dose' => '75mg', 'route' => MedicationRoute::PO, 'frequency' => 'Once Daily', 'indication' => 'Post-PCI dual antiplatelet therapy'],
            // Entered twice -> Duplication.
            ['medication_name' => 'Paracetamol', 'dose' => '500mg', 'route' => MedicationRoute::PO, 'frequency' => 'PRN', 'indication' => 'Pain'],
            ['medication_name' => 'paracetamol', 'dose' => '500mg', 'route' => MedicationRoute::PO, 'frequency' => 'PRN', 'indication' => 'Pain'],
        ];

        foreach ($current as $medication) {
            MedicationCurrent::create([
                ...$medication,
                'reconciliation_id' => $reconciliation->id,
                'ordered_by' => 'Dr. Ramli',
                'order_date' => now()->toDateString(),
            ]);
        }

        app(DiscrepancyDetectionService::class)->sync($reconciliation->fresh());
    }
}
