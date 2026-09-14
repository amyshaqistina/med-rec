<?php

use App\Enums\DiscrepancySeverity;
use App\Enums\DiscrepancyStatus;
use App\Enums\AdherenceLevel;
use App\Enums\MedicationRoute;
use App\Enums\PatientStatus;
use App\Enums\PharmacistAssessment;
use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use App\Enums\SafetyCheckStatus;
use App\Enums\SourceType;
use App\Enums\TakingStatus;
use App\Jobs\CheckMedicationSafetyJob;
use App\Models\Discrepancy;
use App\Models\LabResult;
use App\Models\MedicationCurrent;
use App\Models\Reconciliation;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reconciliation Verification')] class extends Component {
    public Reconciliation $reconciliation;

    public string $type = '';

    public array $currentRows = [];

    /**
     * Which current-medication rows are showing the editable form instead of the
     * compact listing view, keyed by their index in $currentRows.
     */
    public array $editingRows = [];

    public array $assessments = [];

    public bool $showResolvedDiscrepancies = false;

    public function mount(Reconciliation $reconciliation): void
    {
        $this->authorize('view', $reconciliation);

        $this->reconciliation = $reconciliation;
        $this->type = $reconciliation->type->value;

        $this->loadCurrentRows();
        $this->loadAssessments();
        $this->dispatchSafetyChecksNeverAttempted();
    }

    /**
     * Recovers medications that are sitting at the default "pending" status with no
     * checked_at timestamp — i.e. a safety check was never actually dispatched for
     * them (pre-existing/seeded rows created outside saveCurrentMedications(), or a
     * row from before this feature existed). Without this, the safety box would show
     * "Checking…" forever with nothing ever resolving it.
     */
    private function dispatchSafetyChecksNeverAttempted(): void
    {
        $this->reconciliation->medicationCurrents()
            ->where('safety_check_status', SafetyCheckStatus::Pending)
            ->whereNull('safety_checked_at')
            ->each(fn (MedicationCurrent $medication) => CheckMedicationSafetyJob::dispatch($medication));
    }

    public function updatedType(string $value): void
    {
        $this->authorize('update', $this->reconciliation);

        $this->reconciliation->update(['type' => $value]);

        Flux::toast('Reconciliation type updated.', variant: 'success');
    }

    /**
     * Once a patient is discharged, their current-medication list becomes a locked
     * historical record — mirrors the discharge lock used on medication history.
     */
    public function getIsEditableProperty(): bool
    {
        return $this->reconciliation->patient->status === PatientStatus::Active;
    }

    public function loadCurrentRows(): void
    {
        $this->currentRows = $this->reconciliation->medicationCurrents()
            ->orderByDesc('id')
            ->get()
            ->map(fn (MedicationCurrent $item) => [
                'id' => $item->id,
                'medication_name' => $item->medication_name,
                'strength' => (string) $item->strength,
                'dose_amount' => $item->dose_amount !== null ? (string) $item->dose_amount : '',
                'dose_unit' => (string) $item->dose_unit,
                'route' => $item->route?->value ?? '',
                'frequency' => (string) $item->frequency,
                'timing' => (string) $item->timing,
                'indication' => (string) $item->indication,
                'is_patient_taking' => $item->is_patient_taking?->value ?? TakingStatus::Yes->value,
                'adherence_level' => $item->adherence_level?->value ?? AdherenceLevel::Full->value,
                'non_adherence_reason' => (string) $item->non_adherence_reason,
                'source_type' => $item->source_type?->value ?? SourceType::PatientReport->value,
                'ordered_by' => (string) $item->ordered_by,
                'safety_check_status' => $item->safety_check_status->value,
                'safety_flags' => $item->safety_flags,
                'safety_checked_at' => $item->safety_checked_at,
            ])
            ->all();
    }

    /**
     * Loads discrepancies with unresolved items first (worst severity first), then
     * resolved/closed items after — keeps historical items out of the pharmacist's way.
     */
    protected function loadAssessments(): void
    {
        $resolvedStatuses = [DiscrepancyStatus::Resolved, DiscrepancyStatus::Closed];
        $severityOrder = array_flip(array_column(DiscrepancySeverity::cases(), 'value'));

        $this->assessments = $this->reconciliation->discrepancies()
            ->get()
            ->sort(function (Discrepancy $a, Discrepancy $b) use ($resolvedStatuses, $severityOrder) {
                $aResolved = in_array($a->status, $resolvedStatuses, true) ? 1 : 0;
                $bResolved = in_array($b->status, $resolvedStatuses, true) ? 1 : 0;

                return $aResolved <=> $bResolved
                    ?: ($severityOrder[$a->severity->value] ?? 99) <=> ($severityOrder[$b->severity->value] ?? 99);
            })
            ->values()
            ->map(fn (Discrepancy $d) => [
                'id' => $d->id,
                'type' => $d->type->label(),
                'severity' => $d->severity->value,
                'description' => $d->description,
                'pharmacist_assessment' => $d->pharmacist_assessment?->value ?? '',
                'status' => $d->status->value,
                'clinical_note' => (string) $d->clinical_note,
            ])
            ->all();
    }

    public function addCurrentRow(): void
    {
        $this->authorize('update', $this->reconciliation);
        abort_unless($this->isEditable, 403, 'Cannot modify medications for a discharged patient.');

        $this->currentRows[] = [
            'id' => null,
            'medication_name' => '',
            'strength' => '',
            'dose_amount' => '',
            'dose_unit' => '',
            'route' => '',
            'frequency' => '',
            'timing' => '',
            'indication' => '',
            'is_patient_taking' => TakingStatus::Yes->value,
            'adherence_level' => AdherenceLevel::Full->value,
            'non_adherence_reason' => '',
            'source_type' => SourceType::PatientReport->value,
            'ordered_by' => '',
        ];

        $this->editingRows[array_key_last($this->currentRows)] = true;
    }

    public function removeCurrentRow(int $index): void
    {
        $this->authorize('update', $this->reconciliation);
        abort_unless($this->isEditable, 403, 'Cannot modify medications for a discharged patient.');

        $row = $this->currentRows[$index] ?? null;

        if ($row && $row['id']) {
            MedicationCurrent::find($row['id'])?->delete();
        }

        unset($this->currentRows[$index]);
        $this->currentRows = array_values($this->currentRows);
        $this->editingRows = [];
    }

    /**
     * Switches an already-saved medication row from the compact listing view into
     * the editable form. Unsaved rows are always shown as a form already.
     */
    public function editRow(int $index): void
    {
        $this->authorize('update', $this->reconciliation);
        abort_unless($this->isEditable, 403, 'Cannot modify medications for a discharged patient.');

        $this->editingRows[$index] = true;
    }

    /**
     * Collapses a saved medication row back to the compact listing view without
     * discarding any typed-but-unsaved changes — they remain until "Save medications".
     */
    public function collapseRow(int $index): void
    {
        $this->editingRows[$index] = false;
    }

    public function saveCurrentMedications(): void
    {
        $this->authorize('update', $this->reconciliation);
        abort_unless($this->isEditable, 403, 'Cannot modify medications for a discharged patient.');

        $validated = $this->validate([
            'currentRows.*.medication_name' => ['required', 'string', 'max:255'],
            'currentRows.*.strength' => ['nullable', 'string', 'max:100'],
            'currentRows.*.dose_amount' => ['nullable', 'numeric'],
            'currentRows.*.dose_unit' => ['nullable', 'string', 'max:50'],
            'currentRows.*.route' => ['nullable', Rule::enum(MedicationRoute::class)],
            'currentRows.*.frequency' => ['nullable', 'string', 'max:100'],
            'currentRows.*.timing' => ['nullable', 'string', 'max:255'],
            'currentRows.*.indication' => ['nullable', 'string', 'max:255'],
            'currentRows.*.is_patient_taking' => ['required', Rule::enum(TakingStatus::class)],
            'currentRows.*.adherence_level' => ['nullable', Rule::enum(AdherenceLevel::class)],
            'currentRows.*.non_adherence_reason' => ['nullable', 'string', 'max:255'],
            'currentRows.*.source_type' => ['nullable', Rule::enum(SourceType::class)],
            'currentRows.*.ordered_by' => ['nullable', 'string', 'max:255'],
        ])['currentRows'];

        foreach ($validated as $index => $data) {
            $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
            $data['dose'] = $data['dose_amount'] !== null
                ? trim($data['dose_amount'].' '.($data['dose_unit'] ?? ''))
                : null;
            $id = $this->currentRows[$index]['id'] ?? null;
            $medication = $id ? MedicationCurrent::find($id) : null;

            if ($medication) {
                $medication->update($data);
                $this->dispatchSafetyCheckIfNeeded($medication, isNew: false);
            } else {
                $medication = MedicationCurrent::create([
                    ...$data,
                    'reconciliation_id' => $this->reconciliation->id,
                ]);
                $this->dispatchSafetyCheckIfNeeded($medication, isNew: true);
            }
        }

        $this->loadCurrentRows();
        $this->editingRows = [];

        Flux::toast('Current medication list saved.', variant: 'success');
    }

    /**
     * Fires the safety check the instant a medication is committed — a new row,
     * or an existing row whose drug/dose/frequency actually changed. Stale flags
     * are cleared immediately so the box never shows an old "clear"/flagged result
     * while a fresh check is in flight.
     */
    private function dispatchSafetyCheckIfNeeded(MedicationCurrent $medication, bool $isNew): void
    {
        if (! $isNew && ! $medication->wasChanged(['medication_name', 'dose_amount', 'dose_unit', 'dose', 'frequency'])) {
            return;
        }

        if (! $isNew) {
            $medication->update([
                'safety_check_status' => SafetyCheckStatus::Pending,
                'safety_flags' => null,
            ]);
        }

        CheckMedicationSafetyJob::dispatch($medication);
    }

    public function recheckMedicationSafety(int $medicationId): void
    {
        $this->authorize('update', $this->reconciliation);

        $medication = $this->reconciliation->medicationCurrents()->whereKey($medicationId)->first();

        if (! $medication) {
            return;
        }

        $medication->update([
            'safety_check_status' => SafetyCheckStatus::Pending,
            'safety_flags' => null,
        ]);

        CheckMedicationSafetyJob::dispatch($medication);

        $this->loadCurrentRows();
    }

    public function saveAssessments(): void
    {
        $this->authorize('pharmacistAssess', $this->reconciliation);

        $validated = $this->validate([
            'assessments.*.pharmacist_assessment' => ['nullable', Rule::enum(PharmacistAssessment::class)],
            'assessments.*.status' => ['required', Rule::enum(DiscrepancyStatus::class)],
            'assessments.*.clinical_note' => ['nullable', 'string'],
        ])['assessments'];

        foreach ($validated as $index => $data) {
            $id = $this->assessments[$index]['id'];
            $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
            $resolved = $data['status'] === DiscrepancyStatus::Resolved->value;

            Discrepancy::whereKey($id)->update([
                'pharmacist_assessment' => $data['pharmacist_assessment'],
                'status' => $data['status'],
                'clinical_note' => $data['clinical_note'],
                'resolved_by' => $resolved ? auth()->id() : null,
                'resolved_at' => $resolved ? now() : null,
            ]);
        }

        $this->loadAssessments();

        Flux::toast('Assessments saved.', variant: 'success');
    }

    public function completeVerification(): void
    {
        $this->authorize('pharmacistAssess', $this->reconciliation);

        $this->reconciliation->update([
            'status' => ReconciliationStatus::Completed,
            'pharmacist_id' => auth()->id(),
            'completed_at' => now(),
        ]);

        Flux::toast('Reconciliation verification complete.', variant: 'success');

        $this->redirect(route('patients.show', $this->reconciliation->patient_id), navigate: true);
    }

    public function with(): array
    {
        $patient = $this->reconciliation->patient;

        $labResults = $patient->labResults()->latest('taken_at')->limit(8)->get();

        $resolvedValues = [DiscrepancyStatus::Resolved->value, DiscrepancyStatus::Closed->value];
        $unresolved = collect($this->assessments)->reject(fn ($a) => in_array($a['status'], $resolvedValues, true));
        $severityRank = array_flip(array_column(DiscrepancySeverity::cases(), 'value'));

        return [
            'patient' => $patient,
            'bpmhList' => $patient->medicationHistories()
                ->where('is_patient_taking', TakingStatus::Yes)
                ->get(),
            'labResults' => $labResults,
            'abnormalLabCount' => $labResults->filter(fn (LabResult $r) => in_array($r->flag(), ['abnormal', 'borderline'], true))->count(),
            'unresolvedCount' => $unresolved->count(),
            'worstUnresolvedSeverity' => $unresolved->isEmpty()
                ? null
                : $unresolved->sortBy(fn ($a) => $severityRank[$a['severity']] ?? 99)->first()['severity'],
        ];
    }
}; ?>

<section class="mx-auto w-full max-w-6xl space-y-6">
    {{-- Header: identity, status, and the reconciliation-type control merged in directly (no separate page) --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-3">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl">Reconciliation — {{ $patient->full_name }}</flux:heading>
                    <x-risk-badge :level="$patient->risk_level" />
                    <flux:badge size="sm" :color="$reconciliation->status->color()">{{ str($reconciliation->status->value)->replace('_', ' ') }}</flux:badge>
                </div>
                <flux:subheading>
                    MRN {{ $patient->mrn }} ·
                    BPMH {{ $reconciliation->bpmh_finalized ? 'compiled' : 'not yet compiled' }}
                </flux:subheading>
            </div>

            @can('update', $reconciliation)
                @if (in_array($reconciliation->status, [ReconciliationStatus::Draft, ReconciliationStatus::InProgress], true))
                    <flux:radio.group wire:model.live="type" label="Reconciliation type" variant="segmented" size="sm">
                        @foreach (ReconciliationType::cases() as $option)
                            <flux:radio value="{{ $option->value }}" label="{{ $option->value }}" />
                        @endforeach
                    </flux:radio.group>
                @else
                    <flux:badge size="sm" color="zinc">{{ $reconciliation->type->value }}</flux:badge>
                @endif
            @else
                <flux:badge size="sm" color="zinc">{{ $reconciliation->type->value }}</flux:badge>
            @endcan
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('patients.medication-history', ['patient' => $patient, 'reconciliation' => $reconciliation->id])" wire:navigate variant="ghost">
                Manage BPMH
            </flux:button>

            @can('pharmacistAssess', $reconciliation)
                @if ($reconciliation->status !== ReconciliationStatus::Completed && $reconciliation->status !== ReconciliationStatus::Closed)
                    <flux:button variant="primary" wire:click="completeVerification">
                        Complete Verification
                    </flux:button>
                @endif
            @endcan
        </div>
    </div>

    {{-- Quick-scan strip: the four things a pharmacist checks first, before reading anything else --}}
    @php
        $hasAllergies = (bool) ($patient->allergies || $patient->known_adrs);

        $warningColor = match ($worstUnresolvedSeverity) {
            'Critical', 'Major' => 'red',
            'Minor', 'Documentation' => 'amber',
            default => 'emerald',
        };
        $warningIconClasses = match ($warningColor) {
            'red' => 'bg-red-100 text-red-600 dark:bg-red-400/10 dark:text-red-400',
            'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400',
            default => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400',
        };

        $labsIconClasses = match (true) {
            $labResults->isEmpty() => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-400/10 dark:text-zinc-400',
            $abnormalLabCount > 0 => 'bg-red-100 text-red-600 dark:bg-red-400/10 dark:text-red-400',
            default => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400',
        };
    @endphp

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <flux:card class="flex items-center gap-3 p-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $hasAllergies ? 'bg-red-100 text-red-600 dark:bg-red-400/10 dark:text-red-400' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400' }}">
                <flux:icon.exclamation-triangle class="size-4" />
            </div>
            <div>
                <div class="text-sm font-bold leading-tight">{{ $hasAllergies ? 'Yes' : 'None' }}</div>
                <div class="text-xs text-zinc-500">Allergies</div>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-3 p-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $warningIconClasses }}">
                <flux:icon.flag class="size-4" />
            </div>
            <div>
                <div class="text-sm font-bold leading-tight">{{ $unresolvedCount }}</div>
                <div class="text-xs text-zinc-500">Unresolved warnings</div>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-3 p-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                <flux:icon.clipboard-document-list class="size-4" />
            </div>
            <div>
                <div class="text-sm font-bold leading-tight">{{ count($currentRows) }}</div>
                <div class="text-xs text-zinc-500">Current medications</div>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-3 p-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $labsIconClasses }}">
                <flux:icon.beaker class="size-4" />
            </div>
            <div>
                <div class="text-sm font-bold leading-tight">{{ $labResults->isEmpty() ? '—' : $abnormalLabCount }}</div>
                <div class="text-xs text-zinc-500">Abnormal labs</div>
            </div>
        </flux:card>
    </div>

    {{-- Critical alerts — only rendered when there's actually something to warn about --}}
    @if ($hasAllergies)
        <x-allergy-banner :patient="$patient" />
    @endif

    @if ($patient->renal_function->value !== 'Normal' || $patient->egfr)
        <flux:callout variant="warning" icon="beaker" heading="Renal function" :text="str($patient->renal_function->value)->replace('_', ' ').($patient->egfr ? ' · eGFR '.$patient->egfr : '')" />
    @endif

    {{-- Recent labs, closest to the top since they directly inform prescribing --}}
    <flux:card class="space-y-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Recent lab results</flux:heading>
            <flux:button size="sm" variant="ghost" :href="route('patients.lab-results', $patient)" wire:navigate>
                View all
            </flux:button>
        </div>

        @if ($labResults->isEmpty())
            <flux:text class="text-sm text-zinc-500">No lab results recorded yet.</flux:text>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($labResults as $result)
                    @php $flag = $result->flag(); @endphp
                    <div class="flex items-center justify-between gap-3 py-2 text-sm" wire:key="lab-{{ $result->id }}">
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ $result->test_name }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ $result->reference_range ?? 'No reference range' }} · {{ $result->taken_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="font-semibold">{{ $result->result_value }} {{ $result->unit }}</span>
                            @if ($flag === 'abnormal')
                                <flux:badge size="sm" color="red">Abnormal</flux:badge>
                            @elseif ($flag === 'borderline')
                                <flux:badge size="sm" color="amber">Borderline</flux:badge>
                            @elseif ($flag === 'normal')
                                <flux:badge size="sm" color="emerald">Normal</flux:badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- AI medication safety check: per-medication, openFDA/RxNorm-backed, runs automatically in the background. --}}
    <x-medication-safety-check :rows="$currentRows" />

    {{-- Working area: the active/editable current list first, the historical BPMH reference second --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <flux:card class="space-y-4 xl:col-span-2">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Current / intended medications</flux:heading>
                @if ($this->isEditable)
                    @can('update', $reconciliation)
                        <flux:button size="sm" icon="plus" wire:click="addCurrentRow">Add</flux:button>
                    @endcan
                @endif
            </div>

            @if (! $this->isEditable && count($currentRows) > 0)
                <flux:callout variant="secondary" icon="lock-closed" heading="Read-only" text="This patient has been discharged. The medication list below is a locked record and can no longer be modified." />
            @endif

            <div class="space-y-3">
                @forelse ($currentRows as $index => $row)
                    @php $isEditing = $this->isEditable && ($row['id'] === null || ($editingRows[$index] ?? false)); @endphp
                    <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="current-{{ $index }}">
                        @if ($isEditing)
                            <div class="flex items-center justify-between gap-3">
                                <flux:heading size="sm">Medication {{ $index + 1 }}</flux:heading>
                                @can('update', $reconciliation)
                                    <div class="flex items-center gap-2">
                                        @if ($row['id'] !== null)
                                            <flux:button size="sm" variant="ghost" wire:click="collapseRow({{ $index }})">Done</flux:button>
                                        @endif
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeCurrentRow({{ $index }})">Remove</flux:button>
                                    </div>
                                @endcan
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <flux:input size="sm" wire:model="currentRows.{{ $index }}.medication_name" label="Medication name" list="medication-name-options" autocomplete="off" required />
                            <flux:input size="sm" wire:model="currentRows.{{ $index }}.strength" label="Strength" placeholder="e.g. 500mg" />
                            <div class="grid grid-cols-2 gap-2">
                                <flux:input size="sm" wire:model="currentRows.{{ $index }}.dose_amount" type="number" step="0.01" label="Dose" />
                                <flux:input size="sm" wire:model="currentRows.{{ $index }}.dose_unit" label="Unit" placeholder="mg" />
                            </div>

                            <flux:select size="sm" wire:model="currentRows.{{ $index }}.route" label="Route" placeholder="Select…">
                                @foreach (MedicationRoute::cases() as $option)
                                    <option value="{{ $option->value }}">{{ $option->value }}</option>
                                @endforeach
                            </flux:select>
                            <flux:input size="sm" wire:model="currentRows.{{ $index }}.frequency" label="Frequency" list="medication-frequency-options" autocomplete="off" placeholder="e.g. Once Daily" />
                            <flux:input size="sm" wire:model="currentRows.{{ $index }}.timing" label="Timing" placeholder="e.g. Morning with breakfast" />

                            <flux:input size="sm" wire:model="currentRows.{{ $index }}.indication" label="Indication" />
                            <flux:select size="sm" wire:model="currentRows.{{ $index }}.source_type" label="Source">
                                @foreach (SourceType::cases() as $option)
                                    <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                                @endforeach
                            </flux:select>
                            <flux:select size="sm" wire:model="currentRows.{{ $index }}.is_patient_taking" label="Currently taking?">
                                @foreach (TakingStatus::cases() as $option)
                                    <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select size="sm" wire:model="currentRows.{{ $index }}.adherence_level" label="Adherence">
                                @foreach (AdherenceLevel::cases() as $option)
                                    <option value="{{ $option->value }}">{{ $option->value }}</option>
                                @endforeach
                            </flux:select>
                            <flux:input size="sm" wire:model="currentRows.{{ $index }}.ordered_by" label="Ordered by" />
                            @if (in_array($row['adherence_level'], [AdherenceLevel::Partial->value, AdherenceLevel::None->value], true))
                                <flux:input size="sm" wire:model="currentRows.{{ $index }}.non_adherence_reason" label="Reason for non-adherence" class="md:col-span-3" />
                            @endif
                            </div>
                        @else
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:heading size="sm">{{ $row['medication_name'] }}</flux:heading>
                                        @if (filled($row['strength']))
                                            <flux:text class="text-sm text-zinc-500">{{ $row['strength'] }}</flux:text>
                                        @endif
                                        <flux:badge size="sm" :color="$row['is_patient_taking'] === TakingStatus::Yes->value ? 'emerald' : 'zinc'">
                                            {{ str($row['is_patient_taking'])->replace('_', ' ') }}
                                        </flux:badge>
                                    </div>
                                    <div class="mt-1 text-sm text-zinc-500">
                                        {{ collect([trim($row['dose_amount'].' '.$row['dose_unit']), $row['route'], $row['frequency'], $row['timing']])->filter()->implode(' · ') ?: 'No dosing details recorded' }}
                                    </div>
                                    @if (filled($row['indication']))
                                        <div class="text-xs text-zinc-500">For: {{ $row['indication'] }}</div>
                                    @endif
                                </div>

                                @if ($this->isEditable)
                                    @can('update', $reconciliation)
                                        <div class="flex shrink-0 items-center gap-2">
                                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editRow({{ $index }})" />
                                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeCurrentRow({{ $index }})" />
                                        </div>
                                    @endcan
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">No current or intended medications recorded yet.</flux:text>
                @endforelse
            </div>

            @if ($this->isEditable)
                @can('update', $reconciliation)
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button size="sm" variant="primary" wire:click="saveCurrentMedications">
                            Save medications
                        </flux:button>
                    </div>
                @endcan
            @endif
        </flux:card>

        <flux:card class="space-y-3 bg-zinc-50 dark:bg-zinc-800/40">
            <flux:heading size="lg" class="text-zinc-600 dark:text-zinc-400">BPMH (reference)</flux:heading>

            @if ($bpmhList->isEmpty())
                <flux:text class="text-sm text-zinc-500">No active medications recorded in BPMH.</flux:text>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($bpmhList as $item)
                        <li class="rounded-lg border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="font-medium">{{ $item->medication_name }}</div>
                            <div class="text-zinc-500">
                                {{ $item->dose_amount }} {{ $item->dose_unit }} · {{ $item->route?->value }} · {{ $item->frequency }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:card>
    </div>

    <x-medication-name-datalist />
    <x-medication-frequency-datalist />
</section>
