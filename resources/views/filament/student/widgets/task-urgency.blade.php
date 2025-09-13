@php
    $dueDate = $getRecord()->due_date;
    if (!$dueDate) {
        $priority = 'low';
        $label = 'No Rush';
        $color = 'gray';
    } else {
        $daysLeft = now()->diffInDays($dueDate, false);

        if ($daysLeft < 0) {
            $priority = 'critical';
            $label = 'Overdue!';
            $color = 'danger';
        } elseif ($daysLeft <= 1) {
            $priority = 'high';
            $label = 'Urgent';
            $color = 'danger';
        } elseif ($daysLeft <= 3) {
            $priority = 'medium';
            $label = 'Soon';
            $color = 'warning';
        } else {
            $priority = 'low';
            $label = 'Upcoming';
            $color = 'success';
        }
    }
@endphp

<x-filament::badge :color="$color" size="sm">
    {{ $label }}
</x-filament::badge>
