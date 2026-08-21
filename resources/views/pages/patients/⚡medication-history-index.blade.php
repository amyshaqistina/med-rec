<?php

use App\Models\Patient;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Medication History')] class extends Component {
    use WithPagination;

    public Patient $patient;

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);

        $this->patient = $patient;
    }

    public function with(): array
    {
        return [
            'medicationHistories' => $this->patient->medicationHistories()
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full max-w-5xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Medication History — {{ $patient->full_name }}</flux:heading>
            <flux:subheading>MRN {{ $patient->mrn }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \App\Models\MedicationHistory::class)
                <flux:button :href="route('patients.medication-history.create', $patient)" wire:navigate icon="plus">
                    Add medication history
                </flux:button>
            @endcan
            <flux:button :href="route('patients.show', $patient)" wire:navigate variant="ghost">
                Back to patient
            </flux:button>
        </div>
    </div>

    <flux:table :paginate="$medicationHistories">
        <flux:table.columns>
            <flux:table.column>Medication</flux:table.column>
            <flux:table.column>Dose</flux:table.column>
            <flux:table.column>Frequency</flux:table.column>
            <flux:table.column>Taking?</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($medicationHistories as $item)
                <flux:table.row :key="$item->id">
                    <flux:table.cell variant="strong">{{ $item->medication_name }}</flux:table.cell>
                    <flux:table.cell>{{ $item->dose_amount }} {{ $item->dose_unit }}</flux:table.cell>
                    <flux:table.cell>{{ $item->frequency ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $item->is_patient_taking->value }}</flux:table.cell>
                    <flux:table.cell>{{ $item->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @can('update', $item)
                            <flux:button :href="route('patients.medication-history.edit', [$patient, $item])" wire:navigate variant="filled" size="sm" icon="pencil-square" />
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        No medication history recorded.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
