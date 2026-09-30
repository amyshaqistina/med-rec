<?php

use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
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

test('discharge frees the patient\'s bed', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    $bed = $ward->beds()->first();
    $patient = Patient::factory()->create(['ward_id' => $ward->id, 'bed_id' => $bed->id]);

    Livewire::test('pages::patients.show', ['patient' => $patient])
        ->call('discharge');

    expect($patient->refresh()->bed_id)->toBeNull();
});
