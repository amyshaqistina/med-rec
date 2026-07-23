<?php

use App\Enums\RiskLevel;
use App\Models\Patient;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Patients')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $risk = '';

    public string $sortBy = 'last_name';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->authorize('viewAny', Patient::class);
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRisk(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'patients' => Patient::query()
                ->with('ward')
                ->when($this->search, function ($query) {
                    $query->where(function ($query) {
                        $query->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                            ->orWhere('mrn', 'like', "%{$this->search}%");
                    });
                })
                ->when($this->risk, fn ($query) => $query->where('risk_level', $this->risk))
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(10),
            'riskLevels' => RiskLevel::cases(),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Patients</flux:heading>
            <flux:subheading>Search, review risk status, and manage patient records.</flux:subheading>
        </div>

        @can('create', Patient::class)
            <flux:button variant="primary" icon="plus" :href="route('patients.create')" wire:navigate>
                New Patient
            </flux:button>
        @endcan
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or MRN…" icon="magnifying-glass" class="sm:max-w-xs" />

        <flux:select wire:model.live="risk" placeholder="All risk levels" class="sm:max-w-xs">
            @foreach ($riskLevels as $level)
                <option value="{{ $level->value }}">{{ $level->value }} risk</option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$patients">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'last_name'" :direction="$sortDirection" wire:click="sort('last_name')">
                Name
            </flux:table.column>
            <flux:table.column>MRN</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'date_of_birth'" :direction="$sortDirection" wire:click="sort('date_of_birth')">
                Date of birth
            </flux:table.column>
            <flux:table.column>Ward</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column align="end">Risk</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($patients as $patient)
                <flux:table.row :key="$patient->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('patients.show', $patient) }}" wire:navigate class="hover:underline">
                            {{ $patient->full_name }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $patient->mrn }}</flux:table.cell>
                    <flux:table.cell>{{ $patient->date_of_birth->format('d/m/Y') }} ({{ $patient->age }})</flux:table.cell>
                    <flux:table.cell>{{ $patient->ward?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $patient->status->value }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <x-risk-badge :level="$patient->risk_level" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        No patients found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
