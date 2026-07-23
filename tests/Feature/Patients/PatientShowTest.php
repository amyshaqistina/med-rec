<?php

use App\Models\Patient;
use App\Models\User;

test('patient show page displays demographics and risk badge', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->highRisk()->create();

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee($patient->full_name)
        ->assertSee($patient->mrn)
        ->assertSee('High risk', escape: false);
});

test('allergy banner shows documented allergies', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create(['allergies' => 'Penicillin, NSAIDs']);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Penicillin, NSAIDs');
});

test('allergy banner shows no known allergies when none documented', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create(['allergies' => null, 'known_adrs' => null]);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('No known allergies documented');
});
