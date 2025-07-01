@php
    use App\Enums\SubmissionTypes;
    $status = match(true) {
        $getRecord()->status === SubmissionTypes::COMPLETED->value => SubmissionTypes::COMPLETED->value,
        $getRecord()->status === SubmissionTypes::PENDING_SUBMISSION->value => SubmissionTypes::PENDING_SUBMISSION->value,
        $getRecord()->status === SubmissionTypes::PENDING_REVIEW->value => SubmissionTypes::PENDING_REVIEW->value,
        $getRecord()->status === SubmissionTypes::FLAGGED->value => SubmissionTypes::FLAGGED->value,
        $getRecord()->status === SubmissionTypes::NEEDS_REVISION->value => SubmissionTypes::NEEDS_REVISION->value,
        $getRecord()->status === SubmissionTypes::UNDER_REVIEW->value => SubmissionTypes::UNDER_REVIEW->value,
        $getRecord()->status === SubmissionTypes::SUBMITTED->value => SubmissionTypes::SUBMITTED->value,
        default => SubmissionTypes::PENDING_REVIEW->value
    };

    $color = match($status) {
        SubmissionTypes::COMPLETED->value => 'success',
        SubmissionTypes::PENDING_SUBMISSION->value => 'warning',
        SubmissionTypes::PENDING_REVIEW->value => 'info',
        SubmissionTypes::FLAGGED->value => 'danger',
        SubmissionTypes::NEEDS_REVISION->value => 'warning',
        SubmissionTypes::UNDER_REVIEW->value => 'gray',
        SubmissionTypes::SUBMITTED->value => 'gray',
        default => 'gray'
    };

    $label = match($status) {
//        'graded' => 'Graded',
//        'reviewed' => 'Under Review',
//        'submitted' => 'Submitted',
//
        SubmissionTypes::COMPLETED->value => 'Mark as Completed',
        SubmissionTypes::PENDING_SUBMISSION->value => 'Pending Submission',
        SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
        SubmissionTypes::FLAGGED->value => 'Flagged',
        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
        SubmissionTypes::UNDER_REVIEW->value => 'Still in Review',
        SubmissionTypes::SUBMITTED->value => 'Submitted',
        default => 'Unknown'
    };
@endphp

<x-filament::badge :color="$color">
    {{ $label }}
</x-filament::badge>
