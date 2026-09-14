<?php

use App\Services\OpenFdaService;
use Illuminate\Support\Facades\Http;

function openFda(): OpenFdaService
{
    return app(OpenFdaService::class);
}

test('a successful label fetch extracts the expected fields', function () {
    Http::fake([
        '*/drug/label.json*' => Http::response([
            'results' => [[
                'boxed_warning' => ['May cause lactic acidosis.'],
                'contraindications' => ['Severe renal impairment.'],
                'warnings_and_precautions' => ['Monitor renal function.'],
                'drug_interactions' => ['Interacts with contrast dye.'],
                'dosage_and_administration' => ['Take with food.'],
            ]],
        ]),
    ]);

    $result = openFda()->getLabelData('metformin');

    expect($result['status'])->toBe('ok');
    expect($result['label'])->toBe([
        'boxed_warning' => 'May cause lactic acidosis.',
        'contraindications' => 'Severe renal impairment.',
        'warnings_and_precautions' => 'Monitor renal function.',
        'drug_interactions' => 'Interacts with contrast dye.',
        'dosage_and_administration' => 'Take with food.',
    ]);
});

test('warnings_and_precautions falls back to warnings when absent', function () {
    Http::fake([
        '*/drug/label.json*' => Http::response([
            'results' => [[
                'warnings' => ['Older-style warnings field.'],
            ]],
        ]),
    ]);

    $result = openFda()->getLabelData('aspirin');

    expect($result['label']['warnings_and_precautions'])->toBe('Older-style warnings field.');
});

test('a 404 no-match response becomes not_found, not an error', function () {
    Http::fake([
        '*/drug/label.json*' => Http::response(['error' => ['code' => 'NOT_FOUND', 'message' => 'No matches found!']], 404),
    ]);

    $result = openFda()->getLabelData('not a real drug');

    expect($result['status'])->toBe('not_found');
    expect($result['label'])->toBeNull();
});

test('a timeout or server error becomes a failed status without throwing', function () {
    Http::fake([
        '*/drug/label.json*' => Http::response(null, 500),
    ]);

    $result = openFda()->getLabelData('metformin');

    expect($result['status'])->toBe('error');
    expect($result['label'])->toBeNull();
});

test('results are cached for successful lookups and only one request is made', function () {
    Http::fake([
        '*/drug/label.json*' => Http::response(['results' => [['boxed_warning' => ['Warning text.']]]]),
    ]);

    openFda()->getLabelData('metformin');
    openFda()->getLabelData('metformin');

    Http::assertSentCount(1);
});
