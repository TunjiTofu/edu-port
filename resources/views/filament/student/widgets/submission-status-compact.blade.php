@php
    $status = match(true) {
        $getRecord()->score !== null && $getRecord()->is_published => 'graded',
        $getRecord()->score !== null && !$getRecord()->is_published => 'reviewed',
        default => 'submitted'
    };

    $color = match($status) {
        'graded' => 'success',
        'reviewed' => 'warning',
        'submitted' => 'info',
        default => 'gray'
    };

    $label = match($status) {
        'graded' => 'Graded',
        'reviewed' => 'Under Review',
        'submitted' => 'Submitted',
        default => 'Unknown'
    };
@endphp

<x-filament::badge :color="$color">
    {{ $label }}
</x-filament::badge>
