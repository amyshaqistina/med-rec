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

    public function with(): array
    {
        $latestLabDate = $this->patient->labResults()->max('taken_at');

        return [
            'medicationHistories' => $this->patient->medicationHistories()->latest()->limit(5)->get(),
            'reconciliations' => $this->patient->reconciliations()->latest()->get(),
            'latestLabResults' => $latestLabDate
                ? $this->patient->labResults()->where('taken_at', $latestLabDate)->orderBy('test_name')->get()
                : collect(),
        ];
    }
}; ?>

<section class="w-full space-y-6">
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
                <flux:heading size="lg">Latest lab results</flux:heading>
                @if ($latestLabResults->isNotEmpty())
                    <flux:subheading>Drawn {{ $latestLabResults->first()->taken_at->format('d/m/Y H:i') }}</flux:subheading>
                @endif
            </div>
            @if ($latestLabResults->isNotEmpty())
                <flux:button size="sm" variant="ghost" :href="route('patients.lab-results', $patient)" wire:navigate>
                    See all
                </flux:button>
            @endif
        </div>

        @if ($latestLabResults->isEmpty())
            <flux:text class="text-sm text-zinc-500">No lab results recorded yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Test</flux:table.column>
                    <flux:table.column>Result</flux:table.column>
                    <flux:table.column>Reference range</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($latestLabResults as $result)
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
                    <flux:subheading>Latest entries, most recent first</flux:subheading>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($medicationHistories->isNotEmpty())
                    <flux:button size="sm" variant="ghost" :href="route('patients.medication-history.index', $patient)" wire:navigate>
                        See all
                    </flux:button>
                @endif
                @can('update', $patient)
                    <flux:button size="sm" icon="plus" square :href="route('patients.medication-history.create', $patient)" wire:navigate tooltip="Add medication history" aria-label="Add medication history" />
                @endcan
            </div>
        </div>

        @if ($medicationHistories->isEmpty())
            <flux:text class="text-sm text-zinc-500">No medication history recorded yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Medication</flux:table.column>
                    <flux:table.column>Dose</flux:table.column>
                    <flux:table.column>Frequency</flux:table.column>
                    <flux:table.column>Taking?</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
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
            <flux:heading size="lg">Reconciliations</flux:heading>
            @can('create', \App\Models\Reconciliation::class)
                <flux:button size="sm" wire:click="startReconciliation">
                    New reconciliation
                </flux:button>
            @endcan
        </div>

        @if ($reconciliations->isEmpty())
            <flux:text class="text-sm text-zinc-500">No reconciliations started yet.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Started</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($reconciliations as $reconciliation)
                        <flux:table.row :key="$reconciliation->id">
                            <flux:table.cell variant="strong">
                                @if (Route::has('reconciliations.show'))
                                    <a href="{{ route('reconciliations.show', $reconciliation) }}" wire:navigate class="hover:underline">
                                        {{ $reconciliation->type->value }}
                                    </a>
                                @else
                                    {{ $reconciliation->type->value }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$reconciliation->status->color()">{{ str($reconciliation->status->value)->replace('_', ' ') }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $reconciliation->started_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
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
