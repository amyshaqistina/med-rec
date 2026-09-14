<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resolves a raw, as-entered medication string (brand name, dose, abbreviations)
 * to a clean generic ingredient name via the free RxNav API. Never throws —
 * callers should fall back to the raw string when this returns null.
 */
class RxNormService
{
    public function normalize(string $rawName): ?string
    {
        $cacheKey = 'rxnorm:normalize:'.md5(strtolower(trim($rawName)));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($rawName): ?string {
            try {
                return $this->resolve($rawName);
            } catch (Throwable $exception) {
                Log::warning('RxNorm normalization failed.', [
                    'raw_name' => $rawName,
                    'message' => $exception->getMessage(),
                ]);

                return null;
            }
        });
    }

    private function resolve(string $rawName): ?string
    {
        $approximate = $this->client()->get('approximateTerm.json', [
            'term' => $rawName,
            'maxEntries' => 1,
        ]);

        if ($approximate->failed()) {
            return null;
        }

        $candidate = $approximate->json('approximateGroup.candidate.0');

        if (! is_array($candidate) || blank($candidate['rxcui'] ?? null)) {
            return null;
        }

        $ingredientName = $this->resolveIngredientName((string) $candidate['rxcui']);

        if (filled($ingredientName)) {
            return $ingredientName;
        }

        return $this->propertyName((string) $candidate['rxcui']);
    }

    private function resolveIngredientName(string $rxcui): ?string
    {
        $related = $this->client()->get("rxcui/{$rxcui}/related.json", ['tty' => 'IN']);

        if ($related->failed()) {
            return null;
        }

        $name = $related->json('relatedGroup.conceptGroup.0.conceptProperties.0.name');

        return filled($name) ? (string) $name : null;
    }

    private function propertyName(string $rxcui): ?string
    {
        $response = $this->client()->get("rxcui/{$rxcui}/property.json", ['propName' => 'RxNorm Name']);

        if ($response->failed()) {
            return null;
        }

        $name = $response->json('propConceptGroup.propConcept.0.propValue');

        return filled($name) ? (string) $name : null;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.rxnorm.base_url'))
            ->acceptJson()
            ->timeout(5);
    }
}
