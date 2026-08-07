<?php

use App\Enums\DiscrepancyStatus;
use App\Enums\PharmacistAssessment;
use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use App\Models\Discrepancy;
use App\Models\LabResult;
use App\Models\MedicationCurrent;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
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

test('resolved discrepancies are hidden until toggled visible', function () {
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
