<?php

use App\Models\Patient;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Lab Results')] class extends Component {
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
            'labResults' => $this->patient->labResults()
                ->latest('taken_at')
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Lab Results — {{ $patient->full_name }}</flux:heading>
            <flux:subheading>MRN {{ $patient->mrn }}</flux:subheading>
        </div>
        <flux:button :href="route('patients.show', $patient)" wire:navigate variant="ghost">
            Back to patient
        </flux:button>
    </div>

    <flux:table :paginate="$labResults">
        <flux:table.columns>
            <flux:table.column>Test</flux:table.column>
            <flux:table.column>Result</flux:table.column>
            <flux:table.column>Reference range</flux:table.column>
            <flux:table.column>Taken</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($labResults as $result)
                <flux:table.row :key="$result->id">
                    <flux:table.cell variant="strong">{{ $result->test_name }}</flux:table.cell>
                    <flux:table.cell>{{ $result->result_value }} {{ $result->unit }}</flux:table.cell>
                    <flux:table.cell>{{ $result->reference_range ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $result->taken_at->format('d/m/Y H:i') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-500">
                        No lab results recorded.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
