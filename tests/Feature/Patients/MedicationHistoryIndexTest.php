<?php

use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('medication history list page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $this->get(route('patients.medication-history.index', $patient))->assertOk();
});

test('medication history list page shows all recorded entries', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $first = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);
    $second = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Metformin']);

    $this->get(route('patients.medication-history.index', $patient))
        ->assertOk()
        ->assertSee($first->medication_name)
        ->assertSee($second->medication_name);
});

test('medication history can be searched by medication name', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $match = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);
    $other = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Metformin']);

    Livewire::test('pages::patients.medication-history-index', ['patient' => $patient])
        ->set('search', 'Amlodipine')
        ->assertSee($match->medication_name)
        ->assertDontSee($other->medication_name);
});

test('medication history can be sorted by medication name', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Zolpidem']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);

    Livewire::test('pages::patients.medication-history-index', ['patient' => $patient])
        ->call('sort', 'medication_name')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem'])
        ->call('sort', 'medication_name')
        ->assertSeeInOrder(['Zolpidem', 'Amlodipine']);
});

test('medication history sort direction can be toggled via the sort by dropdown', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Zolpidem']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);

    Livewire::test('pages::patients.medication-history-index', ['patient' => $patient])
        ->set('sortBy', 'medication_name')
        ->assertSet('sortDirection', 'desc')
        ->call('toggleSortDirection')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem'])
        ->call('toggleSortDirection')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Zolpidem', 'Amlodipine']);
});

test('medication history can be sorted by dose amount and strength', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Zolpidem', 'dose_amount' => 200, 'strength' => '200mg']);
    MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine', 'dose_amount' => 100, 'strength' => '100mg']);

    Livewire::test('pages::patients.medication-history-index', ['patient' => $patient])
        ->call('sort', 'dose_amount')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem'])
        ->call('sort', 'strength')
        ->assertSeeInOrder(['Amlodipine', 'Zolpidem']);
});
