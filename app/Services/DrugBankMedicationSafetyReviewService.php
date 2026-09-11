<?php

namespace App\Services;

use App\Enums\TakingStatus;
use App\Models\MedicationSafetyReview;
use App\Models\Reconciliation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Fetches evidence-based DrugBank DDI findings. This service intentionally does
 * not prescribe, change doses, or infer lab-specific actions.
 */
class DrugBankMedicationSafetyReviewService
{
    public function review(Reconciliation $reconciliation): MedicationSafetyReview
    {
        if (blank(config('services.drugbank.key'))) {
            throw new RuntimeException('DrugBank is not configured. Add DRUGBANK_API_KEY to .env first.');
        }

        $snapshot = $this->snapshot($reconciliation);

        if ($snapshot['intended_medications'] === []) {
            throw new RuntimeException('Add at least one intended medication before running a safety review.');
        }

        $identifiers = collect($snapshot['medications'])
            ->mapWithKeys(fn (array $medication) => [$medication['name'] => $this->findIdentifiers($medication['name'])])
            ->all();

        $drugIds = collect($identifiers)->pluck('drugbank_ids')->flatten()->unique()->values()->all();
        $productConceptIds = collect($identifiers)->pluck('product_concept_ids')->flatten()->unique()->values()->all();
        $interactions = count($snapshot['medications']) >= 2
            ? $this->findInteractions($drugIds, $productConceptIds)
            : [];

        $unmatched = collect($identifiers)
            ->filter(fn (array $ids) => $ids['drugbank_ids'] === [] && $ids['product_concept_ids'] === [])
            ->keys()
            ->values()
            ->all();

        $results = [
            'source' => 'DrugBank Clinical API',
            'scope' => 'Drug-drug interaction screening only. Lab-specific dosing and prescribing decisions require pharmacist review.',
            'alerts' => collect($interactions)->map(fn (array $interaction) => [
                'severity' => $interaction['severity'] ?? 'unknown',
                'finding' => $interaction['description'] ?? 'DrugBank reported a medication interaction.',
                'management' => $interaction['management'] ?? null,
                'evidence_level' => $interaction['evidence_level'] ?? null,
                'medications' => array_values(array_filter([
                    Arr::get($interaction, 'ingredient.name'),
                    Arr::get($interaction, 'affected_ingredient.name'),
                ])),
            ])->values()->all(),
            'unmatched_medications' => $unmatched,
        ];

        return MedicationSafetyReview::create([
            'reconciliation_id' => $reconciliation->id,
            'requested_by' => auth()->id(),
            'status' => 'completed',
            'input_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
            'input_snapshot' => $snapshot,
            'results' => $results,
            'reviewed_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshot(Reconciliation $reconciliation): array
    {
        $patient = $reconciliation->patient;
        $intended = $reconciliation->medicationCurrents()->get()->map(fn ($medication) => [
            'name' => $medication->medication_name,
            'dose' => $medication->dose,
            'route' => $medication->route?->value,
            'frequency' => $medication->frequency,
        ])->all();
        $bpmh = $patient->medicationHistories()
            ->where('is_patient_taking', TakingStatus::Yes)
            ->get()
            ->map(fn ($medication) => [
                'name' => $medication->medication_name,
                'dose' => trim("{$medication->dose_amount} {$medication->dose_unit}"),
                'route' => $medication->route?->value,
                'frequency' => $medication->frequency,
            ])
            ->all();

        return [
            'intended_medications' => $intended,
            'bpmh_active_medications' => $bpmh,
            'medications' => collect([...$intended, ...$bpmh])->unique('name')->values()->all(),
            'clinical_information' => [
                'renal_function' => $patient->renal_function->value,
                'egfr' => $patient->egfr,
                'hepatic_function' => $patient->hepatic_function->value,
                'pregnancy_status' => $patient->pregnancy_status->value,
                'allergies' => $patient->allergies,
                'known_adrs' => $patient->known_adrs,
            ],
            'latest_labs' => $patient->labResults()->latest('taken_at')->limit(8)->get()->map(fn ($result) => [
                'name' => $result->test_name,
                'value' => $result->result_value,
                'unit' => $result->unit,
                'reference_range' => $result->reference_range,
                'taken_at' => $result->taken_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /** @return array{drugbank_ids: array<int, string>, product_concept_ids: array<int, string>} */
    private function findIdentifiers(string $name): array
    {
        $cacheKey = 'drugbank:identifiers:'.md5(strtolower(trim($name)));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($name): array {
            $response = $this->client()->get('product_concepts', [
                'q' => $name,
                'region' => config('services.drugbank.region'),
                'hit_details' => 'true',
            ]);

            if ($response->failed()) {
                throw new RuntimeException('DrugBank medication lookup failed. Please try again later.');
            }

            $values = $this->extractIdentifiers($response->json());

            return [
                'drugbank_ids' => $values->filter(fn (string $id) => preg_match('/^DB\d+$/', $id) === 1)->values()->all(),
                'product_concept_ids' => $values->filter(fn (string $id) => preg_match('/^DBPC\d+$/', $id) === 1)->values()->all(),
            ];
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function findInteractions(array $drugIds, array $productConceptIds): array
    {
        $response = $this->client()->post('ddi', array_filter([
            'drugbank_id' => $drugIds,
            'product_concept_id' => $productConceptIds,
        ]));

        if ($response->failed()) {
            throw new RuntimeException('DrugBank interaction check failed. Please try again later.');
        }

        $data = $response->json();

        return is_array($data) && array_is_list($data) ? $data : ($data['interactions'] ?? []);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl('https://api.drugbank.com/v1')
            ->acceptJson()
            ->withHeaders(['Authorization' => config('services.drugbank.key')])
            ->timeout(20)
            ->retry(2, 300);
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function extractIdentifiers(mixed $value): \Illuminate\Support\Collection
    {
        $identifiers = collect();

        $walk = function (mixed $item) use (&$walk, &$identifiers): void {
            if (is_array($item)) {
                foreach ($item as $value) {
                    $walk($value);
                }
            } elseif (is_string($item) && preg_match('/^DB(?:PC)?\d+$/', $item) === 1) {
                $identifiers->push($item);
            }
        };

        $walk($value);

        return $identifiers->unique()->values();
    }
}
