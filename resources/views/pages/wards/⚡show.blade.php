<?php

use App\Enums\PatientStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\RiskLevel;
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

    #[Url]
    public string $risk = '';

    #[Url]
    public bool $includeDischarged = false;

    public bool $showFilters = false;

    public function mount(Ward $ward): void
    {
        $this->ward = $ward;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRisk(): void
    {
        $this->resetPage();
    }

    public function updatedIncludeDischarged(): void
    {
        $this->resetPage();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    /**
     * The label shown for a risk level on this page (Stable / Moderate / Critical).
     */
    public function riskLabel(RiskLevel $level): string
    {
        return match ($level) {
            RiskLevel::Low => 'Stable',
            RiskLevel::Medium => 'Moderate',
            RiskLevel::High => 'Critical',
        };
    }

    public function exportList(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $patients = $this->filteredPatients()
            ->with(['reconciliations' => fn ($query) => $query->latest()])
            ->orderBy('last_name')
            ->get();

        return response()->streamDownload(function () use ($patients) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'MRN', 'Gender', 'Age', 'Admission Date', 'Diagnosis', 'Status', 'Bed No.', 'Reconciliation']);

            foreach ($patients as $patient) {
                $latestReconciliation = $patient->reconciliations->first();
                $reconciliationDone = $latestReconciliation && in_array($latestReconciliation->status, [ReconciliationStatus::Completed, ReconciliationStatus::Closed], true);

                fputcsv($handle, [
                    $patient->full_name,
                    $patient->mrn,
                    $patient->gender?->value,
                    $patient->age,
                    $patient->admission_date->format('d/m/Y H:i'),
                    $patient->primary_diagnosis,
                    $this->riskLabel($patient->risk_level),
                    $patient->bed_no,
                    $reconciliationDone ? 'Done' : ($latestReconciliation ? 'Pending' : 'Not started'),
                ]);
            }

            fclose($handle);
        }, str($this->ward->name)->slug()->append('-patients.csv')->toString());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Patient, \App\Models\Ward>
     */
    protected function filteredPatients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->ward->patients()
            ->when(! $this->includeDischarged, fn ($query) => $query->where('status', PatientStatus::Active))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('mrn', 'like', "%{$this->search}%");
                });
            })
            ->when($this->risk, fn ($query) => $query->where('risk_level', $this->risk));
    }

    /**
     * The ward's current occupants — used for occupancy and risk KPI counts, which always
     * reflect who is actually in the ward right now regardless of the discharged toggle.
     */
    protected function activePatients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->ward->patients()->where('status', PatientStatus::Active);
    }

    public function with(): array
    {
        return [
            'patients' => $this->filteredPatients()
                ->with(['reconciliations' => fn ($query) => $query->latest()])
                ->orderBy('last_name')
                ->paginate(10),
            'patientCount' => $this->activePatients()->count(),
            'stableCount' => $this->activePatients()->where('risk_level', RiskLevel::Low)->count(),
            'moderateCount' => $this->activePatients()->where('risk_level', RiskLevel::Medium)->count(),
            'criticalCount' => $this->activePatients()->where('risk_level', RiskLevel::High)->count(),
            'riskLevels' => RiskLevel::cases(),
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

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <flux:card class="flex items-center gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                <flux:icon.users class="size-6" />
            </div>
            <div>
                <div class="text-2xl font-bold">{{ $patientCount }}</div>
                <flux:text class="text-sm text-zinc-500">Total Patients</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                <flux:icon.heart class="size-6" />
            </div>
            <div>
                <div class="text-2xl font-bold">{{ $stableCount }}</div>
                <flux:text class="text-sm text-zinc-500">Stable</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                <flux:icon.exclamation-triangle class="size-6" />
            </div>
            <div>
                <div class="text-2xl font-bold">{{ $moderateCount }}</div>
                <flux:text class="text-sm text-zinc-500">Moderate</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-400/10 dark:text-red-400">
                <flux:icon.fire class="size-6" />
            </div>
            <div>
                <div class="text-2xl font-bold">{{ $criticalCount }}</div>
                <flux:text class="text-sm text-zinc-500">Critical</flux:text>
            </div>
        </flux:card>
    </div>

    <div class="flex flex-wrap gap-3">
        <flux:button wire:click="exportList" variant="primary" icon="arrow-down-tray">Export List</flux:button>
        <flux:button wire:click="toggleFilters" variant="filled" icon="funnel">Filter Patients</flux:button>
    </div>

    @if ($showFilters)
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or MRN…" icon="magnifying-glass" class="sm:max-w-xs" />
            <flux:select wire:model.live="risk" placeholder="All statuses" class="sm:max-w-xs">
                @foreach ($riskLevels as $level)
                    <option value="{{ $level->value }}">{{ $this->riskLabel($level) }}</option>
                @endforeach
            </flux:select>
            <flux:checkbox wire:model.live="includeDischarged" label="Include discharged" />
        </div>
    @endif

    <div class="rounded-xl bg-gradient-to-r from-accent to-teal-800 p-6 text-white shadow-sm dark:to-teal-900">
        <div class="text-xl font-bold">Patient List</div>
        <div class="text-white/80">{{ $ward->name }} · {{ $patientCount }} patient{{ $patientCount === 1 ? '' : 's' }}</div>
    </div>

    <flux:table :paginate="$patients">
        <flux:table.columns>
            <flux:table.column>Patient Info</flux:table.column>
            <flux:table.column>Gender</flux:table.column>
            <flux:table.column>Age</flux:table.column>
            <flux:table.column>Admission Date</flux:table.column>
            <flux:table.column>Diagnosis</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Bed No.</flux:table.column>
            <flux:table.column>Reconciliation</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($patients as $patient)
                @php
                    $latestReconciliation = $patient->reconciliations->first();
                    $reconciliationDone = $latestReconciliation && in_array($latestReconciliation->status, [\App\Enums\ReconciliationStatus::Completed, \App\Enums\ReconciliationStatus::Closed], true);
                @endphp
                <flux:table.row :key="$patient->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('patients.show', $patient) }}" wire:navigate class="flex items-center gap-3 hover:underline">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon.user class="size-4" />
                            </span>
                            <span>
                                <span class="block">{{ $patient->full_name }}</span>
                                <span class="block text-xs font-normal text-zinc-500">MRN: {{ $patient->mrn }}</span>
                            </span>
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $patient->gender?->value ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $patient->age }}</flux:table.cell>
                    <flux:table.cell>
                        <span class="block">{{ $patient->admission_date->format('d M Y') }}</span>
                        <span class="block text-xs text-zinc-500">{{ $patient->admission_date->format('h:i A') }}</span>
                    </flux:table.cell>
                    <flux:table.cell>{{ $patient->primary_diagnosis ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$patient->risk_level->color()">{{ $this->riskLabel($patient->risk_level) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $patient->bed_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$reconciliationDone ? 'emerald' : ($latestReconciliation ? 'amber' : 'zinc')">
                            {{ $reconciliationDone ? 'Done' : ($latestReconciliation ? 'Pending' : 'Not started') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @can('update', $patient)
                            <flux:button :href="route('patients.edit', $patient)" wire:navigate variant="filled" size="sm" icon="pencil-square" />
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="text-center text-zinc-500">
                        No patients currently assigned to this ward.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
