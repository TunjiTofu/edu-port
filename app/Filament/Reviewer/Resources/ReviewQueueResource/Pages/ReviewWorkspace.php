<?php

namespace App\Filament\Reviewer\Resources\ReviewQueueResource\Pages;

use App\Enums\ReviewModificationStatus;
use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\ReviewQueueResource;
use App\Mail\ReviewModificationRequestedMail;
use App\Models\Review;
use App\Models\ReviewModificationRequest;
use App\Models\ReviewRubric;
use App\Models\Submission;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReviewWorkspace extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = ReviewQueueResource::class;
    protected static string $view     = 'filament.reviewer.resources.review-queue-resource.pages.review-workspace';

    public ?Review $review = null;

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        // InteractsWithRecord provides resolveRecord(), which uses the
        // resource's getEloquentQuery() — this is what Filament expects
        // for the {record} route parameter to bind correctly.
        $this->record = $this->resolveRecord($record);

        $this->record->load([
            'student', 'task.rubrics', 'task.section.trainingProgram', 'review.reviewRubrics',
        ]);

        // Get or create the review row for this reviewer
        $this->review = Review::firstOrCreate(
            ['submission_id' => $this->record->id, 'reviewer_id' => Auth::id()],
            ['score' => 0, 'is_completed' => false]
        );

        $this->review->load('reviewRubrics');

        // Build initial form state
        $rubricState = [];
        foreach ($this->record->task->rubrics as $rubric) {
            $existing = $this->review->reviewRubrics->firstWhere('rubric_id', $rubric->id);
            $rubricState["rubric_{$rubric->id}"] = $existing?->points_awarded ?? 0;
        }

        $this->form->fill([
            ...$rubricState,
            // Used only when the task has no rubrics — a single manual score field
            'manual_score' => $this->record->task->rubrics->isEmpty()
                ? ($this->review->score ?? 0)
                : 0,
            'comments' => $this->review->comments,
            'status'   => $this->record->status,
        ]);
    }

    public function getTitle(): string
    {
        return $this->record->task->title;
    }

    // ── Queue position (e.g. "3 of 12") ────────────────────────────────────
    public function getQueuePosition(): array
    {
        $queue = ReviewQueueResource::getEloquentQuery()
            ->whereIn('status', [
                SubmissionTypes::PENDING_REVIEW->value,
                SubmissionTypes::UNDER_REVIEW->value,
            ])
            ->orderBy('submitted_at', 'asc')
            ->pluck('id')
            ->toArray();

        $position = array_search($this->record->id, $queue);

        return [
            'position' => $position !== false ? $position + 1 : null,
            'total'    => count($queue),
        ];
    }

    // ── Whether this review can still be edited ────────────────────────────
    public function isLocked(): bool
    {
        return $this->review?->isLocked() ?? false;
    }

    /**
     * The most recent modification request for this review, if any.
     * Used to show its status (pending/approved/rejected) and to avoid
     * showing the "Request Modification" button if one is already pending.
     */
    public function getModificationRequest(): ?ReviewModificationRequest
    {
        if (! $this->review) {
            return null;
        }

        return ReviewModificationRequest::where('review_id', $this->review->id)
            ->latest()
            ->first();
    }

    /**
     * Header action — lets the reviewer request permission to edit a
     * completed (locked) review. Creates a ReviewModificationRequest and
     * emails all admins. Hidden if a request is already pending.
     */
    protected function getHeaderActions(): array
    {
        if (! $this->isLocked()) {
            return [];
        }

        $existingRequest = $this->getModificationRequest();

        if ($existingRequest && $existingRequest->isPending()) {
            return [];
        }

        return [
            Action::make('request_modification')
                ->label('Request Modification')
                ->icon('heroicon-o-lock-open')
                ->color('warning')
                ->modalHeading('Request Permission to Modify This Review')
                ->modalDescription(
                    'This review has already been completed and submitted. Explain why you need ' .
                    'to change the score or feedback — your program administrator will review this ' .
                    'request and can grant one-time access to edit it.'
                )
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason for Modification')
                        ->placeholder('e.g. "I awarded the wrong rubric score for criterion 2 — the candidate met the requirement."')
                        ->rows(4)
                        ->required(),
                ])
                ->modalSubmitActionLabel('Send Request')
                ->action(function (array $data) {
                    $modRequest = ReviewModificationRequest::create([
                        'review_id'   => $this->review->id,
                        'reviewer_id' => Auth::id(),
                        'reason'      => $data['reason'],
                        'status'      => ReviewModificationStatus::PENDING->value,
                    ]);

                    $this->notifyAdminsOfModificationRequest($modRequest);

                    Notification::make()
                        ->title('Request Sent')
                        ->body('Your administrator has been notified and will review your request.')
                        ->success()->send();
                }),
        ];
    }

    /**
     * Email all admin users about a new modification request.
     * Non-blocking — never delays the reviewer's action.
     */
    private function notifyAdminsOfModificationRequest(ReviewModificationRequest $modRequest): void
    {
        $modRequestId = $modRequest->id;

        dispatch(function () use ($modRequestId) {
            try {
                $modRequest = ReviewModificationRequest::with([
                    'reviewer', 'review.submission.student', 'review.submission.task.section',
                ])->find($modRequestId);

                if (! $modRequest) {
                    return;
                }

                $admins = User::whereHas('role', fn ($q) => $q->where('name', \App\Enums\RoleTypes::ADMIN->value))
                    ->where('is_active', true)
                    ->pluck('email')
                    ->filter()
                    ->toArray();

                if (empty($admins)) {
                    Log::warning('ModificationRequest: no admin emails found', [
                        'event' => 'modification_request_email_skipped',
                        'modification_request_id' => $modRequestId,
                    ]);
                    return;
                }

                Mail::to($admins)->send(new ReviewModificationRequestedMail($modRequest));

                Log::info('ModificationRequest: admin email sent', [
                    'event' => 'modification_request_email_sent',
                    'modification_request_id' => $modRequestId,
                    'admin_count' => count($admins),
                ]);
            } catch (\Exception $e) {
                Log::error('ModificationRequest: admin email failed', [
                    'event' => 'modification_request_email_failed',
                    'modification_request_id' => $modRequestId,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    public function form(Form $form): Form
    {
        $rubrics    = $this->record->task->rubrics;
        $hasRubrics = $rubrics->isNotEmpty();
        $maxTotal   = $hasRubrics
            ? $this->record->task->getTotalRubricPoints()
            : (float) ($this->record->task->max_score ?? 10);
        $locked     = $this->isLocked();

        // ── Live running total — works for both rubric and manual scoring ──
        $runningTotal = Forms\Components\Placeholder::make('running_total')
            ->label('')
            ->content(function (Get $get) use ($rubrics, $hasRubrics, $maxTotal) {
                if ($hasRubrics) {
                    $total = 0;
                    foreach ($rubrics as $rubric) {
                        $total += (float) ($get("rubric_{$rubric->id}") ?? 0);
                    }
                } else {
                    $total = (float) ($get('manual_score') ?? 0);
                }

                $pct = $maxTotal > 0 ? round(($total / $maxTotal) * 100) : 0;

                $color = match (true) {
                    $pct >= 80 => '#16a34a',
                    $pct >= 50 => '#d97706',
                    default    => '#dc2626',
                };

                return new \Illuminate\Support\HtmlString(
                    '<div style="display:flex;align-items:center;justify-content:space-between;
                          padding:16px 20px;background:#f9fafb;border-radius:12px;
                          border:1px solid #e5e7eb;">
                        <span style="font-weight:600;font-size:.95rem;">Total Score</span>
                        <span style="font-weight:700;font-size:1.4rem;color:' . $color . ';">'
                    . $total . ' / ' . $maxTotal .
                    ' <span style="font-size:.85rem;font-weight:500;color:#9ca3af;">(' . $pct . '%)</span>
                        </span>
                    </div>'
                );
            });

        if ($hasRubrics) {
            // ── Rubric-based scoring ─────────────────────────────────────────
            // Each rubric is one row in a clean table-like grid:
            // title + description on the left, a bounded points input on the right.
            $rubricRows = [];
            foreach ($rubrics as $rubric) {
                $rubricRows[] = Forms\Components\Grid::make(['default' => 1, 'sm' => 6])
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Placeholder::make("rubric_title_{$rubric->id}")
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="font-medium text-sm leading-snug">' . e($rubric->title) . '</div>' .
                                    ($rubric->description
                                        ? '<div class="text-xs text-gray-500 mt-1 leading-snug">' . e($rubric->description) . '</div>'
                                        : '')
                                )),
                        ])->columnSpan(['default' => 1, 'sm' => 4]),

                        Forms\Components\TextInput::make("rubric_{$rubric->id}")
                            ->label('Points')
                            ->hiddenLabel()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue((float) $rubric->max_points)
                            ->step(0.5)
                            ->suffix("/ {$rubric->max_points}")
                            ->live(onBlur: true)
                            ->disabled($locked)
                            ->extraInputAttributes(['style' => 'text-align: right;'])
                            ->columnSpan(['default' => 1, 'sm' => 2]),
                    ])
                    ->extraAttributes(['class' => 'rubric-row']);
            }

            $scoringSection = Forms\Components\Section::make('Score This Submission')
                ->description('Award points for each rubric criterion based on the candidate\'s work.')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema($rubricRows)
                ->extraAttributes(['class' => 'rubric-section']);

        } else {
            // ── No rubrics defined for this task — single manual score field ──
            $scoringSection = Forms\Components\Section::make('Score This Submission')
                ->description('This task has no individual rubric criteria — enter an overall score out of ' . $maxTotal . '.')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Forms\Components\TextInput::make('manual_score')
                        ->label('Score')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue($maxTotal)
                        ->step(0.5)
                        ->suffix("/ {$maxTotal}")
                        ->live(onBlur: true)
                        ->disabled($locked)
                        ->required(),
                ]);
        }

        return $form
            ->schema([
                $scoringSection,
                $runningTotal,

                Forms\Components\Section::make('Overall Feedback')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Textarea::make('comments')
                            ->label('Comments for the candidate')
                            ->rows(4)
                            ->placeholder('Share specific, constructive feedback — what was done well, and what needs improvement...')
                            ->disabled($locked)
                            ->required(fn (Get $get) => in_array($get('status'), [
                                SubmissionTypes::NEEDS_REVISION->value,
                                SubmissionTypes::FLAGGED->value,
                            ]))
                            ->helperText(fn (Get $get) => in_array($get('status'), [
                                SubmissionTypes::NEEDS_REVISION->value,
                                SubmissionTypes::FLAGGED->value,
                            ])
                                ? '⚠️ Required — the candidate will receive this feedback by email.'
                                : 'Optional, but candidates appreciate feedback even on great work!'
                            ),
                    ]),

                Forms\Components\Section::make('Decision')
                    ->icon('heroicon-o-flag')
                    ->schema([
                        Forms\Components\ToggleButtons::make('status')
                            ->label('')
                            ->inline()
                            ->options([
                                SubmissionTypes::UNDER_REVIEW->value   => 'Save Draft',
                                SubmissionTypes::COMPLETED->value      => 'Approve ✅',
                                SubmissionTypes::NEEDS_REVISION->value => 'Request Revision ✏️',
                                SubmissionTypes::FLAGGED->value        => 'Flag for Admin 🚩',
                            ])
                            ->colors([
                                SubmissionTypes::UNDER_REVIEW->value   => 'gray',
                                SubmissionTypes::COMPLETED->value      => 'success',
                                SubmissionTypes::NEEDS_REVISION->value => 'warning',
                                SubmissionTypes::FLAGGED->value        => 'danger',
                            ])
                            ->disabled($locked)
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    // ── Save (without leaving) ──────────────────────────────────────────────
    public function save(): void
    {
        $this->persist();

        Notification::make()
            ->title('Saved')
            ->body('Your review has been saved.')
            ->success()->send();
    }

    // ── Save & Next ──────────────────────────────────────────────────────────
    public function saveAndNext(): void
    {
        $this->persist();

        $next = ReviewQueueResource::getEloquentQuery()
            ->whereIn('status', [
                SubmissionTypes::PENDING_REVIEW->value,
                SubmissionTypes::UNDER_REVIEW->value,
            ])
            ->where('id', '!=', $this->record->id)
            ->orderBy('submitted_at', 'asc')
            ->first();

        if ($next) {
            Notification::make()
                ->title('Saved! On to the next one 👉')
                ->success()->send();

            $this->redirect(static::getUrl(['record' => $next->id]));
            return;
        }

        Notification::make()
            ->title('🎉 Queue Cleared!')
            ->body("You've reviewed everything in your queue. Great work!")
            ->success()->persistent()->send();

        $this->redirect(ReviewQueueResource::getUrl());
    }

    // ── Save & Close (back to queue list) ───────────────────────────────────
    public function saveAndClose(): void
    {
        $this->persist();

        Notification::make()
            ->title('Saved')
            ->success()->send();

        $this->redirect(ReviewQueueResource::getUrl());
    }

    /**
     * Core save logic shared by all three actions.
     * Syncs rubric scores, updates the review, and updates the submission status.
     * The SubmissionObserver automatically handles the needs-revision email
     * when status transitions — no email logic needed here.
     */
    private function persist(): void
    {
        $data = $this->form->getState();

        $rubrics = $this->record->task->rubrics;
        $total   = 0;

        if ($rubrics->isNotEmpty()) {
            foreach ($rubrics as $rubric) {
                $points = (float) ($data["rubric_{$rubric->id}"] ?? 0);
                // Cap defensively at the rubric's max — the form already
                // enforces this, but guard against tampering/edge cases.
                $points = min($points, (float) $rubric->max_points);
                $total += $points;

                ReviewRubric::updateOrCreate(
                    ['review_id' => $this->review->id, 'rubric_id' => $rubric->id],
                    [
                        'points_awarded' => $points,
                        'is_checked'     => $points > 0,
                    ]
                );
            }
        } else {
            // No rubrics — single manual score, capped at the task's max_score
            $maxScore = (float) ($this->record->task->max_score ?? 10);
            $total    = min((float) ($data['manual_score'] ?? 0), $maxScore);
        }

        $newStatus  = $data['status'] ?? $this->record->status;
        $isComplete = in_array($newStatus, [
            SubmissionTypes::COMPLETED->value,
            SubmissionTypes::NEEDS_REVISION->value,
            SubmissionTypes::FLAGGED->value,
        ]);

        $this->review->update([
            'score'        => $total,
            'comments'     => $data['comments'] ?? null,
            'is_completed' => $isComplete,
            'reviewed_at'  => $isComplete ? now() : $this->review->reviewed_at,
        ]);

        // ── Re-lock after an approved modification ──────────────────────────
        // If this save was made possible by an approved-but-unused modification
        // request, consume it now. Without this, hasApprovedModificationRequest()
        // would keep returning true forever and isLocked() would never be true
        // again — leaving the review permanently editable.
        if ($isComplete) {
            $activeRequest = $this->review->getActiveModificationRequest();

            if ($activeRequest) {
                $activeRequest->markAsUsed();

                Log::info('Reviewer: modification request consumed', [
                    'event'                    => 'modification_request_used',
                    'modification_request_id' => $activeRequest->id,
                    'review_id'                => $this->review->id,
                    'reviewer_id'              => Auth::id(),
                ]);
            }
        }

        // Updating the submission's status here is what triggers
        // SubmissionObserver::updated() — which sends the needs-revision
        // email automatically if $newStatus === 'needs_revision'.
        $oldStatus = $this->record->status;

        Log::info('Reviewer: about to update submission status', [
            'event'         => 'submission_status_update_attempt',
            'submission_id' => $this->record->id,
            'old_status'    => $oldStatus,
            'new_status'    => $newStatus,
        ]);

        $this->record->update(['status' => $newStatus]);

        Log::info('Reviewer: submission reviewed', [
            'event'         => 'submission_reviewed',
            'submission_id' => $this->record->id,
            'reviewer_id'   => Auth::id(),
            'score'         => $total,
            'status'        => $newStatus,
        ]);
    }
}
