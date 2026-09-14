<?php

use App\Enums\DiscrepancyStatus;
use App\Enums\PharmacistAssessment;
use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use App\Enums\SafetyCheckStatus;
use App\Enums\TakingStatus;
use App\Jobs\CheckMedicationSafetyJob;
use App\Models\Discrepancy;
use App\Models\LabResult;
use App\Models\MedicationCurrent;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('bpmh and current medications are both displayed', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);
    MedicationCurrent::factory()->create(['reconciliation_id' => $reconciliation->id, 'medication_name' => 'Clopidogrel']);

    $this->get(route('reconciliations.show', $reconciliation))
        ->assertOk()
        ->assertSee('Amlodipine')
        ->assertSee('Clopidogrel');
});

test('running the discrepancy check surfaces an omission', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Lisinopril']);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->call('runDiscrepancyCheck');

    expect($reconciliation->discrepancies()->count())->toBe(1);
});

test('technician cannot assess discrepancies', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $discrepancy = Discrepancy::factory()->create(['reconciliation_id' => $reconciliation->id]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->set('assessments.0.status', DiscrepancyStatus::Resolved->value)
        ->call('saveAssessments')
        ->assertForbidden();
});

test('pharmacist can assess discrepancies and complete verification', function () {
    $pharmacist = User::factory()->pharmacist()->create();
    $this->actingAs($pharmacist);

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $discrepancy = Discrepancy::factory()->create(['reconciliation_id' => $reconciliation->id]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->set('assessments.0.pharmacist_assessment', PharmacistAssessment::Unintended->value)
        ->set('assessments.0.status', DiscrepancyStatus::Resolved->value)
        ->call('saveAssessments')
        ->assertHasNoErrors()
        ->call('completeVerification');

    $discrepancy->refresh();
    expect($discrepancy->pharmacist_assessment)->toBe(PharmacistAssessment::Unintended);
    expect($discrepancy->status)->toBe(DiscrepancyStatus::Resolved);
    expect($discrepancy->resolved_by)->toBe($pharmacist->id);

    $reconciliation->refresh();
    expect($reconciliation->status)->toBe(ReconciliationStatus::Completed);
    expect($reconciliation->pharmacist_id)->toBe($pharmacist->id);
    expect($reconciliation->completed_at)->not->toBeNull();
});

test('reconciliation type can be changed inline while still a draft', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create([
        'patient_id' => $patient->id,
        'type' => ReconciliationType::Admission,
        'status' => ReconciliationStatus::Draft,
    ]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->set('type', ReconciliationType::Transfer->value)
        ->assertHasNoErrors();

    expect($reconciliation->fresh()->type)->toBe(ReconciliationType::Transfer);
});

test('technician without update rights cannot change the reconciliation type', function () {
    $this->actingAs(User::factory()->physician()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create([
        'patient_id' => $patient->id,
        'type' => ReconciliationType::Admission,
        'status' => ReconciliationStatus::Draft,
    ]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->set('type', ReconciliationType::Transfer->value)
        ->assertForbidden();

    expect($reconciliation->fresh()->type)->toBe(ReconciliationType::Admission);
});

test('an out-of-range lab result is flagged as abnormal on the reconciliation page', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    LabResult::factory()->create([
        'patient_id' => $patient->id,
        'test_name' => 'Potassium',
        'result_value' => '6.5',
        'reference_range' => '3.5-5.0',
    ]);

    $this->get(route('reconciliations.show', $reconciliation))
        ->assertOk()
        ->assertSee('Potassium')
        ->assertSee('Abnormal');
});

test('a lab result within range is flagged as normal', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    LabResult::factory()->create([
        'patient_id' => $patient->id,
        'test_name' => 'Sodium',
        'result_value' => '140',
        'reference_range' => '135-145',
    ]);

    $this->get(route('reconciliations.show', $reconciliation))
        ->assertOk()
        ->assertSee('Sodium')
        ->assertSee('Normal');
});

test('saving and checking discrepancies together persists medications and runs the check', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Metformin']);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->call('addCurrentRow')
        ->set('currentRows.0.medication_name', 'Metformin')
        ->call('saveAndCheckDiscrepancies')
        ->assertHasNoErrors();

    expect(MedicationCurrent::where('reconciliation_id', $reconciliation->id)->count())->toBe(1);
    expect($reconciliation->discrepancies()->count())->toBe(0);
});

test('a saved medication row shows as a listing with an edit action instead of an open form', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => 'Amlodipine',
    ]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->assertSee('Amlodipine')
        ->assertDontSee('Medication name')
        ->call('editRow', 0)
        ->assertSee('Medication name');
});

test('a newly added medication row always shows the fill-in form', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->call('addCurrentRow')
        ->assertSee('Medication name');
});

test('saving medications collapses rows back to the listing view', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->call('addCurrentRow')
        ->set('currentRows.0.medication_name', 'Metformin')
        ->set('currentRows.0.is_patient_taking', TakingStatus::Yes->value)
        ->call('saveCurrentMedications')
        ->assertDontSee('Medication name')
        ->assertSee('Metformin');
});

test('current medications become a locked record once the patient is discharged', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->discharged()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationCurrent::factory()->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => 'Amlodipine',
    ]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->assertSee('Amlodipine')
        ->assertSee('Read-only')
        ->assertDontSee('Save medications')
        ->call('editRow', 0)
        ->assertForbidden();
});

test('saving a new medication dispatches a safety check', function () {
    Queue::fake([CheckMedicationSafetyJob::class]);
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->call('addCurrentRow')
        ->set('currentRows.0.medication_name', 'Metformin')
        ->call('saveCurrentMedications');

    Queue::assertPushed(CheckMedicationSafetyJob::class, 1);
});

test('changing a medication dose re-dispatches the safety check and clears stale flags', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $medication = MedicationCurrent::factory()->safetyComplete([
        ['severity' => 'moderate', 'category' => 'interaction', 'explanation' => 'Stale finding.'],
    ])->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => 'Amlodipine',
        'dose_amount' => '5',
        'dose_unit' => 'mg',
    ]);

    Queue::fake([CheckMedicationSafetyJob::class]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->set('currentRows.0.dose_amount', '10')
        ->call('saveCurrentMedications');

    Queue::assertPushed(CheckMedicationSafetyJob::class, 1);
    expect($medication->fresh()->safety_check_status)->toBe(SafetyCheckStatus::Pending);
    expect($medication->fresh()->safety_flags)->toBeNull();
});

test('saving an unrelated field edit does not re-dispatch the safety check', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    MedicationCurrent::factory()->safetyComplete()->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => 'Amlodipine',
        'dose_amount' => '5',
        'dose_unit' => 'mg',
        'dose' => '5.00 mg',
        'frequency' => 'Once Daily',
        'indication' => 'Hypertension',
    ]);

    Queue::fake([CheckMedicationSafetyJob::class]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->set('currentRows.0.indication', 'Blood pressure control')
        ->call('saveCurrentMedications');

    Queue::assertNotPushed(CheckMedicationSafetyJob::class);
});

test('the re-check action resets status to pending and dispatches a new safety check', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    $medication = MedicationCurrent::factory()->safetyUnavailable()->create([
        'reconciliation_id' => $reconciliation->id,
        'medication_name' => 'Amlodipine',
    ]);

    Queue::fake([CheckMedicationSafetyJob::class]);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->call('recheckMedicationSafety', $medication->id);

    Queue::assertPushed(CheckMedicationSafetyJob::class, 1);
    expect($medication->fresh()->safety_check_status)->toBe(SafetyCheckStatus::Pending);
});

test('resolved discrepancies are hidden until toggled visible', function () {
    $this->markTestSkipped('Discrepancies card is temporarily hidden from the reconciliation page pending the discrepancy-check fix.');
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $reconciliation = Reconciliation::factory()->create(['patient_id' => $patient->id]);
    Discrepancy::factory()->create(['reconciliation_id' => $reconciliation->id, 'description' => 'Active omission of Lisinopril']);
    Discrepancy::factory()->resolved()->create(['reconciliation_id' => $reconciliation->id, 'description' => 'Resolved duplicate entry']);

    Livewire::test('pages::reconciliations.show', ['reconciliation' => $reconciliation])
        ->assertSee('Active omission of Lisinopril')
        ->assertDontSee('Resolved duplicate entry')
        ->set('showResolvedDiscrepancies', true)
        ->assertSee('Resolved duplicate entry');
});
