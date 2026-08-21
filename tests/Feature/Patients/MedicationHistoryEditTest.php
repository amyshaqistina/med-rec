<?php

use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('edit medication history page is displayed with existing values', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $medication = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);

    $this->get(route('patients.medication-history.edit', [$patient, $medication]))
        ->assertOk()
        ->assertSee('Amlodipine');
});

test('a medication history entry can be updated', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $medication = MedicationHistory::factory()->create(['patient_id' => $patient->id, 'medication_name' => 'Amlodipine']);

    Livewire::test('pages::patients.medication-history-edit', ['patient' => $patient, 'medicationHistory' => $medication])
        ->set('medication_name', 'Lisinopril')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('patients.show', $patient));

    expect($medication->fresh()->medication_name)->toBe('Lisinopril');
});

test('a medication history entry can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();
    $medication = MedicationHistory::factory()->create(['patient_id' => $patient->id]);

    Livewire::test('pages::patients.medication-history-edit', ['patient' => $patient, 'medicationHistory' => $medication])
        ->call('delete')
        ->assertRedirect(route('patients.show', $patient));

    expect(MedicationHistory::find($medication->id))->toBeNull();
});

test('medication history cannot be edited for a discharged patient', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->discharged()->create();
    $medication = MedicationHistory::factory()->create(['patient_id' => $patient->id]);

    Livewire::test('pages::patients.medication-history-edit', ['patient' => $patient, 'medicationHistory' => $medication])
        ->set('medication_name', 'Lisinopril')
        ->call('save')
        ->assertForbidden();
});
