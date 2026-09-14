<?php

use App\Enums\HepaticFunction;
use App\Enums\RenalFunction;
use App\Enums\SafetyCheckStatus;
use App\Jobs\SynthesizeMedicationSafetyContextJob;
use App\Models\LabResult;
use App\Models\MedicationCurrent;
use App\Models\Patient;
use App\Models\Reconciliation;

/**
 * @param  array<string, mixed>  $label
 */
function medicationWithLabel(Reconciliation $reconciliation, string $name, array $label): MedicationCurrent
{
    return MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => $name,
        'safety_check_status' => SafetyCheckStatus::Complete,
        'safety_label_data' => array_merge([
            'boxed_warning' => null,
            'contraindications' => null,
            'warnings_and_precautions' => null,
            'drug_interactions' => null,
            'dosage_and_administration' => null,
        ], $label),
    ]);
}

test('a patient allergy documented against the drug is flagged high severity', function () {
    $patient = Patient::factory()->create(['allergies' => 'Penicillin, Sulfa drugs']);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $medication = medicationWithLabel($reconciliation, 'Amoxicillin', [
        'contraindications' => 'Do not use in patients with known Penicillin hypersensitivity.',
    ]);

    (new SynthesizeMedicationSafetyContextJob($medication))->handle();

    $flags = $medication->fresh()->safety_flags;
    expect($flags)->toHaveCount(1);
    expect($flags[0]['category'])->toBe('allergy');
    expect($flags[0]['severity'])->toBe('high');
});

test('a diagnosis matching the label contraindications is flagged', function () {
    $patient = Patient::factory()->create(['primary_diagnosis' => 'Severe renal impairment']);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $medication = medicationWithLabel($reconciliation, 'Ibuprofen', [
        'contraindications' => 'Contraindicated in patients with severe impairment of renal function.',
    ]);

    (new SynthesizeMedicationSafetyContextJob($medication))->handle();

    $flags = collect($medication->fresh()->safety_flags);
    expect($flags->where('category', 'diagnosis')->isNotEmpty())->toBeTrue();
});

test('an abnormal eGFR is flagged when the label discusses renal dosing', function () {
    $patient = Patient::factory()->create(['renal_function' => RenalFunction::Normal]);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    LabResult::factory()->create([
        'patient_id' => $patient->id,
        'test_name' => 'eGFR',
        'result_value' => '25',
        'reference_range' => '>60',
        'taken_at' => now(),
    ]);
    $medication = medicationWithLabel($reconciliation, 'Metformin', [
        'warnings_and_precautions' => 'Dose adjustment required based on renal function; monitor eGFR.',
    ]);

    (new SynthesizeMedicationSafetyContextJob($medication))->handle();

    $flags = collect($medication->fresh()->safety_flags);
    $labFlag = $flags->firstWhere('category', 'lab');
    expect($labFlag)->not->toBeNull();
    expect($labFlag['severity'])->toBe('high');
});

test('another current medication named in the interactions text is flagged', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => 'Warfarin',
    ]);
    $medication = medicationWithLabel($reconciliation, 'Aspirin', [
        'drug_interactions' => 'Concurrent use with Warfarin increases bleeding risk.',
    ]);

    (new SynthesizeMedicationSafetyContextJob($medication))->handle();

    $flags = collect($medication->fresh()->safety_flags);
    expect($flags->where('category', 'interaction')->isNotEmpty())->toBeTrue();
});

test('a boxed warning is always flagged regardless of patient-specific correlation', function () {
    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $medication = medicationWithLabel($reconciliation, 'Rosiglitazone', [
        'boxed_warning' => 'May increase risk of cardiovascular events.',
    ]);

    (new SynthesizeMedicationSafetyContextJob($medication))->handle();

    $flags = collect($medication->fresh()->safety_flags);
    $labelWarning = $flags->firstWhere('category', 'label_warning');
    expect($labelWarning)->not->toBeNull();
    expect($labelWarning['severity'])->toBe('high');
});

test('no matching allergy, diagnosis, lab, or interaction data produces zero fabricated flags', function () {
    $patient = Patient::factory()->create([
        'allergies' => null,
        'known_adrs' => null,
        'primary_diagnosis' => 'Routine checkup',
        'renal_function' => RenalFunction::Normal,
        'hepatic_function' => HepaticFunction::Normal,
    ]);
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $medication = medicationWithLabel($reconciliation, 'Paracetamol', [
        'contraindications' => 'Known hypersensitivity to paracetamol.',
        'warnings_and_precautions' => 'Avoid exceeding recommended dose.',
    ]);

    (new SynthesizeMedicationSafetyContextJob($medication))->handle();

    expect($medication->fresh()->safety_flags)->toBe([]);
});
