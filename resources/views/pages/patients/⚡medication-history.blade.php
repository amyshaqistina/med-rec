<?php

use App\Enums\AdherenceLevel;
use App\Enums\MedicationRoute;
use App\Enums\PatientStatus;
use App\Enums\SourceType;
use App\Enums\TakingStatus;
use App\Models\MedicationHistory;
use App\Models\Patient;
use App\Models\Reconciliation;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Medication History')] class extends Component {
    public Patient $patient;

    #[Url]
    public ?int $reconciliation = null;

    public array $rows = [];

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);

        $this->patient = $patient;

        $this->loadRows();
    }

    public function getIsEditableProperty(): bool
    {
        return $this->patient->status === PatientStatus::Active;
    }

    protected function loadRows(): void
    {
        $this->rows = $this->patient->medicationHistories()
            ->orderByDesc('id')
            ->get()
            ->map(fn (MedicationHistory $item) => [
                'id' => $item->id,
                'medication_name' => $item->medication_name,
                'strength' => (string) $item->strength,
                'dose_amount' => $item->dose_amount !== null ? (string) $item->dose_amount : '',
                'dose_unit' => (string) $item->dose_unit,
                'route' => $item->route?->value ?? '',
                'frequency' => (string) $item->frequency,
                'timing' => (string) $item->timing,
                'indication' => (string) $item->indication,
                'is_patient_taking' => $item->is_patient_taking->value,
                'adherence_level' => $item->adherence_level?->value ?? '',
                'non_adherence_reason' => (string) $item->non_adherence_reason,
                'source_type' => $item->source_type?->value ?? '',
            ])
            ->all();
    }

    public function addRow(): void
    {
        $this->authorize('create', MedicationHistory::class);

        $this->rows[] = [
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
        ];
    }

    public function removeRow(int $index): void
    {
        $this->authorize('create', MedicationHistory::class);

        $row = $this->rows[$index] ?? null;

        if ($row && $row['id']) {
            MedicationHistory::find($row['id'])?->delete();
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function save(): void
    {
        $this->authorize('create', MedicationHistory::class);
        abort_unless($this->isEditable, 403, 'Cannot modify medication history for a discharged patient.');

        $validated = $this->validate([
            'rows.*.medication_name' => ['required', 'string', 'max:255'],
            'rows.*.strength' => ['nullable', 'string', 'max:100'],
            'rows.*.dose_amount' => ['nullable', 'numeric'],
            'rows.*.dose_unit' => ['nullable', 'string', 'max:50'],
            'rows.*.route' => ['nullable', Rule::enum(MedicationRoute::class)],
            'rows.*.frequency' => ['nullable', 'string', 'max:100'],
            'rows.*.timing' => ['nullable', 'string', 'max:255'],
            'rows.*.indication' => ['nullable', 'string', 'max:255'],
            'rows.*.is_patient_taking' => ['required', Rule::enum(TakingStatus::class)],
            'rows.*.adherence_level' => ['nullable', Rule::enum(AdherenceLevel::class)],
            'rows.*.non_adherence_reason' => ['nullable', 'string', 'max:255'],
            'rows.*.source_type' => ['nullable', Rule::enum(SourceType::class)],
        ])['rows'];

        foreach ($validated as $index => $data) {
            $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
            $id = $this->rows[$index]['id'] ?? null;

            if ($id) {
                MedicationHistory::find($id)?->update($data);
            } else {
                MedicationHistory::create([
                    ...$data,
                    'patient_id' => $this->patient->id,
                    'reconciliation_id' => $this->reconciliation,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        $this->loadRows();

        Flux::toast('Medication history saved.', variant: 'success');
    }

    public function markCompiled(): void
    {
        if (! $this->reconciliation) {
            return;
        }

        $reconciliationModel = Reconciliation::find($this->reconciliation);

        if ($reconciliationModel) {
            $this->authorize('update', $reconciliationModel);

            $reconciliationModel->update(['bpmh_finalized' => true]);

            Flux::toast('BPMH marked as compiled.', variant: 'success');
        }
    }
}; ?>

<section class="w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Medication History — {{ $patient->full_name }}</flux:heading>
            <flux:subheading>Best Possible Medication History (BPMH) interview for MRN {{ $patient->mrn }}.</flux:subheading>
        </div>
        <flux:button :href="route('patients.show', $patient)" wire:navigate variant="ghost">
            Back to patient
        </flux:button>
    </div>

    <x-allergy-banner :patient="$patient" />

    @unless ($this->isEditable)
        <flux:callout variant="secondary" icon="lock-closed" heading="Read-only" text="This patient has been discharged. Medication history can no longer be modified." />
    @endunless

    <form wire:submit="save" class="space-y-4">
        @foreach ($rows as $index => $row)
            <flux:card class="space-y-4" wire:key="row-{{ $index }}">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Medication {{ $index + 1 }}</flux:heading>
                    @if ($this->isEditable)
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeRow({{ $index }})">
                            Remove
                        </flux:button>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input wire:model="rows.{{ $index }}.medication_name" label="Medication name" :disabled="! $this->isEditable" required />
                    <flux:input wire:model="rows.{{ $index }}.strength" label="Strength" :disabled="! $this->isEditable" placeholder="e.g. 500mg" />
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model="rows.{{ $index }}.dose_amount" type="number" step="0.01" label="Dose" :disabled="! $this->isEditable" />
                        <flux:input wire:model="rows.{{ $index }}.dose_unit" label="Unit" :disabled="! $this->isEditable" placeholder="mg" />
                    </div>

                    <flux:select wire:model="rows.{{ $index }}.route" label="Route" placeholder="Select…" :disabled="! $this->isEditable">
                        @foreach (\App\Enums\MedicationRoute::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->value }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="rows.{{ $index }}.frequency" label="Frequency" :disabled="! $this->isEditable" placeholder="e.g. Once Daily" />
                    <flux:input wire:model="rows.{{ $index }}.timing" label="Timing" :disabled="! $this->isEditable" placeholder="e.g. Morning with breakfast" />

                    <flux:input wire:model="rows.{{ $index }}.indication" label="Indication" :disabled="! $this->isEditable" class="sm:col-span-2" />
                    <flux:select wire:model="rows.{{ $index }}.source_type" label="Source" :disabled="! $this->isEditable">
                        @foreach (\App\Enums\SourceType::cases() as $option)
                            <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="rows.{{ $index }}.is_patient_taking" label="Currently taking?" :disabled="! $this->isEditable">
                        @foreach (\App\Enums\TakingStatus::cases() as $option)
                            <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="rows.{{ $index }}.adherence_level" label="Adherence" :disabled="! $this->isEditable">
                        @foreach (\App\Enums\AdherenceLevel::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->value }}</option>
                        @endforeach
                    </flux:select>

                    @if (in_array($row['adherence_level'], ['Partial', 'None']))
                        <flux:input wire:model="rows.{{ $index }}.non_adherence_reason" label="Reason for non-adherence" :disabled="! $this->isEditable" class="sm:col-span-3" />
                    @endif
                </div>
            </flux:card>
        @endforeach

        @if ($this->isEditable)
            <div class="flex items-center gap-3">
                <flux:button type="button" wire:click="addRow" icon="plus">Add Medication</flux:button>
                <flux:button type="submit" variant="primary">Save Medication History</flux:button>

                @if ($reconciliation)
                    <flux:button type="button" variant="ghost" wire:click="markCompiled">
                        Mark BPMH Compiled
                    </flux:button>
                @endif
            </div>
        @endif
    </form>
</section>
