<?php

namespace App\Filament\Reviewer\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\ReviewQueueResource\Pages;
use App\Models\Submission;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReviewQueueResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon  = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel = 'Review Queue';
    protected static ?string $modelLabel      = 'Submission';
    protected static ?string $slug            = 'review-queue';
    protected static ?int    $navigationSort  = 1;

    public static function getEloquentQuery(): Builder
    {
        // Only submissions assigned to the logged-in reviewer
        return parent::getEloquentQuery()
            ->whereHas('review', fn ($q) => $q->where('reviewer_id', Auth::id()))
            ->with(['student', 'task.section.trainingProgram', 'task.rubrics', 'review']);
    }

    /**
     * Navigation badge shows how many submissions are waiting for this reviewer.
     * Encourages reviewers to clear their queue — turns green when empty.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', [
                SubmissionTypes::PENDING_REVIEW->value,
                SubmissionTypes::UNDER_REVIEW->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([

                        // ── Status indicator strip (icon) ───────────────────
                        Tables\Columns\TextColumn::make('status_icon')
                            ->label('')
                            ->getStateUsing(fn (?Submission $record): string => match (true) {
                                $record?->is_resubmission
                                && $record?->status === SubmissionTypes::PENDING_REVIEW->value => '↩',
                                $record?->status === SubmissionTypes::PENDING_REVIEW->value => '🆕',
                                $record?->status === SubmissionTypes::UNDER_REVIEW->value   => '👀',
                                $record?->status === SubmissionTypes::COMPLETED->value      => '✅',
                                $record?->status === SubmissionTypes::NEEDS_REVISION->value => '✏️',
                                $record?->status === SubmissionTypes::FLAGGED->value        => '🚩',
                                default                                                      => '📄',
                            })
                            ->size('lg')
                            ->grow(false),

                        // ── Main info ────────────────────────────────────────
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\Layout\Split::make([
                                Tables\Columns\TextColumn::make('task.title')
                                    ->weight('bold')
                                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                                    ->wrap()
                                    ->limit(60),

                                Tables\Columns\TextColumn::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        SubmissionTypes::COMPLETED->value      => 'success',
                                        SubmissionTypes::PENDING_REVIEW->value => 'info',
                                        SubmissionTypes::UNDER_REVIEW->value   => 'warning',
                                        SubmissionTypes::NEEDS_REVISION->value => 'danger',
                                        SubmissionTypes::FLAGGED->value        => 'danger',
                                        default                                 => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        SubmissionTypes::PENDING_REVIEW->value => 'New',
                                        SubmissionTypes::UNDER_REVIEW->value   => 'In Progress',
                                        SubmissionTypes::COMPLETED->value      => 'Completed',
                                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                                        SubmissionTypes::FLAGGED->value        => 'Flagged',
                                        default                                 => $state,
                                    })
                                    ->grow(false),
                            ]),

                            Tables\Columns\TextColumn::make('student.name')
                                ->label('Candidate')
                                ->icon('heroicon-m-user-circle')
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                            Tables\Columns\TextColumn::make('task.section.trainingProgram.name')
                                ->label('')
                                ->icon('heroicon-m-academic-cap')
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                ->formatStateUsing(fn ($state, ?Submission $record) =>
                                    ($record?->task?->section?->name ?? '') .
                                    ($state ? " · {$state}" : '')
                                ),

                            Tables\Columns\Layout\Split::make([
                                Tables\Columns\TextColumn::make('submitted_at')
                                    ->label('')
                                    ->since()
                                    ->icon('heroicon-m-clock')
                                    ->color('gray')
                                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall),

                                Tables\Columns\TextColumn::make('review.score')
                                    ->label('')
                                    ->formatStateUsing(function ($state, ?Submission $record) {
                                        $task = $record?->task;

                                        // Same logic as ReviewWorkspace: use rubric total
                                        // if rubrics exist, otherwise fall back to the
                                        // task's max_score (default 10).
                                        $hasRubrics = $task?->rubrics?->isNotEmpty();
                                        $total = $hasRubrics
                                            ? ($task->getTotalRubricPoints() ?? 0)
                                            : (float) ($task?->max_score ?? 10);

                                        return "Score: {$state} / {$total}";
                                    })
                                    ->badge()
                                    ->color('success')
                                    // Only show once the review is actually completed —
                                    // avoids showing the score for the default placeholder
                                    // Review row created before any scoring happens.
                                    ->visible(fn (?Submission $record) =>
                                        $record?->review?->is_completed === true
                                    ),
                            ]),
                        ])->space(1)->grow(true),
                    ])->from('sm'),
                ])->space(2),
            ])
            ->contentGrid([
                'default' => 1,
                'md'      => 2,
                'xl'      => 3,
            ])
            ->defaultSort('submitted_at', 'asc') // oldest first — first in, first out
            ->filters([
                // ── Year filter ─────────────────────────────────────────────
                // Options use string keys so ->default() string matches correctly.
                // Column is qualified as submissions.submitted_at to avoid
                // ambiguity when Eloquent builds the whereHas join internally.
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $currentYear = (string) now()->year;
                        $years       = [];

                        Submission::whereHas('review', fn ($q) =>
                        $q->where('reviewer_id', Auth::id())
                        )
                            ->selectRaw('DISTINCT YEAR(submitted_at) as yr')
                            ->orderByDesc('yr')
                            ->pluck('yr')
                            ->filter()
                            ->each(fn ($y) => $years[(string) $y] = (string) $y);

                        $years[$currentYear] = $currentYear;
                        krsort($years);

                        return ['' => 'All Years'] + $years;
                    })
                    ->default((string) now()->year)
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        // Qualify column to avoid ambiguity with joins
                        return $query->whereYear('submissions.submitted_at', (int) $data['value']);
                    }),

                // ── Status filter ────────────────────────────────────────────
                // Single SelectFilter keeps status filters mutually exclusive.
                // Previously multiple toggle filters ANDed together, so turning
                // on "Completed" + the default "Needs My Review" produced
                // WHERE status IN (pending,under_review) AND status = 'completed'
                // — always zero results.
                Tables\Filters\SelectFilter::make('status_group')
                    ->label('Status')
                    ->options([
                        ''              => 'All Statuses',
                        'needs_review'  => '🆕 Needs My Review',
                        'needs_revision'=> '✏️ Awaiting Resubmission',
                        'completed'     => '✅ Completed',
                        'flagged'       => '🚩 Flagged',
                    ])
                    ->default('needs_review')
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? '') {
                            'needs_review'   => $query->whereIn('submissions.status', [
                                SubmissionTypes::PENDING_REVIEW->value,
                                SubmissionTypes::UNDER_REVIEW->value,
                            ]),
                            'needs_revision' => $query->where('submissions.status', SubmissionTypes::NEEDS_REVISION->value),
                            'completed'      => $query->where('submissions.status', SubmissionTypes::COMPLETED->value),
                            'flagged'        => $query->where('submissions.status', SubmissionTypes::FLAGGED->value),
                            default          => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label(fn (?Submission $record) => match (true) {
                        $record && in_array($record->status, [
                            SubmissionTypes::COMPLETED->value,
                            SubmissionTypes::NEEDS_REVISION->value,
                            SubmissionTypes::FLAGGED->value,
                        ])                                                     => 'View Review',
                        $record?->is_resubmission
                        && $record?->status === SubmissionTypes::PENDING_REVIEW->value => 'Review Resubmission',
                        default                                                => 'Start Review',
                    })
                    ->icon('heroicon-o-arrow-right-circle')
                    ->button()
                    ->color(fn (?Submission $record) => match (true) {
                        $record?->is_resubmission
                        && $record?->status === SubmissionTypes::PENDING_REVIEW->value => 'warning',
                        $record?->status === SubmissionTypes::PENDING_REVIEW->value        => 'primary',
                        default                                                             => 'gray',
                    })
                    ->url(fn (?Submission $record) => $record ? Pages\ReviewWorkspace::getUrl(['record' => $record->id]) : null),
            ])
            ->emptyStateHeading('🎉 All Caught Up!')
            ->emptyStateDescription('You have no submissions waiting for review right now. Great work!')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReviewQueues::route('/'),
            'review' => Pages\ReviewWorkspace::route('/{record}/review'),
        ];
    }
}
