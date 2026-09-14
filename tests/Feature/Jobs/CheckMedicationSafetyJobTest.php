<?php

use App\Enums\SafetyCheckStatus;
use App\Jobs\CheckMedicationSafetyJob;
use App\Jobs\SynthesizeMedicationSafetyContextJob;
use App\Models\MedicationCurrent;
use App\Models\Reconciliation;
use App\Services\OpenFdaService;
use App\Services\RxNormService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function fakeRxNormMatch(string $rxcui, string $name): void
{
    Http::fake([
        '*/approximateTerm.json*' => Http::response([
            'approximateGroup' => ['candidate' => [['rxcui' => $rxcui, 'score' => '100']]],
        ]),
        "*/rxcui/{$rxcui}/related.json*" => Http::response([
            'relatedGroup' => ['conceptGroup' => [
                ['tty' => 'IN', 'conceptProperties' => [['rxcui' => $rxcui, 'name' => $name]]],
            ]],
        ]),
    ]);
}

test('a successful label fetch updates the medication and dispatches synthesis', function () {
    fakeRxNormMatch('6809', 'metformin');
    Http::fake([
        '*/drug/label.json*' => Http::response(['results' => [[
            'boxed_warning' => ['Lactic acidosis risk.'],
            'contraindications' => ['Severe renal impairment.'],
            'warnings_and_precautions' => ['Monitor renal function.'],
            'drug_interactions' => [],
            'dosage_and_administration' => ['Take with food.'],
        ]]]),
    ]);
    Queue::fake([SynthesizeMedicationSafetyContextJob::class]);

    $medication = MedicationCurrent::factory()->create([
        'reconciliation_id' => Reconciliation::factory(),
        'medication_name' => 'Metformin 500mg BD',
    ]);

    (new CheckMedicationSafetyJob($medication))->handle(app(RxNormService::class), app(OpenFdaService::class));

    $medication->refresh();
    expect($medication->safety_check_status)->toBe(SafetyCheckStatus::Complete);
    expect($medication->safety_label_data['boxed_warning'])->toBe('Lactic acidosis risk.');
    expect($medication->safety_checked_at)->not->toBeNull();

    Queue::assertPushed(SynthesizeMedicationSafetyContextJob::class);
});

test('no rxnorm match falls back to the raw name and openfda is still queried with it', function () {
    Http::fake([
        '*/approximateTerm.json*' => Http::response(['approximateGroup' => ['candidate' => []]]),
        '*/drug/label.json*' => Http::response(['results' => [['boxed_warning' => ['A warning.']]]]),
    ]);
    Queue::fake([SynthesizeMedicationSafetyContextJob::class]);

    $medication = MedicationCurrent::factory()->create([
        'reconciliation_id' => Reconciliation::factory(),
        'medication_name' => 'Some Unmapped Drug Name',
    ]);

    (new CheckMedicationSafetyJob($medication))->handle(app(RxNormService::class), app(OpenFdaService::class));

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'drug/label.json')
        && str_contains($request['search'] ?? '', 'Some Unmapped Drug Name'));

    expect($medication->fresh()->safety_check_status)->toBe(SafetyCheckStatus::Complete);
});

test('openfda returning no results marks the check unavailable without throwing', function () {
    fakeRxNormMatch('6809', 'metformin');
    Http::fake([
        '*/drug/label.json*' => Http::response(['error' => ['code' => 'NOT_FOUND']], 404),
    ]);
    Queue::fake([SynthesizeMedicationSafetyContextJob::class]);

    $medication = MedicationCurrent::factory()->create([
        'reconciliation_id' => Reconciliation::factory(),
        'medication_name' => 'Metformin',
    ]);

    (new CheckMedicationSafetyJob($medication))->handle(app(RxNormService::class), app(OpenFdaService::class));

    expect($medication->fresh()->safety_check_status)->toBe(SafetyCheckStatus::Unavailable);
    Queue::assertNotPushed(SynthesizeMedicationSafetyContextJob::class);
});

test('an openfda timeout or error marks the check failed without bubbling an exception', function () {
    fakeRxNormMatch('6809', 'metformin');
    Http::fake([
        '*/drug/label.json*' => Http::response(null, 500),
    ]);
    Queue::fake([SynthesizeMedicationSafetyContextJob::class]);

    $medication = MedicationCurrent::factory()->create([
        'reconciliation_id' => Reconciliation::factory(),
        'medication_name' => 'Metformin',
    ]);

    (new CheckMedicationSafetyJob($medication))->handle(app(RxNormService::class), app(OpenFdaService::class));

    expect($medication->fresh()->safety_check_status)->toBe(SafetyCheckStatus::Failed);
    Queue::assertNotPushed(SynthesizeMedicationSafetyContextJob::class);
});
