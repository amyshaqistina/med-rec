<?php

use App\Enums\RiskLevel;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('patient list page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('patients.index'))->assertOk();
});

test('patients can be searched by name or mrn', function () {
    $this->actingAs(User::factory()->create());

    $match = Patient::factory()->create(['first_name' => 'Ahmad', 'last_name' => 'Bin Ali']);
    $other = Patient::factory()->create(['first_name' => 'Siti', 'last_name' => 'Nur']);

    Livewire::test('pages::patients.index')
        ->set('search', 'Ahmad')
        ->assertSee($match->full_name)
        ->assertDontSee($other->full_name);

    Livewire::test('pages::patients.index')
        ->set('search', $other->mrn)
        ->assertSee($other->full_name)
        ->assertDontSee($match->full_name);
});

test('patients can be filtered by risk level', function () {
    $this->actingAs(User::factory()->create());

    $high = Patient::factory()->highRisk()->create();
    $low = Patient::factory()->lowRisk()->create();

    Livewire::test('pages::patients.index')
        ->set('risk', RiskLevel::High->value)
        ->assertSee($high->full_name)
        ->assertDontSee($low->full_name);
});
