@props(['rows'])

@php
    // Unsaved rows (no id yet) haven't been committed, so they have no check to show.
    $checkedRows = collect($rows)->filter(fn ($row) => $row['id'] !== null)->values();
    $inFlight = $checkedRows->contains(fn ($row) => $row['safety_check_status'] === 'pending'
        || ($row['safety_check_status'] === 'complete' && $row['safety_flags'] === null));
@endphp

@if ($checkedRows->isNotEmpty())
    <div @if ($inFlight) wire:poll.3s="loadCurrentRows" @endif>
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">AI medication safety check</flux:heading>
            <flux:subheading>openFDA label screening against this patient's allergies, diagnosis, labs, and other medications. Decision support only — always pharmacist-verified.</flux:subheading>
        </div>

        <div class="space-y-2">
            @foreach ($checkedRows as $row)
                @php
                    $status = $row['safety_check_status'];
                    $flags = $row['safety_flags'];
                    $stillChecking = $status === 'pending' || ($status === 'complete' && $flags === null);
                    $highFlags = $stillChecking ? [] : collect($flags ?? [])->where('severity', 'high')->values();
                    $otherFlags = $stillChecking ? [] : collect($flags ?? [])->where('severity', '!=', 'high')->values();
                @endphp

                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="safety-{{ $row['id'] }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm">{{ $row['medication_name'] }}</flux:heading>

                            @if ($stillChecking)
                                <flux:badge size="sm" color="zinc" class="animate-pulse">Checking…</flux:badge>
                            @elseif ($status === 'unavailable')
                                <flux:badge size="sm" color="zinc">Unavailable</flux:badge>
                            @elseif ($status === 'failed')
                                <flux:badge size="sm" color="amber">Check failed</flux:badge>
                            @elseif ($highFlags->isNotEmpty())
                                <flux:badge size="sm" color="red">{{ $highFlags->count() }} high-severity</flux:badge>
                            @elseif ($otherFlags->isNotEmpty())
                                <flux:badge size="sm" color="amber">{{ $otherFlags->count() }} flagged</flux:badge>
                            @else
                                <flux:icon.check-circle class="size-4 text-zinc-400 dark:text-zinc-500" />
                                <flux:text class="text-sm text-zinc-500">No concerns identified from available data</flux:text>
                            @endif
                        </div>

                        @if (in_array($status, ['unavailable', 'failed'], true))
                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="recheckMedicationSafety({{ $row['id'] }})">
                                Re-check
                            </flux:button>
                        @else
                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="recheckMedicationSafety({{ $row['id'] }})" title="Re-check this medication" />
                        @endif
                    </div>

                    @if ($stillChecking)
                        <flux:text class="mt-1 text-sm text-zinc-500">Checking {{ $row['medication_name'] }} against patient data…</flux:text>
                    @elseif ($status === 'unavailable')
                        <flux:text class="mt-1 text-sm text-zinc-500">Safety data unavailable for {{ $row['medication_name'] }} — verify manually.</flux:text>
                    @elseif ($status === 'failed')
                        <flux:text class="mt-1 text-sm text-zinc-500">The safety check for {{ $row['medication_name'] }} could not complete. Try again, or verify manually.</flux:text>
                    @else
                        @if ($highFlags->isNotEmpty())
                            <div class="mt-2 space-y-2" aria-live="assertive">
                                @foreach ($highFlags as $flag)
                                    <div class="rounded-lg border border-red-200 bg-red-50 p-2 dark:border-red-400/20 dark:bg-red-400/10">
                                        <div class="flex items-center gap-2">
                                            <flux:badge size="sm" color="red">{{ str($flag['category'])->replace('_', ' ')->headline() }}</flux:badge>
                                        </div>
                                        <flux:text class="mt-1 text-sm">{{ $flag['explanation'] }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($otherFlags->isNotEmpty())
                            <details class="mt-2 group" @if ($highFlags->isEmpty() && $otherFlags->count() === 1) open @endif>
                                <summary class="cursor-pointer text-sm font-medium text-amber-600 dark:text-amber-400">
                                    {{ $otherFlags->count() }} flagged {{ $otherFlags->count() === 1 ? 'item' : 'items' }} — tap to review
                                </summary>
                                <div class="mt-2 space-y-2">
                                    @foreach ($otherFlags as $flag)
                                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-2 dark:border-amber-400/20 dark:bg-amber-400/10">
                                            <flux:badge size="sm" color="amber">{{ str($flag['category'])->replace('_', ' ')->headline() }}</flux:badge>
                                            <flux:text class="mt-1 text-sm">{{ $flag['explanation'] }}</flux:text>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @endif

                    <flux:text class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
                        Source: FDA label data
                        @if ($row['safety_checked_at'])
                            · Checked {{ $row['safety_checked_at']->format('d/m/Y H:i') }}
                        @endif
                    </flux:text>
                </div>
            @endforeach
        </div>
    </flux:card>
    </div>
@endif
