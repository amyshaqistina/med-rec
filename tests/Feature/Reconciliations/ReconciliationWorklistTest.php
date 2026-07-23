<?php

use App\Enums\ReconciliationStatus;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
use Livewire\Livewire;

test('worklist sorts high risk patients first', function () {
    $this->actingAs(User::factory()->create());

    $lowRiskPatient = Patient::factory()->lowRisk()->create();
    $highRiskPatient = Patient::factory()->highRisk()->create();

    Reconciliation::factory()->create(['patient_id' => $lowRiskPatient->id]);
    Reconciliation::factory()->create(['patient_id' => $highRiskPatient->id]);

    $response = Livewire::test('pages::reconciliations.index');

    $names = $response->viewData('reconciliations')->pluck('patient.full_name')->all();

    expect($names[0])->toBe($highRiskPatient->full_name);
});

test('worklist can be filtered by status', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    Reconciliation::factory()->create(['patient_id' => $patient->id, 'status' => ReconciliationStatus::Draft]);
    $completed = Reconciliation::factory()->completed()->create(['patient_id' => $patient->id]);

    $response = Livewire::test('pages::reconciliations.index')
        ->set('status', ReconciliationStatus::Completed->value);

    $ids = $response->viewData('reconciliations')->pluck('id')->all();

    expect($ids)->toBe([$completed->id]);
});
