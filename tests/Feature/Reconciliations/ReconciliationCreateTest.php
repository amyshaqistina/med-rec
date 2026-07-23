<?php

use App\Enums\ReconciliationStatus;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
use Livewire\Livewire;

test('technician can start a reconciliation for a patient', function () {
    $technician = User::factory()->create();
    $this->actingAs($technician);

    $patient = Patient::factory()->create();

    Livewire::test('pages::reconciliations.create', ['patient' => $patient])
        ->set('type', 'Admission')
        ->call('save');

    $reconciliation = Reconciliation::firstWhere('patient_id', $patient->id);

    expect($reconciliation)->not->toBeNull();
    expect($reconciliation->status)->toBe(ReconciliationStatus::Draft);
    expect($reconciliation->technician_id)->toBe($technician->id);
});

test('physician cannot create a reconciliation', function () {
    $this->actingAs(User::factory()->physician()->create());

    $patient = Patient::factory()->create();

    $this->get(route('reconciliations.create', $patient))->assertForbidden();
});
