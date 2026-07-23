@props(['patient'])

@if ($patient->allergies || $patient->known_adrs)
    <flux:callout variant="danger" icon="exclamation-triangle" heading="Allergies & adverse drug reactions">
        <flux:callout.text>
            @if ($patient->allergies)
                <div><strong>Allergies:</strong> {{ $patient->allergies }}</div>
            @endif
            @if ($patient->known_adrs)
                <div><strong>Known ADRs:</strong> {{ $patient->known_adrs }}</div>
            @endif
        </flux:callout.text>
    </flux:callout>
@else
    <flux:callout variant="secondary" icon="check-circle" heading="No known allergies documented" />
@endif
