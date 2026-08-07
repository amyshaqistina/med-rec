<?php

use App\Enums\PatientStatus;
use App\Enums\ReconciliationStatus;
use App\Models\Ward;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ward Dashboard')] class extends Component {
    public function with(): array
    {
        $wards = Ward::withCount(['patients' => function ($query) {
                $query->where('status', PatientStatus::Active);
            }])
            ->withCount(['patients as unreconciled_patients_count' => function ($query) {
                $query->where('status', PatientStatus::Active)
                    ->whereDoesntHave('reconciliations', function ($query) {
                        $query->whereIn('status', [ReconciliationStatus::Completed, ReconciliationStatus::Closed]);
                    });
            }])
            ->orderBy('name')
            ->get();

        $totalPatients = $wards->sum('patients_count');
        $totalBeds = $wards->sum('bed_capacity');

        return [
            'wards' => $wards,
            'totalPatients' => $totalPatients,
            'activeWards' => $wards->where('patients_count', '>', 0)->count(),
            'occupancyRate' => $totalBeds > 0 ? (int) round(($totalPatients / $totalBeds) * 100) : 0,
            'availableBeds' => max($totalBeds - $totalPatients, 0),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Ward Management Dashboard</flux:heading>
        <flux:subheading>Overview of hospital wards, occupancy, and patient distribution.</flux:subheading>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <flux:card class="space-y-1 text-center">
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $totalPatients }}</div>
            <flux:text class="text-sm text-zinc-500">Total Patients</flux:text>
        </flux:card>
        <flux:card class="space-y-1 text-center">
            <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $activeWards }}</div>
            <flux:text class="text-sm text-zinc-500">Active Wards</flux:text>
        </flux:card>
        <flux:card class="space-y-1 text-center">
            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $occupancyRate }}%</div>
            <flux:text class="text-sm text-zinc-500">Occupancy Rate</flux:text>
        </flux:card>
        <flux:card class="space-y-1 text-center">
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $availableBeds }}</div>
            <flux:text class="text-sm text-zinc-500">Available Beds</flux:text>
        </flux:card>
    </div>

    <a
        href="{{ route('patients.index') }}"
        wire:navigate
        class="block rounded-xl bg-gradient-to-r from-accent to-teal-800 p-6 text-white shadow-sm transition hover:opacity-95 dark:to-teal-900"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-full bg-white/20">
                    <flux:icon.building-office-2 class="size-7" />
                </div>
                <div>
                    <div class="text-xl font-bold">All Wards</div>
                    <div class="text-white/80">Complete hospital overview</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold">{{ $totalPatients }}</div>
                <div class="text-white/80">Total Patients</div>
            </div>
        </div>
    </a>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($wards as $ward)
            @php $needsReconciliation = $ward->unreconciled_patients_count > 0; @endphp

            <a
                href="{{ route('wards.show', $ward) }}"
                wire:navigate
                wire:key="ward-{{ $ward->id }}"
                @class([
                    'relative flex items-center justify-between rounded-xl border bg-white p-5 shadow-sm transition hover:shadow-md dark:bg-zinc-900',
                    'border-red-300 hover:border-red-400 dark:border-red-500/40 dark:hover:border-red-500/70' => $needsReconciliation,
                    'border-zinc-200 hover:border-accent dark:border-zinc-700' => ! $needsReconciliation,
                ])
            >
                @if ($needsReconciliation)
                    <span class="absolute -top-1.5 -right-1.5 flex size-4">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex size-4 rounded-full bg-red-500"></span>
                    </span>
                @endif

                <div class="flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full {{ $ward->iconClasses() }}">
                        <flux:icon.user class="size-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">{{ $ward->name }}</flux:heading>
                            @if ($needsReconciliation)
                                <flux:badge size="sm" color="red">
                                    {{ $ward->unreconciled_patients_count }} pending
                                </flux:badge>
                            @endif
                        </div>
                        <flux:text class="text-sm text-zinc-500">{{ $ward->department }}</flux:text>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">{{ $ward->patients_count }}</div>
                    <div class="text-sm text-zinc-500">patients</div>
                </div>
            </a>
        @endforeach
    </div>
</section>
