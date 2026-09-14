<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches the FDA drug label for a normalized generic ingredient name and
 * extracts the fields relevant to a medication safety check. Never throws —
 * failures are logged and reported back via the returned status instead, so a
 * lookup outage can never break the medication-add flow.
 *
 * @phpstan-type LabelData array{
 *     boxed_warning: string|null,
 *     contraindications: string|null,
 *     warnings_and_precautions: string|null,
 *     drug_interactions: string|null,
 *     dosage_and_administration: string|null,
 * }
 */
class OpenFdaService
{
    /**
     * @return array{status: 'ok'|'not_found'|'error', label: LabelData|null}
     */
    public function getLabelData(string $genericName): array
    {
        $cacheKey = 'openfda:label:'.md5(strtolower(trim($genericName)));

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetch($genericName);

        if ($result['status'] !== 'error') {
            Cache::put($cacheKey, $result, now()->addDays(7));
        }

        return $result;
    }

    /**
     * @return array{status: 'ok'|'not_found'|'error', label: LabelData|null}
     */
    private function fetch(string $genericName): array
    {
        try {
            $response = $this->client()->get('drug/label.json', array_filter([
                'search' => 'openfda.generic_name:"'.$genericName.'"',
                'limit' => 1,
                'api_key' => config('services.openfda.key'),
            ]));
        } catch (Throwable $exception) {
            Log::warning('openFDA label lookup failed.', [
                'generic_name' => $genericName,
                'message' => $exception->getMessage(),
            ]);

            return ['status' => 'error', 'label' => null];
        }

        // openFDA responds 404 with a NOT_FOUND error body when nothing matches the
        // search — that is a legitimate "no results" outcome, not a failure.
        if ($response->status() === 404) {
            return ['status' => 'not_found', 'label' => null];
        }

        if ($response->failed()) {
            Log::warning('openFDA label lookup returned an error response.', [
                'generic_name' => $genericName,
                'status' => $response->status(),
            ]);

            return ['status' => 'error', 'label' => null];
        }

        $result = $response->json('results.0');

        if (! is_array($result)) {
            return ['status' => 'not_found', 'label' => null];
        }

        return ['status' => 'ok', 'label' => $this->extract($result)];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return LabelData
     */
    private function extract(array $result): array
    {
        $field = fn (string $key): ?string => filled($result[$key][0] ?? null) ? (string) $result[$key][0] : null;

        return [
            'boxed_warning' => $field('boxed_warning'),
            'contraindications' => $field('contraindications'),
            'warnings_and_precautions' => $field('warnings_and_precautions') ?? $field('warnings'),
            'drug_interactions' => $field('drug_interactions'),
            'dosage_and_administration' => $field('dosage_and_administration'),
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.openfda.base_url'))
            ->acceptJson()
            ->timeout(5);
    }
}
