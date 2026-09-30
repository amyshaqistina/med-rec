<?php

use App\Models\Patient;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Medication History')] class extends Component {
    use WithPagination;

    public Patient $patient;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);

        $this->patient = $patient;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'medicationHistories' => $this->patient->medicationHistories()
                ->when($this->search, fn ($query) => $query->where('medication_name', 'like', "%{$this->search}%"))
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(15),
        ];
    }
}; ?>

<section class="mx-auto w-full max-w-5xl space-y-6">
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

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by medication name…" icon="magnifying-glass" class="sm:max-w-xs" />

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="sortBy" class="sm:max-w-40">
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
                :icon="$sortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                wire:click="toggleSortDirection"
                :tooltip="$sortDirection === 'asc' ? 'Ascending' : 'Descending'"
                aria-label="Toggle sort direction"
            />
        </div>
    </div>

    <flux:table :paginate="$medicationHistories">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'medication_name'" :direction="$sortDirection" wire:click="sort('medication_name')">Medication</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'dose_amount'" :direction="$sortDirection" wire:click="sort('dose_amount')">Dose</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'frequency'" :direction="$sortDirection" wire:click="sort('frequency')">Frequency</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'is_patient_taking'" :direction="$sortDirection" wire:click="sort('is_patient_taking')">Taking?</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Date</flux:table.column>
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
