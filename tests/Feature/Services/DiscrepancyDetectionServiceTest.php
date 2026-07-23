<?php

use App\Enums\DiscrepancySeverity;
use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\MedicationRoute;
use App\Enums\PharmacistAssessment;
use App\Enums\TakingStatus;
use App\Models\MedicationCurrent;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Services\DiscrepancyDetectionService;

function detector(): DiscrepancyDetectionService
{
    return app(DiscrepancyDetectionService::class);
}

test('medication in bpmh but not current is an omission', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Lisinopril']);

    $discrepancies = detector()->sync($reconciliation);

    expect($discrepancies)->toHaveCount(1);
    expect($discrepancies->first()->type)->toBe(DiscrepancyType::Omission);
    expect($discrepancies->first()->severity)->toBe(DiscrepancySeverity::Major);
});

test('medication in current but not bpmh is a commission', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationCurrent::factory()->create(['reconciliation_id' => $reconciliation->id, 'medication_name' => 'Clopidogrel']);

    $discrepancies = detector()->sync($reconciliation);

    expect($discrepancies)->toHaveCount(1);
    expect($discrepancies->first()->type)->toBe(DiscrepancyType::Commission);
});

test('medications the patient is not taking are excluded from bpmh comparison', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->notTaking()->create(['patient_id' => $patient->id, 'medication_name' => 'Ibuprofen']);

    $discrepancies = detector()->sync($reconciliation);

    expect($discrepancies)->toHaveCount(0);
});

test('matched medication with no changes produces no discrepancy', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id,
        'medication_name' => 'Amlodipine',
        'dose_amount' => 5,
        'dose_unit' => 'mg',
        'frequency' => 'Once Daily',
        'route' => MedicationRoute::PO,
    ]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => 'Amlodipine',
        'dose' => '5mg',
        'frequency' => 'Once Daily',
        'route' => MedicationRoute::PO,
    ]);

    expect(detector()->sync($reconciliation))->toHaveCount(0);
});

test('dose decrease over 25 percent is major, at or under is minor', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id, 'medication_name' => 'Metformin', 'dose_amount' => 100, 'dose_unit' => 'mg',
    ]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id, 'medication_name' => 'Metformin', 'dose' => '74mg',
    ]);

    $discrepancy = detector()->sync($reconciliation)->firstWhere('type', DiscrepancyType::DoseChange);

    expect($discrepancy->severity)->toBe(DiscrepancySeverity::Major);
});

test('dose decrease of 20 percent is minor', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id, 'medication_name' => 'Metformin', 'dose_amount' => 100, 'dose_unit' => 'mg',
    ]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id, 'medication_name' => 'Metformin', 'dose' => '80mg',
    ]);

    $discrepancy = detector()->sync($reconciliation)->firstWhere('type', DiscrepancyType::DoseChange);

    expect($discrepancy->severity)->toBe(DiscrepancySeverity::Minor);
});

test('dose increase over 50 percent is major', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id, 'medication_name' => 'Metformin', 'dose_amount' => 100, 'dose_unit' => 'mg',
    ]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id, 'medication_name' => 'Metformin', 'dose' => '151mg',
    ]);

    $discrepancy = detector()->sync($reconciliation)->firstWhere('type', DiscrepancyType::DoseChange);

    expect($discrepancy->severity)->toBe(DiscrepancySeverity::Major);
});

test('dose increase of 40 percent is minor', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id, 'medication_name' => 'Metformin', 'dose_amount' => 100, 'dose_unit' => 'mg',
    ]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id, 'medication_name' => 'Metformin', 'dose' => '140mg',
    ]);

    $discrepancy = detector()->sync($reconciliation)->firstWhere('type', DiscrepancyType::DoseChange);

    expect($discrepancy->severity)->toBe(DiscrepancySeverity::Minor);
});

test('frequency change is detected as minor', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id, 'medication_name' => 'Aspirin', 'frequency' => 'Once Daily',
    ]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id, 'medication_name' => 'Aspirin', 'frequency' => 'Twice Daily',
    ]);

    $discrepancy = detector()->sync($reconciliation)->firstWhere('type', DiscrepancyType::FrequencyChange);

    expect($discrepancy)->not->toBeNull();
    expect($discrepancy->severity)->toBe(DiscrepancySeverity::Minor);
});

test('route change is detected as minor', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create([
        'patient_id' => $patient->id, 'medication_name' => 'Furosemide', 'route' => MedicationRoute::PO,
    ]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id, 'medication_name' => 'Furosemide', 'route' => MedicationRoute::IV,
    ]);

    $discrepancy = detector()->sync($reconciliation)->firstWhere('type', DiscrepancyType::RouteChange);

    expect($discrepancy)->not->toBeNull();
    expect($discrepancy->severity)->toBe(DiscrepancySeverity::Minor);
});

test('duplicate medications in current list are flagged', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationCurrent::factory()->create(['reconciliation_id' => $reconciliation->id, 'medication_name' => 'Paracetamol']);
    MedicationCurrent::factory()->create(['reconciliation_id' => $reconciliation->id, 'medication_name' => 'paracetamol']);

    $discrepancies = detector()->sync($reconciliation);

    expect($discrepancies->where('type', DiscrepancyType::Duplication))->toHaveCount(1);
});

test('sync is idempotent when run twice with no changes', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Lisinopril']);

    $first = detector()->sync($reconciliation);
    $second = detector()->sync($reconciliation);

    expect($second)->toHaveCount(1);
    expect($second->first()->id)->toBe($first->first()->id);
});

test('recompute preserves pharmacist assessment on a still-matching discrepancy', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Lisinopril']);

    $discrepancy = detector()->sync($reconciliation)->first();
    $discrepancy->update([
        'pharmacist_assessment' => PharmacistAssessment::Unintended,
        'status' => DiscrepancyStatus::UnderReview,
        'clinical_note' => 'Discussed with physician.',
    ]);

    detector()->sync($reconciliation);

    $discrepancy->refresh();
    expect($discrepancy->pharmacist_assessment)->toBe(PharmacistAssessment::Unintended);
    expect($discrepancy->status)->toBe(DiscrepancyStatus::UnderReview);
    expect($discrepancy->clinical_note)->toBe('Discussed with physician.');
});

test('recompute deletes a stale discrepancy once lists reconcile', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Lisinopril']);

    expect(detector()->sync($reconciliation))->toHaveCount(1);

    MedicationCurrent::factory()->create(['reconciliation_id' => $reconciliation->id, 'medication_name' => 'Lisinopril']);

    expect(detector()->sync($reconciliation))->toHaveCount(0);
});
