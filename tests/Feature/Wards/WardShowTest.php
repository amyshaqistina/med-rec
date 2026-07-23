<?php

use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

test('ward patient list shows only patients assigned to that ward', function () {
    $this->actingAs(User::factory()->create());

    $wardA = Ward::factory()->create(['name' => 'Ward 1']);
    $wardB = Ward::factory()->create(['name' => 'Ward 2']);

    $inWard = Patient::factory()->create(['ward_id' => $wardA->id, 'first_name' => 'Ahmad']);
    $elsewhere = Patient::factory()->create(['ward_id' => $wardB->id, 'first_name' => 'Siti']);

    $this->get(route('wards.show', $wardA))
        ->assertOk()
        ->assertSee('Ahmad')
        ->assertDontSee('Siti');
});

test('patients in a ward can be searched by name or mrn', function () {
    $this->actingAs(User::factory()->create());

    $ward = Ward::factory()->create();
    $match = Patient::factory()->create(['ward_id' => $ward->id, 'first_name' => 'Ahmad', 'last_name' => 'Bin Ali']);
    $other = Patient::factory()->create(['ward_id' => $ward->id, 'first_name' => 'Siti', 'last_name' => 'Nur']);

    Livewire::test('pages::wards.show', ['ward' => $ward])
        ->set('search', 'Ahmad')
        ->assertSee($match->full_name)
        ->assertDontSee($other->full_name);
});
