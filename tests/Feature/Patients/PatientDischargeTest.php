<?php

use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('discharge marks patient as discharged with a discharge date', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('discharge');

    $patient->refresh();

    expect($patient->status)->toBe(PatientStatus::Discharged);
    expect($patient->discharge_date)->not->toBeNull();
});
