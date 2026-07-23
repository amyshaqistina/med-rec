<?php

use App\Enums\RiskLevel;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

test('patient can be updated and risk is recalculated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $patient = Patient::factory()->lowRisk()->create();
    $ward = Ward::factory()->create(['name' => 'Cardiology Ward']);

    Livewire::test('pages::patients.edit', ['patient' => $patient])
        ->set('ward_id', (string) $ward->id)
        ->set('date_of_birth', now()->subYears(70)->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $patient->refresh();

    expect($patient->ward_id)->toBe($ward->id);
    expect($patient->updated_by)->toBe($user->id);
    expect($patient->risk_level)->toBe(RiskLevel::High);
});
