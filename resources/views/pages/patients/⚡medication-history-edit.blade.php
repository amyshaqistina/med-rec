<?php

use App\Enums\AdherenceLevel;
use App\Enums\MedicationRoute;
use App\Enums\PatientStatus;
use App\Enums\SourceType;
use App\Enums\TakingStatus;
use App\Models\MedicationHistory;
use App\Models\Patient;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Medication History')] class extends Component {
    public Patient $patient;

    public MedicationHistory $medicationHistory;

    public bool $showDeleteModal = false;

    public string $medication_name = '';

    public string $strength = '';

    public string $dose_amount = '';

    public string $dose_unit = '';

    public string $route = '';

    public string $frequency = '';

    public string $timing = '';

    public string $indication = '';

    public string $is_patient_taking = '';

    public string $adherence_level = '';

    public string $non_adherence_reason = '';

    public string $source_type = '';

    public function mount(Patient $patient, MedicationHistory $medicationHistory): void
    {
        $this->authorize('update', $medicationHistory);
        abort_unless($medicationHistory->patient_id === $patient->id, 404);

        $this->patient = $patient;
        $this->medicationHistory = $medicationHistory;

        $this->medication_name = $medicationHistory->medication_name;
        $this->strength = (string) $medicationHistory->strength;
        $this->dose_amount = $medicationHistory->dose_amount !== null ? (string) $medicationHistory->dose_amount : '';
        $this->dose_unit = (string) $medicationHistory->dose_unit;
        $this->route = $medicationHistory->route?->value ?? '';
        $this->frequency = (string) $medicationHistory->frequency;
        $this->timing = (string) $medicationHistory->timing;
        $this->indication = (string) $medicationHistory->indication;
        $this->is_patient_taking = $medicationHistory->is_patient_taking->value;
        $this->adherence_level = $medicationHistory->adherence_level?->value ?? '';
        $this->non_adherence_reason = (string) $medicationHistory->non_adherence_reason;
        $this->source_type = $medicationHistory->source_type?->value ?? '';
    }

    public function getIsEditableProperty(): bool
    {
        return $this->patient->status === PatientStatus::Active;
    }

    public function save(): void
    {
        $this->authorize('update', $this->medicationHistory);
        abort_unless($this->isEditable, 403, 'Cannot modify medication history for a discharged patient.');

        $validated = $this->validate([
            'medication_name' => ['required', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:100'],
            'dose_amount' => ['nullable', 'numeric'],
            'dose_unit' => ['nullable', 'string', 'max:50'],
            'route' => ['nullable', Rule::enum(MedicationRoute::class)],
            'frequency' => ['nullable', 'string', 'max:100'],
            'timing' => ['nullable', 'string', 'max:255'],
            'indication' => ['nullable', 'string', 'max:255'],
            'is_patient_taking' => ['required', Rule::enum(TakingStatus::class)],
            'adherence_level' => ['nullable', Rule::enum(AdherenceLevel::class)],
            'non_adherence_reason' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', Rule::enum(SourceType::class)],
        ]);

        $validated = array_map(fn ($value) => $value === '' ? null : $value, $validated);

        $this->medicationHistory->update($validated);

        Flux::toast('Medication history updated.', variant: 'success');

        $this->redirect(route('patients.show', $this->patient), navigate: true);
    }

    public function delete(): void
    {
        $this->authorize('update', $this->medicationHistory);
        abort_unless($this->isEditable, 403, 'Cannot modify medication history for a discharged patient.');

        $this->medicationHistory->delete();

        Flux::toast('Medication history entry deleted.', variant: 'success');

        $this->redirect(route('patients.show', $this->patient), navigate: true);
    }
}; ?>

<section class="w-full max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Edit Medication History — {{ $patient->full_name }}</flux:heading>
            <flux:subheading>MRN {{ $patient->mrn }}</flux:subheading>
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
        <flux:card class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:input wire:model="medication_name" label="Medication name" list="medication-name-options" autocomplete="off" :disabled="! $this->isEditable" required />
                <flux:input wire:model="strength" label="Strength" :disabled="! $this->isEditable" placeholder="e.g. 500mg" />
                <div class="grid grid-cols-2 gap-2">
                    <flux:input wire:model="dose_amount" type="number" step="0.01" label="Dose" :disabled="! $this->isEditable" />
                    <flux:input wire:model="dose_unit" label="Unit" :disabled="! $this->isEditable" placeholder="mg" />
                </div>

                <flux:select wire:model="route" label="Route" placeholder="Select…" :disabled="! $this->isEditable">
                    @foreach (MedicationRoute::cases() as $option)
                        <option value="{{ $option->value }}">{{ $option->value }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="frequency" label="Frequency" list="medication-frequency-options" autocomplete="off" :disabled="! $this->isEditable" placeholder="e.g. Once Daily" />
                <flux:input wire:model="timing" label="Timing" :disabled="! $this->isEditable" placeholder="e.g. Morning with breakfast" />

                <flux:input wire:model="indication" label="Indication" :disabled="! $this->isEditable" class="sm:col-span-2" />
                <flux:select wire:model="source_type" label="Source" :disabled="! $this->isEditable">
                    @foreach (SourceType::cases() as $option)
                        <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="is_patient_taking" label="Currently taking?" :disabled="! $this->isEditable">
                    @foreach (TakingStatus::cases() as $option)
                        <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="adherence_level" label="Adherence" :disabled="! $this->isEditable">
                    @foreach (AdherenceLevel::cases() as $option)
                        <option value="{{ $option->value }}">{{ $option->value }}</option>
                    @endforeach
                </flux:select>

                @if (in_array($adherence_level, ['Partial', 'None']))
                    <flux:input wire:model="non_adherence_reason" label="Reason for non-adherence" :disabled="! $this->isEditable" class="sm:col-span-3" />
                @endif
            </div>
        </flux:card>

        @if ($this->isEditable)
            <div class="flex items-center justify-between">
                <flux:modal.trigger name="delete-medication-history">
                    <flux:button type="button" variant="danger" icon="trash">Delete</flux:button>
                </flux:modal.trigger>

                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        @endif
    </form>

    <flux:modal name="delete-medication-history" class="max-w-md" wire:model="showDeleteModal">
        <div class="space-y-4">
            <flux:heading size="lg">Delete this medication history entry?</flux:heading>
            <flux:text>
                This will permanently remove {{ $medication_name }} from {{ $patient->full_name }}'s medication history.
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">Confirm delete</flux:button>
            </div>
        </div>
    </flux:modal>

    <x-medication-name-datalist />
    <x-medication-frequency-datalist />
</section>
