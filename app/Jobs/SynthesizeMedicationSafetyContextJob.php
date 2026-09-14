<?php

namespace App\Jobs;

use App\Enums\HepaticFunction;
use App\Enums\RenalFunction;
use App\Models\LabResult;
use App\Models\MedicationCurrent;
use App\Models\Patient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cross-references a medication's openFDA label text against the patient's
 * allergies, diagnosis, latest flagged labs, and other current medications,
 * producing a short list of flags. Every flag is a direct keyword/threshold
 * match against retrieved label text and stored patient data — nothing here
 * is generated or inferred beyond what that data literally supports, so an
 * empty result means "no concerns found", not "not checked".
 */
class SynthesizeMedicationSafetyContextJob implements ShouldQueue
{
    use Queueable;

    private const RENAL_KEYWORDS = ['renal', 'kidney', 'egfr'];

    private const HEPATIC_KEYWORDS = ['hepatic', 'liver'];

    private const RENAL_LAB_KEYWORDS = ['egfr', 'creatinine', 'gfr'];

    private const HEPATIC_LAB_KEYWORDS = ['alt', 'ast', 'bilirubin', 'liver'];

    private const STOPWORDS = ['with', 'and', 'the', 'of', 'for', 'without', 'a', 'an', 'to', 'in', 'on'];

    public function __construct(public MedicationCurrent $medication)
    {
        //
    }

    public function handle(): void
    {
        try {
            $label = $this->medication->safety_label_data;

            if (! is_array($label)) {
                return;
            }

            $patient = $this->medication->reconciliation->patient;
            $otherMedications = $this->medication->reconciliation->medicationCurrents()
                ->whereKeyNot($this->medication->id)
                ->get();
            $labs = $patient->labResults()->latest('taken_at')->limit(8)->get();

            $flags = [
                ...$this->allergyFlags($patient, $label),
                ...$this->diagnosisFlags($patient, $label),
                ...$this->labFlags($patient, $labs, $label),
                ...$this->interactionFlags($otherMedications, $label),
                ...$this->labelWarningFlags($label),
            ];

            $this->medication->update(['safety_flags' => array_values($flags)]);
        } catch (Throwable $exception) {
            Log::error('Medication safety synthesis failed unexpectedly.', [
                'medication_id' => $this->medication->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $label
     * @return array<int, array{severity: string, category: string, explanation: string}>
     */
    private function allergyFlags(Patient $patient, array $label): array
    {
        $terms = $this->splitTerms(($patient->allergies ?? '').', '.($patient->known_adrs ?? ''));
        $haystack = $this->collapse([
            $this->medication->medication_name,
            $label['contraindications'] ?? null,
            $label['warnings_and_precautions'] ?? null,
        ]);

        $flags = [];

        foreach ($terms as $term) {
            if (str_contains(strtolower($haystack), strtolower($term))) {
                $flags[] = [
                    'severity' => 'high',
                    'category' => 'allergy',
                    'explanation' => "Patient has a documented allergy/ADR to \"{$term}\", which appears in this medication's name or label warnings.",
                ];
            }
        }

        return $flags;
    }

    /**
     * @param  array<string, mixed>  $label
     * @return array<int, array{severity: string, category: string, explanation: string}>
     */
    private function diagnosisFlags(Patient $patient, array $label): array
    {
        if (blank($patient->primary_diagnosis)) {
            return [];
        }

        $words = collect(preg_split('/[\s,;]+/', strtolower($patient->primary_diagnosis)) ?: [])
            ->filter(fn (string $word) => strlen($word) > 3 && ! in_array($word, self::STOPWORDS, true))
            ->unique();

        $contraindications = strtolower((string) ($label['contraindications'] ?? ''));
        $warnings = strtolower((string) ($label['warnings_and_precautions'] ?? ''));

        $flags = [];

        foreach ($words as $word) {
            if (str_contains($contraindications, $word)) {
                $flags[] = [
                    'severity' => 'high',
                    'category' => 'diagnosis',
                    'explanation' => "Patient's diagnosis (\"{$patient->primary_diagnosis}\") includes \"{$word}\", which appears in this medication's contraindications.",
                ];
            } elseif (str_contains($warnings, $word)) {
                $flags[] = [
                    'severity' => 'moderate',
                    'category' => 'diagnosis',
                    'explanation' => "Patient's diagnosis (\"{$patient->primary_diagnosis}\") includes \"{$word}\", which appears in this medication's warnings and precautions.",
                ];
            }
        }

        return $flags;
    }

    /**
     * @param  Collection<int, LabResult>  $labs
     * @param  array<string, mixed>  $label
     * @return array<int, array{severity: string, category: string, explanation: string}>
     */
    private function labFlags(Patient $patient, Collection $labs, array $label): array
    {
        $text = strtolower($this->collapse([
            $label['warnings_and_precautions'] ?? null,
            $label['contraindications'] ?? null,
            $label['dosage_and_administration'] ?? null,
        ]));

        $flags = [];

        $mentionsRenal = $this->containsAny($text, self::RENAL_KEYWORDS);
        $renalLab = $labs->first(fn ($lab) => $this->containsAny(strtolower($lab->test_name), self::RENAL_LAB_KEYWORDS)
            && in_array($lab->flag(), ['abnormal', 'borderline'], true));
        $renalImpaired = $patient->renal_function !== RenalFunction::Normal;

        if ($mentionsRenal && ($renalLab || $renalImpaired)) {
            $flags[] = [
                'severity' => ($renalLab?->flag() === 'abnormal' || $patient->renal_function === RenalFunction::SevereImpairment) ? 'high' : 'moderate',
                'category' => 'lab',
                'explanation' => $renalLab
                    ? "This medication's label discusses renal dosing, and the patient's {$renalLab->test_name} is flagged {$renalLab->flag()}."
                    : "This medication's label discusses renal dosing, and the patient's renal function is documented as ".str($patient->renal_function->value)->replace('_', ' ').'.',
            ];
        }

        $mentionsHepatic = $this->containsAny($text, self::HEPATIC_KEYWORDS);
        $hepaticLab = $labs->first(fn ($lab) => $this->containsAny(strtolower($lab->test_name), self::HEPATIC_LAB_KEYWORDS)
            && in_array($lab->flag(), ['abnormal', 'borderline'], true));
        $hepaticImpaired = $patient->hepatic_function !== HepaticFunction::Normal;

        if ($mentionsHepatic && ($hepaticLab || $hepaticImpaired)) {
            $flags[] = [
                'severity' => ($hepaticLab?->flag() === 'abnormal' || $patient->hepatic_function === HepaticFunction::Severe) ? 'high' : 'moderate',
                'category' => 'lab',
                'explanation' => $hepaticLab
                    ? "This medication's label discusses hepatic dosing, and the patient's {$hepaticLab->test_name} is flagged {$hepaticLab->flag()}."
                    : "This medication's label discusses hepatic dosing, and the patient's hepatic function is documented as ".str($patient->hepatic_function->value)->replace('_', ' ').'.',
            ];
        }

        return $flags;
    }

    /**
     * @param  Collection<int, MedicationCurrent>  $otherMedications
     * @param  array<string, mixed>  $label
     * @return array<int, array{severity: string, category: string, explanation: string}>
     */
    private function interactionFlags(Collection $otherMedications, array $label): array
    {
        $interactionText = strtolower((string) ($label['drug_interactions'] ?? ''));

        if (blank($interactionText)) {
            return [];
        }

        $flags = [];

        foreach ($otherMedications as $other) {
            $name = strtolower(trim($other->medication_name));

            if ($name !== '' && preg_match('/\b'.preg_quote($name, '/').'\b/', $interactionText) === 1) {
                $flags[] = [
                    'severity' => 'moderate',
                    'category' => 'interaction',
                    'explanation' => "This medication's label mentions an interaction with \"{$other->medication_name}\", which is also on the patient's current medication list.",
                ];
            }
        }

        return $flags;
    }

    /**
     * @param  array<string, mixed>  $label
     * @return array<int, array{severity: string, category: string, explanation: string}>
     */
    private function labelWarningFlags(array $label): array
    {
        if (blank($label['boxed_warning'] ?? null)) {
            return [];
        }

        return [[
            'severity' => 'high',
            'category' => 'label_warning',
            'explanation' => 'This medication carries an FDA boxed warning: '.str($label['boxed_warning'])->limit(200),
        ]];
    }

    /**
     * @return array<int, string>
     */
    private function splitTerms(string $text): array
    {
        // Strip parenthetical annotations first (e.g. "Penicillin (anaphylaxis)" ->
        // "Penicillin") so a reaction note doesn't prevent the drug/class name itself
        // from matching.
        $text = preg_replace('/\([^)]*\)/', '', $text) ?? $text;

        return collect(preg_split('/[,;]|\band\b/i', $text) ?: [])
            ->map(fn (string $term) => trim($term))
            ->filter(fn (string $term) => strlen($term) > 2)
            ->unique()
            ->values()
            ->all();
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string|null>  $parts
     */
    private function collapse(array $parts): string
    {
        return implode(' ', array_filter($parts, fn ($part) => filled($part)));
    }
}
