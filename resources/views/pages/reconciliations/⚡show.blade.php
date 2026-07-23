<?php

use App\Enums\DiscrepancySeverity;
use App\Enums\DiscrepancyStatus;
use App\Enums\MedicationRoute;
use App\Enums\PharmacistAssessment;
use App\Enums\ReconciliationStatus;
use App\Enums\TakingStatus;
use App\Models\Discrepancy;
use App\Models\MedicationCurrent;
use App\Models\Reconciliation;
use App\Services\DiscrepancyDetectionService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reconciliation Verification')] class extends Component {
    public Reconciliation $reconciliation;

    public array $currentRows = [];

    public array $assessments = [];

    public function mount(Reconciliation $reconciliation): void
    {
        $this->authorize('view', $reconciliation);

        $this->reconciliation = $reconciliation;

        $this->loadCurrentRows();
        $this->loadAssessments();
    }

    protected function loadCurrentRows(): void
    {
        $this->currentRows = $this->reconciliation->medicationCurrents()
            ->orderByDesc('id')
            ->get()
            ->map(fn (MedicationCurrent $item) => [
                'id' => $item->id,
                'medication_name' => $item->medication_name,
                'dose' => (string) $item->dose,
                'route' => $item->route?->value ?? '',
                'frequency' => (string) $item->frequency,
                'indication' => (string) $item->indication,
                'ordered_by' => (string) $item->ordered_by,
            ])
            ->all();
    }

    protected function loadAssessments(): void
    {
        $this->assessments = $this->reconciliation->discrepancies()
            ->get()
            ->sortBy(fn (Discrepancy $d) => array_search($d->severity, DiscrepancySeverity::cases(), true))
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

        $this->currentRows[] = [
            'id' => null,
            'medication_name' => '',
            'dose' => '',
            'route' => '',
            'frequency' => '',
            'indication' => '',
            'ordered_by' => '',
        ];
    }

    public function removeCurrentRow(int $index): void
    {
        $this->authorize('update', $this->reconciliation);

        $row = $this->currentRows[$index] ?? null;

        if ($row && $row['id']) {
            MedicationCurrent::find($row['id'])?->delete();
        }

        unset($this->currentRows[$index]);
        $this->currentRows = array_values($this->currentRows);
    }

    public function saveCurrentMedications(): void
    {
        $this->authorize('update', $this->reconciliation);

        $validated = $this->validate([
            'currentRows.*.medication_name' => ['required', 'string', 'max:255'],
            'currentRows.*.dose' => ['nullable', 'string', 'max:100'],
            'currentRows.*.route' => ['nullable', Rule::enum(MedicationRoute::class)],
            'currentRows.*.frequency' => ['nullable', 'string', 'max:100'],
            'currentRows.*.indication' => ['nullable', 'string', 'max:255'],
            'currentRows.*.ordered_by' => ['nullable', 'string', 'max:255'],
        ])['currentRows'];

        foreach ($validated as $index => $data) {
            $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
            $id = $this->currentRows[$index]['id'] ?? null;

            if ($id) {
                MedicationCurrent::find($id)?->update($data);
            } else {
                MedicationCurrent::create([
                    ...$data,
                    'reconciliation_id' => $this->reconciliation->id,
                ]);
            }
        }

        $this->loadCurrentRows();

        Flux::toast('Current medication list saved.', variant: 'success');
    }

    public function runDiscrepancyCheck(DiscrepancyDetectionService $service): void
    {
        $this->authorize('update', $this->reconciliation);

        $service->sync($this->reconciliation);

        $this->loadAssessments();

        Flux::toast('Discrepancy check complete.', variant: 'success');
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
    }

    public function with(): array
    {
        return [
            'patient' => $this->reconciliation->patient,
            'bpmhList' => $this->reconciliation->patient->medicationHistories()
                ->where('is_patient_taking', TakingStatus::Yes)
                ->get(),
        ];
    }
}; ?>

<section class="w-full max-w-6xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $reconciliation->type->value }} Reconciliation — {{ $patient->full_name }}</flux:heading>
                <x-risk-badge :level="$patient->risk_level" />
                <flux:badge size="sm" :color="$reconciliation->status->color()">{{ str($reconciliation->status->value)->replace('_', ' ') }}</flux:badge>
            </div>
            <flux:subheading>
                MRN {{ $patient->mrn }} ·
                BPMH {{ $reconciliation->bpmh_finalized ? 'compiled' : 'not yet compiled' }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('patients.medication-history', ['patient' => $patient, 'reconciliation' => $reconciliation->id])" wire:navigate variant="ghost">
                Manage BPMH
            </flux:button>

            @can('pharmacistAssess', $reconciliation)
                @if ($reconciliation->status !== \App\Enums\ReconciliationStatus::Completed && $reconciliation->status !== \App\Enums\ReconciliationStatus::Closed)
                    <flux:button variant="primary" wire:click="completeVerification">
                        Complete Verification
                    </flux:button>
                @endif
            @endcan
        </div>
    </div>

    <x-allergy-banner :patient="$patient" />

    @if ($patient->renal_function->value !== 'Normal' || $patient->egfr)
        <flux:callout variant="warning" icon="beaker" heading="Renal function" :text="str($patient->renal_function->value)->replace('_', ' ').($patient->egfr ? ' · eGFR '.$patient->egfr : '')" />
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card class="space-y-3">
            <flux:heading size="lg">BPMH (from medication history)</flux:heading>

            @if ($bpmhList->isEmpty())
                <flux:text class="text-sm text-zinc-500">No active medications recorded in BPMH.</flux:text>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($bpmhList as $item)
                        <li class="rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                            <div class="font-medium">{{ $item->medication_name }}</div>
                            <div class="text-zinc-500">
                                {{ $item->dose_amount }} {{ $item->dose_unit }} · {{ $item->route?->value }} · {{ $item->frequency }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:card>

        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Current / intended medications</flux:heading>
                @can('update', $reconciliation)
                    <flux:button size="sm" icon="plus" wire:click="addCurrentRow">Add</flux:button>
                @endcan
            </div>

            <div class="space-y-3">
                @foreach ($currentRows as $index => $row)
                    <div class="grid grid-cols-1 gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-2" wire:key="current-{{ $index }}">
                        <flux:input size="sm" wire:model="currentRows.{{ $index }}.medication_name" placeholder="Medication name" />
                        <flux:input size="sm" wire:model="currentRows.{{ $index }}.dose" placeholder="Dose, e.g. 500mg" />
                        <flux:select size="sm" wire:model="currentRows.{{ $index }}.route" placeholder="Route">
                            @foreach (\App\Enums\MedicationRoute::cases() as $option)
                                <option value="{{ $option->value }}">{{ $option->value }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input size="sm" wire:model="currentRows.{{ $index }}.frequency" placeholder="Frequency" />
                        <flux:input size="sm" wire:model="currentRows.{{ $index }}.indication" placeholder="Indication" />
                        <flux:input size="sm" wire:model="currentRows.{{ $index }}.ordered_by" placeholder="Ordered by" />
                        @can('update', $reconciliation)
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeCurrentRow({{ $index }})" class="sm:col-span-2">
                                Remove
                            </flux:button>
                        @endcan
                    </div>
                @endforeach
            </div>

            @can('update', $reconciliation)
                <div class="flex items-center gap-3">
                    <flux:button size="sm" variant="primary" wire:click="saveCurrentMedications">Save Current Medications</flux:button>
                    <flux:button size="sm" wire:click="runDiscrepancyCheck">Run Discrepancy Check</flux:button>
                </div>
            @endcan
        </flux:card>
    </div>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Discrepancies</flux:heading>

        @if (empty($assessments))
            <flux:text class="text-sm text-zinc-500">No discrepancies identified. Run a discrepancy check after entering both medication lists.</flux:text>
        @else
            <div class="space-y-4">
                @foreach ($assessments as $index => $item)
                    <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="discrepancy-{{ $item['id'] }}">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" :color="\App\Enums\DiscrepancySeverity::from($item['severity'])->color()">{{ $item['severity'] }}</flux:badge>
                            <flux:text class="font-medium">{{ $item['type'] }}</flux:text>
                        </div>
                        <flux:text class="text-sm text-zinc-500">{{ $item['description'] }}</flux:text>

                        @can('pharmacistAssess', $reconciliation)
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <flux:select size="sm" wire:model="assessments.{{ $index }}.pharmacist_assessment" label="Assessment" placeholder="Select…">
                                    @foreach (\App\Enums\PharmacistAssessment::cases() as $option)
                                        <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select size="sm" wire:model="assessments.{{ $index }}.status" label="Status">
                                    @foreach (\App\Enums\DiscrepancyStatus::cases() as $option)
                                        <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:input size="sm" wire:model="assessments.{{ $index }}.clinical_note" label="Clinical note" class="sm:col-span-1" />
                            </div>
                        @else
                            <flux:text class="text-sm">
                                Status: {{ str($item['status'])->replace('_', ' ') }}
                                @if ($item['pharmacist_assessment'])
                                    · {{ str($item['pharmacist_assessment'])->replace('_', ' ') }}
                                @endif
                            </flux:text>
                        @endcan
                    </div>
                @endforeach
            </div>

            @can('pharmacistAssess', $reconciliation)
                <flux:button variant="primary" wire:click="saveAssessments">Save Assessments</flux:button>
            @endcan
        @endif
    </flux:card>
</section>
