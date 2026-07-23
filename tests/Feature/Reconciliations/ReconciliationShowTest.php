<?php

use App\Enums\DiscrepancyStatus;
use App\Enums\PharmacistAssessment;
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
    $discrepancy = \App\Models\Discrepancy::factory()->create(['reconciliation_id' => $reconciliation->id]);

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
    $discrepancy = \App\Models\Discrepancy::factory()->create(['reconciliation_id' => $reconciliation->id]);

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
    expect($reconciliation->status)->toBe(\App\Enums\ReconciliationStatus::Completed);
    expect($reconciliation->pharmacist_id)->toBe($pharmacist->id);
    expect($reconciliation->completed_at)->not->toBeNull();
});
