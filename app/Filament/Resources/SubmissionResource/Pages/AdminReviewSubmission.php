<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Enums\SubmissionTypes;
use App\Filament\Resources\SubmissionResource;
use App\Models\Review;
use App\Models\Submission;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminReviewSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

    // Custom view — same layout as reviewer workspace but with admin controls
    protected static string $view = 'filament.resources.submission-resource.pages.admin-review-submission';

    // ── Record resolution ─────────────────────────────────────────────────────

    protected function resolveRecord(int|string $key): Model
    {
        return Submission::with([
            'student.church',
            'student.district',
            'task.section.trainingProgram',
            'task',
            'review.reviewer',
        ])->findOrFail($key);
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        $maxScore = $this->record->task?->max_score ?? 10;
        $review   = $this->record->review;

        return $form->schema([
            Section::make('Submission Status')
                ->icon('heroicon-o-flag')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                            SubmissionTypes::UNDER_REVIEW->value   => 'Under Review',
                            SubmissionTypes::COMPLETED->value      => 'Completed',
                            SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                            SubmissionTypes::FLAGGED->value        => 'Flagged',
                        ])
                        ->required(),
                ]),

            Section::make('Score & Feedback')
                ->icon('heroicon-o-star')
                ->description("Task maximum score: {$maxScore}")
                ->schema([
                    TextInput::make('score')
                        ->label('Score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue($maxScore)
                        ->step(0.5)
                        ->suffix("/ {$maxScore}"),

                    Textarea::make('comments')
                        ->label('Reviewer Comments')
                        ->rows(6)
                        ->helperText('These comments are visible to the candidate.')
                        ->columnSpanFull(),
                ]),

            Section::make('Admin Audit Trail')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    // Show previous admin modification history if it exists
                    Placeholder::make('audit_history')
                        ->label('Previous Admin Modification')
                        ->content(function () use ($review) {
                            if (! $review?->admin_modified_by) {
                                return 'No previous admin modifications.';
                            }
                            $admin = \App\Models\User::find($review->admin_modified_by);
                            return new \Illuminate\Support\HtmlString(
                                '<div class="text-sm space-y-1">'
                                . '<div>Modified by <strong>' . e($admin?->name ?? 'Unknown') . '</strong>'
                                . ' on <strong>' . ($review->admin_modified_at ? \Carbon\Carbon::parse($review->admin_modified_at)->format('M j, Y g:i A') : '—') . '</strong></div>'
                                . '<div class="text-gray-500">Original score: ' . ($review->original_score ?? 'N/A') . '</div>'
                                . '<div class="text-gray-500">Reason: ' . e($review->admin_modification_note ?? '—') . '</div>'
                                . '</div>'
                            );
                        })
                        ->visible(fn () => (bool) $this->record->review?->admin_modified_by),

                    Textarea::make('admin_note')
                        ->label('Reason for Modification')
                        ->rows(3)
                        ->placeholder('Optional — internal only, not shown to the candidate or reviewer.')
                        ->helperText('This is an internal audit note.'),
                ]),
        ]);
    }

    // ── Pre-fill form with current review values ──────────────────────────────

    protected function fillForm(): void
    {
        $review = $this->record->review;

        $this->form->fill([
            'status'     => $this->record->status,
            'score'      => $review?->score,
            'comments'   => $review?->comments,
            'admin_note' => '',
        ]);
    }

    // ── Custom save — update review + audit trail ─────────────────────────────

    public function save(bool $shouldRedirect = false, bool $shouldSendSavedNotification = false): void
    {
        $state  = $this->form->getState();
        $review = $this->record->review;

        $scoreChanged    = (string) ($state['score'] ?? '')    !== (string) ($review?->score ?? '');
        $commentsChanged = ($state['comments'] ?? '')          !== ($review?->comments ?? '');
        $anythingChanged = $scoreChanged || $commentsChanged;

        $adminId = Auth::id();
        $now     = now();

        if ($review) {
            $update = [
                'score'        => $state['score'] ?? null,
                'comments'     => $state['comments'] ?? null,
                'is_completed' => in_array($state['status'], [
                    SubmissionTypes::COMPLETED->value,
                    SubmissionTypes::NEEDS_REVISION->value,
                    SubmissionTypes::FLAGGED->value,
                ]),
                'reviewed_at'  => $review->reviewed_at ?? $now,
            ];

            if ($anythingChanged) {
                // Only save original values once — don't overwrite a prior admin audit entry
                $update['original_score']          = $review->original_score ?? $review->score;
                $update['original_comments']       = $review->original_comments ?? $review->comments;
                $update['admin_modified_by']       = $adminId;
                $update['admin_modified_at']       = $now;
                $update['admin_modification_note'] = trim($state['admin_note']);
            }

            $review->update($update);
        } else {
            // No reviewer assigned — admin creates the review directly
            Review::create([
                'submission_id'           => $this->record->id,
                'reviewer_id'             => $adminId,
                'score'                   => $state['score'] ?? null,
                'comments'                => $state['comments'] ?? null,
                'is_completed'            => true,
                'reviewed_at'             => $now,
                'admin_modified_by'       => $adminId,
                'admin_modified_at'       => $now,
                'admin_modification_note' => trim($state['admin_note'] ?? ''),
            ]);
        }

        $this->record->update(['status' => $state['status']]);

        Log::info('Admin review modified', [
            'event'            => 'admin_review_modified',
            'submission_id'    => $this->record->id,
            'admin_id'         => $adminId,
            'score_changed'    => $scoreChanged,
            'comments_changed' => $commentsChanged,
        ]);

        Notification::make()
            ->title('Review updated')
            ->body('The submission has been updated successfully.')
            ->success()
            ->send();

        // Close the browser tab after a short delay so the notification is visible.
        // Works because the tab was opened via window.open() (->openUrlInNewTab()).
        $this->js('setTimeout(() => window.close(), 1500)');
    }

    // ── Page chrome ───────────────────────────────────────────────────────────

    public function getTitle(): string
    {
        return 'Admin Review — ' . ($this->record->student?->name ?? 'Submission');
    }

    // Remove the default EditRecord header actions (Save, Delete etc.)
    // We put our own Save button in the blade view
    protected function getHeaderActions(): array
    {
        return [];
    }
}
