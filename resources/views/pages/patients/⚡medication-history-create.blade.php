<?php

use App\Enums\AdherenceLevel;
use App\Enums\MedicationRoute;
use App\Enums\SourceType;
use App\Enums\TakingStatus;
use App\Models\MedicationHistory;
use App\Models\Patient;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add Medication History')] class extends Component {
    public Patient $patient;

    public array $rows = [];

    public function mount(Patient $patient): void
    {
        $this->authorize('create', MedicationHistory::class);

        $this->patient = $patient;

        $this->addRow();
    }

    public function addRow(): void
    {
        $this->rows[] = [
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
        if (count($this->rows) <= 1) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function save(): void
    {
        $this->authorize('create', MedicationHistory::class);
        abort_unless($this->patient->status === \App\Enums\PatientStatus::Active, 403, 'Cannot modify medication history for a discharged patient.');

        $validated = $this->validate([
            'rows.*.medication_name' => ['required', 'string', 'max:255'],
            'rows.*.strength' => ['nullable', 'string', 'max:100'],
            'rows.*.dose_amount' => ['nullable', 'numeric'],
            'rows.*.dose_unit' => ['nullable', 'string', 'max:50'],
            'rows.*.route' => ['nullable', \Illuminate\Validation\Rule::enum(MedicationRoute::class)],
            'rows.*.frequency' => ['nullable', 'string', 'max:100'],
            'rows.*.timing' => ['nullable', 'string', 'max:255'],
            'rows.*.indication' => ['nullable', 'string', 'max:255'],
            'rows.*.is_patient_taking' => ['required', \Illuminate\Validation\Rule::enum(TakingStatus::class)],
            'rows.*.adherence_level' => ['nullable', \Illuminate\Validation\Rule::enum(AdherenceLevel::class)],
            'rows.*.non_adherence_reason' => ['nullable', 'string', 'max:255'],
            'rows.*.source_type' => ['nullable', \Illuminate\Validation\Rule::enum(SourceType::class)],
        ])['rows'];

        foreach ($validated as $data) {
            $data = array_map(fn ($value) => $value === '' ? null : $value, $data);

            MedicationHistory::create([
                ...$data,
                'patient_id' => $this->patient->id,
                'created_by' => auth()->id(),
            ]);
        }

        Flux::toast('Medication history added.', variant: 'success');

        $this->redirect(route('patients.show', $this->patient), navigate: true);
    }
}; ?>

<section class="w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Add Medication History — {{ $patient->full_name }}</flux:heading>
            <flux:subheading>MRN {{ $patient->mrn }}</flux:subheading>
        </div>
        <flux:button :href="route('patients.show', $patient)" wire:navigate variant="ghost">
            Back to patient
        </flux:button>
    </div>

    <x-allergy-banner :patient="$patient" />

    <form wire:submit="save" class="space-y-4">
        @foreach ($rows as $index => $row)
            <flux:card class="space-y-4" wire:key="row-{{ $index }}">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Medication {{ $index + 1 }}</flux:heading>
                    @if (count($rows) > 1)
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeRow({{ $index }})">
                            Remove
                        </flux:button>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input wire:model="rows.{{ $index }}.medication_name" label="Medication name" list="medication-name-options" autocomplete="off" required />
                    <flux:input wire:model="rows.{{ $index }}.strength" label="Strength" placeholder="e.g. 500mg" />
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model="rows.{{ $index }}.dose_amount" type="number" step="0.01" label="Dose" />
                        <flux:input wire:model="rows.{{ $index }}.dose_unit" label="Unit" placeholder="mg" />
                    </div>

                    <flux:select wire:model="rows.{{ $index }}.route" label="Route" placeholder="Select…">
                        @foreach (MedicationRoute::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->value }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="rows.{{ $index }}.frequency" label="Frequency" list="medication-frequency-options" autocomplete="off" placeholder="e.g. Once Daily" />
                    <flux:input wire:model="rows.{{ $index }}.timing" label="Timing" placeholder="e.g. Morning with breakfast" />

                    <flux:input wire:model="rows.{{ $index }}.indication" label="Indication" class="sm:col-span-2" />
                    <flux:select wire:model="rows.{{ $index }}.source_type" label="Source">
                        @foreach (SourceType::cases() as $option)
                            <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="rows.{{ $index }}.is_patient_taking" label="Currently taking?">
                        @foreach (TakingStatus::cases() as $option)
                            <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="rows.{{ $index }}.adherence_level" label="Adherence">
                        @foreach (AdherenceLevel::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->value }}</option>
                        @endforeach
                    </flux:select>

                    @if (in_array($row['adherence_level'], ['Partial', 'None']))
                        <flux:input wire:model="rows.{{ $index }}.non_adherence_reason" label="Reason for non-adherence" class="sm:col-span-3" />
                    @endif
                </div>
            </flux:card>
        @endforeach

        <div class="flex items-center gap-3">
            <flux:button type="button" wire:click="addRow" icon="plus">Add Medication</flux:button>
            <flux:button type="submit" variant="primary">Save Medication History</flux:button>
        </div>
    </form>

    <x-medication-name-datalist />
    <x-medication-frequency-datalist />
</section>
