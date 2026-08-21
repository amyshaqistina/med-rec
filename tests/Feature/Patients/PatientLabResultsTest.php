<?php

use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('lab results page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $this->get(route('patients.lab-results', $patient))->assertOk();
});

test('lab results can be searched by test name', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $match = LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Hemoglobin']);
    $other = LabResult::factory()->create(['patient_id' => $patient->id, 'test_name' => 'Sodium']);

    Livewire::test('pages::patients.lab-results', ['patient' => $patient])
        ->set('search', 'Hemoglobin')
        ->assertSee($match->test_name)
        ->assertDontSee($other->test_name);
});

test('lab results can be filtered by date range', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    $inRange = LabResult::factory()->create([
        'patient_id' => $patient->id,
        'test_name' => 'Potassium',
        'taken_at' => now()->subDays(5),
    ]);

    $outOfRange = LabResult::factory()->create([
        'patient_id' => $patient->id,
        'test_name' => 'Creatinine',
        'taken_at' => now()->subDays(40),
    ]);

    Livewire::test('pages::patients.lab-results', ['patient' => $patient])
        ->set('dateFrom', now()->subDays(10)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee($inRange->test_name)
        ->assertDontSee($outOfRange->test_name);
});
