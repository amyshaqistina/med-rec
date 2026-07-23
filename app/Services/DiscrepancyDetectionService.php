<?php

namespace App\Services;

use App\Enums\DiscrepancySeverity;
use App\Enums\DiscrepancyType;
use App\Enums\TakingStatus;
use App\Models\Discrepancy;
use App\Models\MedicationCurrent;
use App\Models\MedicationHistory;
use App\Models\Reconciliation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Implements the SRS FR-RE-001.2 automatic discrepancy detection rules.
 *
 * Comparison is always the patient's standing active BPMH (is_patient_taking = Yes)
 * against the reconciliation's current-medications list — matched by normalized
 * medication name only (no fuzzy matching, no unit conversion). Therapeutic_Duplication
 * is never produced (requires a drug-class database this prototype doesn't have).
 *
 * sync() is idempotent: it upserts by a natural key of
 * (reconciliation_id, type, medication_history_id, medication_current_id), touching only
 * system-owned columns, so pharmacist-entered assessment/status/notes on a still-valid
 * discrepancy survive recomputation. Auto-detected rows that no longer apply are deleted.
 */
class DiscrepancyDetectionService
{
    /**
     * @return Collection<int, Discrepancy>
     */
    public function sync(Reconciliation $reconciliation): Collection
    {
        $bpmh = MedicationHistory::query()
            ->where('patient_id', $reconciliation->patient_id)
            ->where('is_patient_taking', TakingStatus::Yes)
            ->get()
            ->groupBy(fn (MedicationHistory $item) => $this->normalize($item->medication_name));

        $current = $reconciliation->medicationCurrents()
            ->get()
            ->groupBy(fn (MedicationCurrent $item) => $this->normalize($item->medication_name));

        $keptIds = [];

        $this->detectOmissions($reconciliation, $bpmh, $current, $keptIds);
        $this->detectCommissions($reconciliation, $bpmh, $current, $keptIds);
        $this->detectMatchedChanges($reconciliation, $bpmh, $current, $keptIds);
        $this->detectDuplications($reconciliation, $current, $keptIds);

        Discrepancy::query()
            ->where('reconciliation_id', $reconciliation->id)
            ->whereNotIn('id', $keptIds ?: [0])
            ->delete();

        return Discrepancy::query()
            ->where('reconciliation_id', $reconciliation->id)
            ->get()
            ->sortBy(fn (Discrepancy $d) => array_search($d->severity, DiscrepancySeverity::cases(), true))
            ->values();
    }

    /**
     * @param  Collection<string, Collection<int, MedicationHistory>>  $bpmh
     * @param  Collection<string, Collection<int, MedicationCurrent>>  $current
     * @param  array<int>  $keptIds
     */
    private function detectOmissions(Reconciliation $reconciliation, Collection $bpmh, Collection $current, array &$keptIds): void
    {
        foreach ($bpmh as $name => $group) {
            if ($current->has($name)) {
                continue;
            }

            foreach ($group as $historyItem) {
                $keptIds[] = $this->upsert(
                    reconciliation: $reconciliation,
                    type: DiscrepancyType::Omission,
                    severity: DiscrepancySeverity::Major,
                    medicationHistory: $historyItem,
                    medicationCurrent: null,
                    description: "{$historyItem->medication_name} was reported as a home medication but does not appear on the current medication list.",
                )->id;
            }
        }
    }

    /**
     * @param  Collection<string, Collection<int, MedicationHistory>>  $bpmh
     * @param  Collection<string, Collection<int, MedicationCurrent>>  $current
     * @param  array<int>  $keptIds
     */
    private function detectCommissions(Reconciliation $reconciliation, Collection $bpmh, Collection $current, array &$keptIds): void
    {
        foreach ($current as $name => $group) {
            if ($bpmh->has($name)) {
                continue;
            }

            foreach ($group as $currentItem) {
                $keptIds[] = $this->upsert(
                    reconciliation: $reconciliation,
                    type: DiscrepancyType::Commission,
                    severity: DiscrepancySeverity::Major,
                    medicationHistory: null,
                    medicationCurrent: $currentItem,
                    description: "{$currentItem->medication_name} appears on the current medication list but was not reported in the patient's medication history.",
                )->id;
            }
        }
    }

    /**
     * @param  Collection<string, Collection<int, MedicationHistory>>  $bpmh
     * @param  Collection<string, Collection<int, MedicationCurrent>>  $current
     * @param  array<int>  $keptIds
     */
    private function detectMatchedChanges(Reconciliation $reconciliation, Collection $bpmh, Collection $current, array &$keptIds): void
    {
        foreach ($bpmh as $name => $group) {
            if (! $current->has($name)) {
                continue;
            }

            $historyItem = $group->first();
            $currentItem = $current->get($name)->first();

            if ($severity = $this->doseChangeSeverity($historyItem, $currentItem)) {
                $keptIds[] = $this->upsert(
                    reconciliation: $reconciliation,
                    type: DiscrepancyType::DoseChange,
                    severity: $severity,
                    medicationHistory: $historyItem,
                    medicationCurrent: $currentItem,
                    description: "{$historyItem->medication_name} dose changed from {$historyItem->dose_amount} {$historyItem->dose_unit} to {$currentItem->dose}.",
                )->id;
            }

            if ($this->frequencyChanged($historyItem, $currentItem)) {
                $keptIds[] = $this->upsert(
                    reconciliation: $reconciliation,
                    type: DiscrepancyType::FrequencyChange,
                    severity: DiscrepancySeverity::Minor,
                    medicationHistory: $historyItem,
                    medicationCurrent: $currentItem,
                    description: "{$historyItem->medication_name} frequency changed from {$historyItem->frequency} to {$currentItem->frequency}.",
                )->id;
            }

            if ($this->routeChanged($historyItem, $currentItem)) {
                $keptIds[] = $this->upsert(
                    reconciliation: $reconciliation,
                    type: DiscrepancyType::RouteChange,
                    severity: DiscrepancySeverity::Minor,
                    medicationHistory: $historyItem,
                    medicationCurrent: $currentItem,
                    description: "{$historyItem->medication_name} route changed from {$historyItem->route?->value} to {$currentItem->route?->value}.",
                )->id;
            }
        }
    }

    /**
     * @param  Collection<string, Collection<int, MedicationCurrent>>  $current
     * @param  array<int>  $keptIds
     */
    private function detectDuplications(Reconciliation $reconciliation, Collection $current, array &$keptIds): void
    {
        foreach ($current as $name => $group) {
            if ($group->count() < 2) {
                continue;
            }

            foreach ($group->skip(1) as $duplicateItem) {
                $keptIds[] = $this->upsert(
                    reconciliation: $reconciliation,
                    type: DiscrepancyType::Duplication,
                    severity: DiscrepancySeverity::Major,
                    medicationHistory: null,
                    medicationCurrent: $duplicateItem,
                    description: "{$duplicateItem->medication_name} appears more than once on the current medication list.",
                )->id;
            }
        }
    }

    private function upsert(
        Reconciliation $reconciliation,
        DiscrepancyType $type,
        DiscrepancySeverity $severity,
        ?MedicationHistory $medicationHistory,
        ?MedicationCurrent $medicationCurrent,
        string $description,
    ): Discrepancy {
        return Discrepancy::query()->updateOrCreate(
            [
                'reconciliation_id' => $reconciliation->id,
                'type' => $type,
                'medication_history_id' => $medicationHistory?->id,
                'medication_current_id' => $medicationCurrent?->id,
            ],
            [
                'severity' => $severity,
                'description' => $description,
            ],
        );
    }

    private function doseChangeSeverity(MedicationHistory $historyItem, MedicationCurrent $currentItem): ?DiscrepancySeverity
    {
        $before = $historyItem->dose_amount !== null ? (float) $historyItem->dose_amount : null;
        $after = $this->extractNumeric($currentItem->dose);

        if ($before === null || $after === null || $before <= 0.0) {
            return null;
        }

        if (abs($after - $before) < 0.0001) {
            return null;
        }

        $percentChange = ($after - $before) / $before;

        if ($percentChange > 0.50 || $percentChange < -0.25) {
            return DiscrepancySeverity::Major;
        }

        return DiscrepancySeverity::Minor;
    }

    private function extractNumeric(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (! preg_match('/\d+(\.\d+)?/', $value, $matches)) {
            return null;
        }

        return (float) $matches[0];
    }

    private function frequencyChanged(MedicationHistory $historyItem, MedicationCurrent $currentItem): bool
    {
        $before = $this->normalize((string) $historyItem->frequency);
        $after = $this->normalize((string) $currentItem->frequency);

        return $before !== '' && $after !== '' && $before !== $after;
    }

    private function routeChanged(MedicationHistory $historyItem, MedicationCurrent $currentItem): bool
    {
        return $historyItem->route !== null
            && $currentItem->route !== null
            && $historyItem->route !== $currentItem->route;
    }

    private function normalize(string $name): string
    {
        return Str::of($name)->trim()->lower()->squish()->value();
    }
}
