<?php

use App\Models\Ward;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Ward Patients')] class extends Component {
    use WithPagination;

    public Ward $ward;

    #[Url]
    public string $search = '';

    public function mount(Ward $ward): void
    {
        $this->ward = $ward;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'patients' => $this->ward->patients()
                ->when($this->search, function ($query) {
                    $query->where(function ($query) {
                        $query->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                            ->orWhere('mrn', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('last_name')
                ->paginate(10),
            'patientCount' => $this->ward->patients()->count(),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full {{ $ward->iconClasses() }}">
                    <flux:icon.user class="size-5" />
                </div>
                <flux:heading size="xl">{{ $ward->name }}</flux:heading>
            </div>
            <flux:subheading>
                {{ $ward->department }} · {{ $patientCount }} / {{ $ward->bed_capacity }} beds occupied
            </flux:subheading>
        </div>

        <flux:button :href="route('dashboard')" wire:navigate variant="ghost">
            Back to Ward Dashboard
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or MRN…" icon="magnifying-glass" class="sm:max-w-xs" />

    <flux:table :paginate="$patients">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>MRN</flux:table.column>
            <flux:table.column>Date of birth</flux:table.column>
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
                    <flux:table.cell>{{ $patient->status->value }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <x-risk-badge :level="$patient->risk_level" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">
                        No patients currently assigned to this ward.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
