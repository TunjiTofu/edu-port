@php
    use App\Enums\SubmissionTypes;

    // FIX: Removed the redundant match(true) block that mapped every SubmissionTypes
    // value back to itself — a complete no-op. Replaced with a single null-safe
    // fallback assignment.
    $status = $getRecord()->status ?? SubmissionTypes::PENDING_REVIEW->value;

    $color = match ($status) {
        SubmissionTypes::COMPLETED->value        => 'success',
        SubmissionTypes::PENDING_SUBMISSION->value => 'warning',
        SubmissionTypes::PENDING_REVIEW->value    => 'info',
        SubmissionTypes::FLAGGED->value           => 'danger',
        SubmissionTypes::NEEDS_REVISION->value    => 'warning',
        SubmissionTypes::UNDER_REVIEW->value      => 'gray',
        SubmissionTypes::SUBMITTED->value         => 'gray',
        default                                   => 'gray',
    };

    $label = match ($status) {
        // FIX: Changed 'Mark as Completed' → 'Completed'.
        // This is a display badge, not an action button.
        SubmissionTypes::COMPLETED->value         => 'Completed',
        SubmissionTypes::PENDING_SUBMISSION->value => 'Pending Submission',
        SubmissionTypes::PENDING_REVIEW->value    => 'Pending Review',
        SubmissionTypes::FLAGGED->value           => 'Flagged',
        SubmissionTypes::NEEDS_REVISION->value    => 'Needs Revision',
        SubmissionTypes::UNDER_REVIEW->value      => 'Under Review',
        SubmissionTypes::SUBMITTED->value         => 'Submitted',
        default                                   => 'Unknown',
    };
@endphp

<x-filament::badge :color="$color">
    {{ $label }}
</x-filament::badge>
