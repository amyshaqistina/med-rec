<?php

use App\Models\Ward;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ward Dashboard')] class extends Component {
    public function with(): array
    {
        $wards = Ward::withCount('patients')->orderBy('name')->get();

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
            <a
                href="{{ route('wards.show', $ward) }}"
                wire:navigate
                wire:key="ward-{{ $ward->id }}"
                class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-accent hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
            >
                <div class="flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full {{ $ward->iconClasses() }}">
                        <flux:icon.user class="size-6" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ $ward->name }}</flux:heading>
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
