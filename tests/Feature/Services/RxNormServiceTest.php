<?php

use App\Services\RxNormService;
use Illuminate\Support\Facades\Http;

function rxNorm(): RxNormService
{
    return app(RxNormService::class);
}

test('a matched drug name resolves to its generic ingredient name', function () {
    Http::fake([
        '*/approximateTerm.json*' => Http::response([
            'approximateGroup' => [
                'candidate' => [
                    ['rxcui' => '6809', 'score' => '100'],
                ],
            ],
        ]),
        '*/rxcui/6809/related.json*' => Http::response([
            'relatedGroup' => [
                'conceptGroup' => [
                    ['tty' => 'IN', 'conceptProperties' => [['rxcui' => '6809', 'name' => 'metformin']]],
                ],
            ],
        ]),
    ]);

    expect(rxNorm()->normalize('Metformin 500mg BD'))->toBe('metformin');
});

test('no match falls back to null so the caller can use the raw string', function () {
    Http::fake([
        '*/approximateTerm.json*' => Http::response(['approximateGroup' => ['candidate' => []]]),
    ]);

    expect(rxNorm()->normalize('Not A Real Drug'))->toBeNull();
});

test('an upstream failure is handled gracefully and returns null', function () {
    Http::fake([
        '*/approximateTerm.json*' => Http::response(null, 500),
    ]);

    expect(rxNorm()->normalize('Metformin'))->toBeNull();
});
