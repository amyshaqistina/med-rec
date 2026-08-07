<?php

use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use App\Models\Patient;
use App\Models\Reconciliation;
use App\Models\User;
use Livewire\Livewire;

test('technician can start a reconciliation directly from the patient page', function () {
    $technician = User::factory()->create();
    $this->actingAs($technician);

    $patient = Patient::factory()->create();

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('startReconciliation');

    $reconciliation = Reconciliation::firstWhere('patient_id', $patient->id);

    expect($reconciliation)->not->toBeNull();
    expect($reconciliation->status)->toBe(ReconciliationStatus::Draft);
    expect($reconciliation->type)->toBe(ReconciliationType::Admission);
    expect($reconciliation->technician_id)->toBe($technician->id);
});

test('physician cannot start a reconciliation', function () {
    $this->actingAs(User::factory()->physician()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('startReconciliation')
        ->assertForbidden();
});
