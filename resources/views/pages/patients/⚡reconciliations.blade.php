<?php

use App\Models\Patient;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reconciliation History')] class extends Component {
    public Patient $patient;

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);

        $this->patient = $patient;
    }

    public function with(): array
    {
        return [
            'reconciliations' => $this->patient->reconciliations()
                ->with('medicationCurrents')
                ->latest('started_at')
                ->get(),
        ];
    }
}; ?>

<section class="w-full max-w-6xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Reconciliation history — {{ $patient->full_name }}</flux:heading>
            <flux:subheading>Medication records grouped by reconciliation date.</flux:subheading>
        </div>
        <flux:button :href="route('patients.show', $patient)" wire:navigate variant="ghost">Back to patient</flux:button>
    </div>

    @forelse ($reconciliations as $reconciliation)
        <flux:card class="space-y-4" wire:key="reconciliation-{{ $reconciliation->id }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <flux:heading size="lg">{{ $reconciliation->started_at?->format('d/m/Y H:i') ?? 'Date unavailable' }}</flux:heading>
                    <flux:subheading>{{ $reconciliation->type->value }} reconciliation</flux:subheading>
                </div>
                <flux:button size="sm" variant="ghost" :href="route('reconciliations.show', $reconciliation)" wire:navigate>Open reconciliation</flux:button>
            </div>

            @if ($reconciliation->medicationCurrents->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Medication name</flux:table.column>
                        <flux:table.column>Dose</flux:table.column>
                        <flux:table.column>Frequency</flux:table.column>
                        <flux:table.column>Reconciliation type</flux:table.column>
                        <flux:table.column>Date</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($reconciliation->medicationCurrents as $medication)
                            <flux:table.row :key="$medication->id">
                                <flux:table.cell variant="strong">{{ $medication->medication_name }}</flux:table.cell>
                                <flux:table.cell>{{ $medication->dose ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $medication->frequency ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $reconciliation->type->value }}</flux:table.cell>
                                <flux:table.cell>{{ $reconciliation->started_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    @empty
        <flux:card><flux:text class="text-sm text-zinc-500">No reconciliations started yet.</flux:text></flux:card>
    @endforelse
</section>
