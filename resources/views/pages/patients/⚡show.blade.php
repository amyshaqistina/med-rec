<?php

use App\Enums\PatientStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use App\Models\Patient;
use App\Models\Reconciliation;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Patient Details')] class extends Component {
    public Patient $patient;

    public bool $showDischargeModal = false;

    public bool $showAllLabResults = false;

    public bool $showAllMedicationHistory = false;

    public string $labResultsSearch = '';

    public string $labResultsSortBy = 'taken_at';

    public string $labResultsSortDirection = 'desc';

    public string $medicationHistorySearch = '';

    public string $medicationHistorySortBy = 'created_at';

    public string $medicationHistorySortDirection = 'desc';

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);

        $this->patient = $patient;
    }

    public function discharge(): void
    {
        $this->authorize('update', $this->patient);

        $this->patient->update([
            'status' => PatientStatus::Discharged,
            'discharge_date' => now(),
            'updated_by' => auth()->id(),
        ]);

        $this->showDischargeModal = false;

        Flux::toast('Patient discharged.', variant: 'success');
    }

    public function startReconciliation(): void
    {
        $this->authorize('create', Reconciliation::class);

        $reconciliation = Reconciliation::create([
            'patient_id' => $this->patient->id,
            'type' => ReconciliationType::Admission,
            'status' => ReconciliationStatus::Draft,
            'started_at' => now(),
            'technician_id' => auth()->id(),
        ]);

        $this->redirect(route('reconciliations.show', $reconciliation), navigate: true);
    }

    public function sortLabResults(string $column): void
    {
        if ($this->labResultsSortBy === $column) {
            $this->labResultsSortDirection = $this->labResultsSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->labResultsSortBy = $column;
            $this->labResultsSortDirection = 'asc';
        }
    }

    public function toggleLabResultsSortDirection(): void
    {
        $this->labResultsSortDirection = $this->labResultsSortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function sortMedicationHistory(string $column): void
    {
        if ($this->medicationHistorySortBy === $column) {
            $this->medicationHistorySortDirection = $this->medicationHistorySortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->medicationHistorySortBy = $column;
            $this->medicationHistorySortDirection = 'asc';
        }
    }

    public function toggleMedicationHistorySortDirection(): void
    {
        $this->medicationHistorySortDirection = $this->medicationHistorySortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function with(): array
    {
        $latestLabDate = $this->patient->labResults()->max('taken_at');

        $labResults = ($this->showAllLabResults || $latestLabDate)
            ? $this->patient->labResults()
                ->when(! $this->showAllLabResults, fn ($query) => $query->where('taken_at', $latestLabDate))
                ->when($this->labResultsSearch, fn ($query) => $query->where('test_name', 'like', "%{$this->labResultsSearch}%"))
                ->orderBy($this->labResultsSortBy, $this->labResultsSortDirection)
                ->get()
            : collect();

        $medicationHistoriesQuery = $this->patient->medicationHistories()
            ->when($this->medicationHistorySearch, fn ($query) => $query->where('medication_name', 'like', "%{$this->medicationHistorySearch}%"))
            ->orderBy($this->medicationHistorySortBy, $this->medicationHistorySortDirection);

        return [
            'medicationHistories' => $this->showAllMedicationHistory
                ? $medicationHistoriesQuery->get()
                : $medicationHistoriesQuery->limit(5)->get(),
            'medicationHistoryTotal' => $this->patient->medicationHistories()->count(),
            'latestReconciliation' => $this->patient->reconciliations()
                ->with('medicationCurrents')
                ->latest('started_at')
                ->first(),
            'labResults' => $labResults,
            'latestLabDate' => $labResults->max('taken_at'),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    @if ($patient->ward)
        <flux:button :href="route('wards.show', $patient->ward)" wire:navigate variant="ghost" size="sm" icon="arrow-left">
            Back to {{ $patient->ward->name }}
        </flux:button>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl">{{ $patient->full_name }}</flux:heading>
                <x-risk-badge :level="$patient->risk_level" />
                <flux:badge size="sm" color="zinc">{{ $patient->status->value }}</flux:badge>
            </div>
            <flux:subheading>
                MRN {{ $patient->mrn }} · {{ $patient->age }} years old · {{ $patient->ward?->name ?? 'No ward assigned' }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $patient)
                <flux:button :href="route('patients.edit', $patient)" wire:navigate>Edit</flux:button>

                @if ($patient->status === PatientStatus::Active)
                    <flux:modal.trigger name="discharge-patient">
                        <flux:button variant="danger">Discharge</flux:button>
                    </flux:modal.trigger>
                @endif
            @endcan
        </div>
    </div>

    <x-allergy-banner :patient="$patient" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card class="space-y-3">
            <flux:heading size="lg">Demographics &amp; admission</flux:heading>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <dt class="text-zinc-500">Gender</dt>
                <dd>{{ $patient->gender?->value ?? '—' }}</dd>
                <dt class="text-zinc-500">Contact</dt>
                <dd>{{ $patient->contact_primary ?? '—' }}</dd>
                <dt class="text-zinc-500">Email</dt>
                <dd>{{ $patient->email ?? '—' }}</dd>
                <dt class="text-zinc-500">Admitted</dt>
                <dd>{{ $patient->admission_date->format('d/m/Y H:i') }}</dd>
                <dt class="text-zinc-500">Discharged</dt>
                <dd>{{ $patient->discharge_date?->format('d/m/Y H:i') ?? '—' }}</dd>
                <dt class="text-zinc-500">Primary diagnosis</dt>
                <dd>{{ $patient->primary_diagnosis ?? '—' }}</dd>
            </dl>
        </flux:card>

        <flux:card class="space-y-3">
            <flux:heading size="lg">Clinical information</flux:heading>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <dt class="text-zinc-500">Renal function</dt>
                <dd>{{ str($patient->renal_function->value)->replace('_', ' ') }}</dd>
                <dt class="text-zinc-500">eGFR</dt>
                <dd>{{ $patient->egfr ?? '—' }}</dd>
                <dt class="text-zinc-500">Hepatic function</dt>
                <dd>{{ $patient->hepatic_function->value }}</dd>
                <dt class="text-zinc-500">Pregnancy status</dt>
                <dd>{{ str($patient->pregnancy_status->value)->replace('_', ' ') }}</dd>
            </dl>
            @if ($patient->notes)
                <flux:text class="text-sm">{{ $patient->notes }}</flux:text>
            @endif
        </flux:card>
    </div>

    <flux:card class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ $showAllLabResults ? 'All lab results' : 'Latest lab results' }}</flux:heading>
                @if ($labResults->isNotEmpty())
                    <flux:subheading>
                        {{ $showAllLabResults ? 'All draws, most recent first' : 'Drawn '.$latestLabDate?->format('d/m/Y H:i') }}
                    </flux:subheading>
                @endif
            </div>
            @if ($labResults->isNotEmpty() || $showAllLabResults)
                <flux:button size="sm" variant="ghost" wire:click="$toggle('showAllLabResults')">
                    {{ $showAllLabResults ? 'Show latest only' : 'See all' }}
                </flux:button>
            @endif
        </div>

        @if ($labResults->isNotEmpty() || $labResultsSearch || $latestLabDate)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <flux:input wire:model.live.debounce.300ms="labResultsSearch" placeholder="Search by test name…" icon="magnifying-glass" size="sm" class="sm:max-w-xs" />

                <div class="flex items-center gap-2">
                    <flux:select wire:model.live="labResultsSortBy" size="sm" class="sm:max-w-40">
                        <option value="test_name">Sort: Test</option>
                        <option value="result_value">Sort: Result</option>
                        <option value="reference_range">Sort: Reference range</option>
                        <option value="taken_at">Sort: Date</option>
                    </flux:select>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        :icon="$labResultsSortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                        wire:click="toggleLabResultsSortDirection"
                        :tooltip="$labResultsSortDirection === 'asc' ? 'Ascending' : 'Descending'"
                        aria-label="Toggle sort direction"
                    />
                </div>
            </div>
        @endif

        @if ($labResults->isEmpty())
            <flux:text class="text-sm text-zinc-500">No lab results recorded yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$labResultsSortBy === 'test_name'" :direction="$labResultsSortDirection" wire:click="sortLabResults('test_name')">Test</flux:table.column>
                    <flux:table.column sortable :sorted="$labResultsSortBy === 'result_value'" :direction="$labResultsSortDirection" wire:click="sortLabResults('result_value')">Result</flux:table.column>
                    <flux:table.column sortable :sorted="$labResultsSortBy === 'reference_range'" :direction="$labResultsSortDirection" wire:click="sortLabResults('reference_range')">Reference range</flux:table.column>
                    <flux:table.column sortable :sorted="$labResultsSortBy === 'taken_at'" :direction="$labResultsSortDirection" wire:click="sortLabResults('taken_at')">Date</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($labResults as $result)
                        <flux:table.row :key="$result->id">
                            <flux:table.cell variant="strong">{{ $result->test_name }}</flux:table.cell>
                            <flux:table.cell>{{ $result->result_value }} {{ $result->unit }}</flux:table.cell>
                            <flux:table.cell>{{ $result->reference_range ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $result->taken_at->format('d/m/Y H:i') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:card class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">Medication history (BPMH)</flux:heading>
                @if ($medicationHistories->isNotEmpty())
                    <flux:subheading>
                        {{ $showAllMedicationHistory ? 'All entries, most recent first' : 'Latest entries, most recent first' }}
                    </flux:subheading>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($medicationHistoryTotal > 5 || $showAllMedicationHistory)
                    <flux:button size="sm" variant="ghost" wire:click="$toggle('showAllMedicationHistory')">
                        {{ $showAllMedicationHistory ? 'Show latest only' : 'See all' }}
                    </flux:button>
                @endif
                @can('update', $patient)
                    <flux:button size="sm" icon="plus" square :href="route('patients.medication-history.create', $patient)" wire:navigate tooltip="Add medication history" aria-label="Add medication history" />
                @endcan
            </div>
        </div>

        @if ($medicationHistories->isNotEmpty() || $medicationHistorySearch || $medicationHistoryTotal)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <flux:input wire:model.live.debounce.300ms="medicationHistorySearch" placeholder="Search by medication name…" icon="magnifying-glass" size="sm" class="sm:max-w-xs" />

                <div class="flex items-center gap-2">
                    <flux:select wire:model.live="medicationHistorySortBy" size="sm" class="sm:max-w-40">
                        <option value="medication_name">Sort: Medication</option>
                        <option value="strength">Sort: Strength</option>
                        <option value="dose_amount">Sort: Dose</option>
                        <option value="frequency">Sort: Frequency</option>
                        <option value="is_patient_taking">Sort: Taking?</option>
                        <option value="created_at">Sort: Date</option>
                    </flux:select>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        :icon="$medicationHistorySortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                        wire:click="toggleMedicationHistorySortDirection"
                        :tooltip="$medicationHistorySortDirection === 'asc' ? 'Ascending' : 'Descending'"
                        aria-label="Toggle sort direction"
                    />
                </div>
            </div>
        @endif

        @if ($medicationHistories->isEmpty())
            <flux:text class="text-sm text-zinc-500">No medication history recorded yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$medicationHistorySortBy === 'medication_name'" :direction="$medicationHistorySortDirection" wire:click="sortMedicationHistory('medication_name')">Medication</flux:table.column>
                    <flux:table.column sortable :sorted="$medicationHistorySortBy === 'dose_amount'" :direction="$medicationHistorySortDirection" wire:click="sortMedicationHistory('dose_amount')">Dose</flux:table.column>
                    <flux:table.column sortable :sorted="$medicationHistorySortBy === 'frequency'" :direction="$medicationHistorySortDirection" wire:click="sortMedicationHistory('frequency')">Frequency</flux:table.column>
                    <flux:table.column sortable :sorted="$medicationHistorySortBy === 'is_patient_taking'" :direction="$medicationHistorySortDirection" wire:click="sortMedicationHistory('is_patient_taking')">Taking?</flux:table.column>
                    <flux:table.column sortable :sorted="$medicationHistorySortBy === 'created_at'" :direction="$medicationHistorySortDirection" wire:click="sortMedicationHistory('created_at')">Date</flux:table.column>
                    <flux:table.column align="end">Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($medicationHistories as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell variant="strong">{{ $item->medication_name }}</flux:table.cell>
                            <flux:table.cell>{{ $item->dose_amount }} {{ $item->dose_unit }}</flux:table.cell>
                            <flux:table.cell>{{ $item->frequency }}</flux:table.cell>
                            <flux:table.cell>{{ $item->is_patient_taking->value }}</flux:table.cell>
                            <flux:table.cell>{{ $item->created_at->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                @can('update', $item)
                                    <flux:button :href="route('patients.medication-history.edit', [$patient, $item])" wire:navigate variant="filled" size="sm" icon="pencil-square" />
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:card class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">Reconciliations</flux:heading>
                @if ($latestReconciliation)
                    <flux:subheading>Latest reconciliation only</flux:subheading>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($latestReconciliation)
                    <flux:button size="sm" variant="ghost" :href="route('patients.reconciliations', $patient)" wire:navigate>See all</flux:button>
                @endif
                @can('create', \App\Models\Reconciliation::class)
                    <flux:button size="sm" wire:click="startReconciliation">New reconciliation</flux:button>
                @endcan
            </div>
        </div>

        @if (! $latestReconciliation)
            <flux:text class="text-sm text-zinc-500">No reconciliations started yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Medication name</flux:table.column>
                    <flux:table.column>Dose</flux:table.column>
                    <flux:table.column>Frequency</flux:table.column>
                    <flux:table.column>Reconciliation type</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($latestReconciliation->medicationCurrents as $medication)
                        <flux:table.row :key="$medication->id">
                            <flux:table.cell variant="strong">{{ $medication->medication_name }}</flux:table.cell>
                            <flux:table.cell>{{ $medication->dose ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $medication->frequency ?? '—' }}</flux:table.cell>
                            <flux:table.cell><a href="{{ route('reconciliations.show', $latestReconciliation) }}" wire:navigate class="hover:underline">{{ $latestReconciliation->type->value }}</a></flux:table.cell>
                            <flux:table.cell>{{ $latestReconciliation->started_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:modal name="discharge-patient" class="max-w-md" wire:model="showDischargeModal">
        <div class="space-y-4">
            <flux:heading size="lg">Discharge {{ $patient->full_name }}?</flux:heading>
            <flux:text>
                This will mark the patient as discharged and prevent further medication history additions.
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="discharge">Confirm discharge</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
