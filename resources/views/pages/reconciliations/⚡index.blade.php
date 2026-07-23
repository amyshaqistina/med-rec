<?php

use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Reconciliation Worklist')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Reconciliation::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'reconciliations' => Reconciliation::query()
                ->with('patient')
                ->when($this->status, fn ($query) => $query->where('reconciliations.status', $this->status))
                ->join('patients', 'patients.id', '=', 'reconciliations.patient_id')
                ->orderByRaw("CASE patients.risk_level WHEN 'High' THEN 0 WHEN 'Medium' THEN 1 ELSE 2 END")
                ->orderByDesc('reconciliations.created_at')
                ->select('reconciliations.*')
                ->paginate(10),
            'statuses' => ReconciliationStatus::cases(),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Reconciliation Worklist</flux:heading>
        <flux:subheading>Active reconciliations, sorted by patient risk.</flux:subheading>
    </div>

    <flux:select wire:model.live="status" placeholder="All statuses" class="sm:max-w-xs">
        @foreach ($statuses as $option)
            <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
        @endforeach
    </flux:select>

    <flux:table :paginate="$reconciliations">
        <flux:table.columns>
            <flux:table.column>Patient</flux:table.column>
            <flux:table.column>Risk</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Started</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($reconciliations as $reconciliation)
                <flux:table.row :key="$reconciliation->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('reconciliations.show', $reconciliation) }}" wire:navigate class="hover:underline">
                            {{ $reconciliation->patient->full_name }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>
                        <x-risk-badge :level="$reconciliation->patient->risk_level" />
                    </flux:table.cell>
                    <flux:table.cell>{{ $reconciliation->type->value }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$reconciliation->status->color()">{{ str($reconciliation->status->value)->replace('_', ' ') }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $reconciliation->started_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">
                        No reconciliations found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
