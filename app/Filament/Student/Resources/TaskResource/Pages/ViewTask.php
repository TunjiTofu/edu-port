<?php

namespace App\Filament\Student\Resources\TaskResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Student\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── First submission ─────────────────────────────────────────────
            Actions\Action::make('submit')
                ->label('Submit Assignment')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size('lg')
                ->visible(fn () =>
                $this->record->submissions()
                    ->where('student_id', Auth::id())
                    ->doesntExist()
                )
                ->form(fn () => TaskResource::submissionWizard())
                ->action(fn ($data) => TaskResource::handleSubmission($this->record, $data)),

            // ── Resubmit — pending review, first submission only ─────────────
            // Hidden once is_resubmission=true (already addressed a revision).
            // Student must wait for the reviewer to act again before resubmitting.
            Actions\Action::make('resubmit')
                ->label('Resubmit')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->size('lg')
                ->visible(fn () =>
                $this->record->submissions()
                    ->where('student_id', Auth::id())
                    ->where('status', SubmissionTypes::PENDING_REVIEW->value)
                    ->where('is_resubmission', false)
                    ->exists()
                )
                ->requiresConfirmation()
                ->modalHeading('Replace Your Submission?')
                ->modalDescription('Resubmitting will replace your current file. You can only do this while your submission is still awaiting review.')
                ->modalSubmitActionLabel('Yes, Replace Submission')
                ->form(fn () => TaskResource::submissionWizard())
                ->action(fn ($data) => TaskResource::handleResubmission($this->record, $data)),

            // ── Resubmit after revision request ─────────────────────────────
            // Deadline is intentionally NOT enforced for needs_revision —
            // the reviewer already accepted and evaluated the submission
            // after the deadline, so the student must be allowed to
            // address the feedback regardless of when the deadline was.
            Actions\Action::make('resubmit_revision')
                ->label('Resubmit (Revision Requested)')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->size('lg')
                ->visible(fn () =>
                $this->record->submissions()
                    ->where('student_id', Auth::id())
                    ->where('status', SubmissionTypes::NEEDS_REVISION->value)
                    ->exists()
                )
                ->requiresConfirmation()
                ->modalHeading('Resubmit Your Revised Work')
                ->modalDescription('Upload your revised file to address the reviewer\'s feedback. Your previous submission will be replaced.')
                ->modalSubmitActionLabel('Submit Revised Work')
                ->form(fn () => TaskResource::submissionWizard())
                ->action(fn ($data) => TaskResource::handleResubmission($this->record, $data)),

            // ── Status badge — no action available ───────────────────────────
            // Only shown when under review, completed, or flagged — states
            // where neither submitting nor resubmitting is possible.
            Actions\Action::make('submitted_badge')
                ->label('Submitted ✓')
                ->color('success')
                ->disabled()
                ->visible(fn () =>
                $this->record->submissions()
                    ->where('student_id', Auth::id())
                    ->whereIn('status', [
                        SubmissionTypes::UNDER_REVIEW->value,
                        SubmissionTypes::COMPLETED->value,
                        SubmissionTypes::FLAGGED->value,
                    ])
                    ->exists()
                ),
        ];
    }

    protected function resolveRecord($key): Task
    {
        return Task::with([
            'section.trainingProgram',
            'activeRubrics',
            'submissions' => fn ($q) => $q
                ->where('student_id', Auth::id())
                ->with(['review.reviewRubrics.rubric']),
        ])->findOrFail($key);
    }

    public function getTitle(): string
    {
        return $this->record->title;
    }
}
