<?php

use App\Enums\Gender;
use App\Enums\HepaticFunction;
use App\Enums\PregnancyStatus;
use App\Enums\RenalFunction;
use App\Models\Patient;
use App\Models\Ward;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Patient')] class extends Component {
    public string $first_name = '';

    public string $last_name = '';

    public string $date_of_birth = '';

    public string $gender = '';

    public string $contact_primary = '';

    public string $contact_secondary = '';

    public string $email = '';

    public string $address_street = '';

    public string $address_city = '';

    public string $address_postcode = '';

    public string $address_state = '';

    public string $admission_date = '';

    public string $ward_id = '';

    public string $bed_no = '';

    public string $primary_diagnosis = '';

    public string $allergies = '';

    public string $known_adrs = '';

    public string $renal_function = 'Normal';

    public string $egfr = '';

    public string $hepatic_function = 'Normal';

    public string $pregnancy_status = 'Unknown';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', Patient::class);

        $this->admission_date = now()->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'contact_primary' => ['nullable', 'string', 'max:20'],
            'contact_secondary' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'address_postcode' => ['nullable', 'string', 'max:10'],
            'address_state' => ['nullable', 'string', 'max:50'],
            'admission_date' => ['required', 'date'],
            'ward_id' => ['nullable', 'integer', 'exists:wards,id'],
            'bed_no' => ['nullable', 'string', 'max:20'],
            'primary_diagnosis' => ['nullable', 'string', 'max:255'],
            'allergies' => ['nullable', 'string'],
            'known_adrs' => ['nullable', 'string'],
            'renal_function' => ['required', Rule::enum(RenalFunction::class)],
            'egfr' => ['nullable', 'numeric', 'between:0,200'],
            'hepatic_function' => ['required', Rule::enum(HepaticFunction::class)],
            'pregnancy_status' => ['required', Rule::enum(PregnancyStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $validated = collect($validated)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        $validated['created_by'] = auth()->id();

        $patient = Patient::create($validated);

        Flux::toast('Patient registered successfully.', variant: 'success');

        $this->redirect(route('patients.show', $patient), navigate: true);
    }

    public function with(): array
    {
        return [
            'wards' => Ward::orderBy('name')->get(),
        ];
    }
}; ?>

<section class="w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">New Patient</flux:heading>
        <flux:subheading>Register a patient for medication reconciliation.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:fieldset>
            <flux:legend>Demographics</flux:legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="first_name" label="First name" required />
                <flux:input wire:model="last_name" label="Last name" required />
                <flux:input wire:model="date_of_birth" type="date" label="Date of birth" required />
                <flux:select wire:model="gender" label="Gender" placeholder="Select gender…">
                    @foreach (\App\Enums\Gender::cases() as $option)
                        <option value="{{ $option->value }}">{{ $option->value }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>Contact &amp; address</flux:legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="contact_primary" label="Primary contact number" />
                <flux:input wire:model="contact_secondary" label="Secondary contact number" />
                <flux:input wire:model="email" type="email" label="Email" class="sm:col-span-2" />
                <flux:input wire:model="address_street" label="Street address" class="sm:col-span-2" />
                <flux:input wire:model="address_city" label="City" />
                <flux:input wire:model="address_postcode" label="Postcode" />
                <flux:input wire:model="address_state" label="State" />
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>Admission</flux:legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="admission_date" type="datetime-local" label="Admission date & time" required />
                <flux:select wire:model="ward_id" label="Ward" placeholder="Select ward…">
                    @foreach ($wards as $option)
                        <option value="{{ $option->id }}">{{ $option->name }} — {{ $option->department }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="bed_no" label="Bed no." placeholder="e.g. E-01" />
                <flux:input wire:model="primary_diagnosis" label="Primary diagnosis" class="sm:col-span-2" />
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>Clinical information</flux:legend>

            <div class="grid grid-cols-1 gap-4">
                <flux:textarea wire:model="allergies" label="Allergies" description="Separate multiple allergies with commas." rows="2" />
                <flux:textarea wire:model="known_adrs" label="Known adverse drug reactions" rows="2" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:select wire:model="renal_function" label="Renal function">
                        @foreach (\App\Enums\RenalFunction::cases() as $option)
                            <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="egfr" type="number" step="0.01" label="eGFR" description="mL/min/1.73m²" />
                    <flux:select wire:model="hepatic_function" label="Hepatic function">
                        @foreach (\App\Enums\HepaticFunction::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->value }}</option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($gender === \App\Enums\Gender::Female->value)
                    <flux:select wire:model="pregnancy_status" label="Pregnancy status" class="sm:max-w-xs">
                        @foreach (\App\Enums\PregnancyStatus::cases() as $option)
                            <option value="{{ $option->value }}">{{ str($option->value)->replace('_', ' ') }}</option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:textarea wire:model="notes" label="Special notes / precautions" rows="2" />
            </div>
        </flux:fieldset>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">Register Patient</flux:button>
            <flux:button :href="route('patients.index')" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</section>
