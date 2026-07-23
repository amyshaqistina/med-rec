<?php

use App\Enums\ReconciliationStatus;
use App\Enums\ReconciliationType;
use App\Models\Patient;
use App\Models\Reconciliation;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Reconciliation')] class extends Component {
    public Patient $patient;

    public string $type = 'Admission';

    public function mount(Patient $patient): void
    {
        $this->authorize('create', Reconciliation::class);

        $this->patient = $patient;
    }

    public function save(): void
    {
        $this->authorize('create', Reconciliation::class);

        $reconciliation = Reconciliation::create([
            'patient_id' => $this->patient->id,
            'type' => $this->type,
            'status' => ReconciliationStatus::Draft,
            'started_at' => now(),
            'technician_id' => auth()->id(),
        ]);

        Flux::toast('Reconciliation started.', variant: 'success');

        $this->redirect(route('reconciliations.show', $reconciliation), navigate: true);
    }
}; ?>

<section class="w-full max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl">New Reconciliation — {{ $patient->full_name }}</flux:heading>
        <flux:subheading>MRN {{ $patient->mrn }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:radio.group wire:model="type" label="Reconciliation type" variant="segmented">
            @foreach (\App\Enums\ReconciliationType::cases() as $option)
                <flux:radio value="{{ $option->value }}" label="{{ $option->value }}" />
            @endforeach
        </flux:radio.group>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">Start Reconciliation</flux:button>
            <flux:button :href="route('patients.show', $patient)" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</section>
