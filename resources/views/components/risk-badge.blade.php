@props(['level'])

<flux:badge size="sm" :color="$level->color()">
    {{ $level->value }} risk
</flux:badge>
