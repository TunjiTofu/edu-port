<?php

namespace App\Enums;

enum SubmissionTypes: string
{
    case PENDING_SUBMISSION = 'pending_submission';
    case PENDING_REVIEW = 'pending_review';
    case UNDER_REVIEW = 'under_review';
    case COMPLETED = 'completed';
    case NEEDS_REVISION = 'needs_revision';
    case FLAGGED = 'flagged';
}