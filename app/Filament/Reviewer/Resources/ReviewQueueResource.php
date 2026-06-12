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
                            ->getStateUsing(fn (?Submission $record) => match ($record?->status) {
                                SubmissionTypes::PENDING_REVIEW->value => '🆕',
                                SubmissionTypes::UNDER_REVIEW->value   => '👀',
                                SubmissionTypes::COMPLETED->value      => '✅',
                                SubmissionTypes::NEEDS_REVISION->value => '✏️',
                                SubmissionTypes::FLAGGED->value        => '🚩',
                                default                                 => '📄',
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
                                    ->color(fn ($state) => match ($state) {
                                        SubmissionTypes::COMPLETED->value      => 'success',
                                        SubmissionTypes::PENDING_REVIEW->value => 'info',
                                        SubmissionTypes::UNDER_REVIEW->value   => 'warning',
                                        SubmissionTypes::NEEDS_REVISION->value => 'danger',
                                        SubmissionTypes::FLAGGED->value        => 'danger',
                                        default                                 => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => match ($state) {
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
                // ── Year filter — defaults to current year ─────────────────
                // Filters by the calendar year the submission was submitted.
                // Combined with the default sort (submitted_at asc), this
                // shows the oldest current-year submission at the top —
                // exactly what a reviewer should tackle first.
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $years = static::getEloquentQuery()
                            ->selectRaw('DISTINCT YEAR(submitted_at) as yr')
                            ->orderByDesc('yr')
                            ->pluck('yr', 'yr')
                            ->map(fn ($y) => (string) $y)
                            ->toArray();

                        // Always include current year even if no submissions yet
                        $currentYear = now()->year;
                        $years[$currentYear] = (string) $currentYear;
                        krsort($years);

                        return $years;
                    })
                    ->default((string) now()->year)
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereYear('submitted_at', (int) $data['value']);
                    }),

                Tables\Filters\Filter::make('needs_review')
                    ->label('🆕 Needs My Review')
                    ->query(fn (Builder $q) => $q->whereIn('status', [
                        SubmissionTypes::PENDING_REVIEW->value,
                        SubmissionTypes::UNDER_REVIEW->value,
                    ]))
                    ->default(),

                Tables\Filters\Filter::make('needs_revision')
                    ->label('✏️ Awaiting Resubmission')
                    ->query(fn (Builder $q) => $q->where('status', SubmissionTypes::NEEDS_REVISION->value)),

                Tables\Filters\Filter::make('completed')
                    ->label('✅ Completed')
                    ->query(fn (Builder $q) => $q->where('status', SubmissionTypes::COMPLETED->value)),

                Tables\Filters\Filter::make('flagged')
                    ->label('🚩 Flagged')
                    ->query(fn (Builder $q) => $q->where('status', SubmissionTypes::FLAGGED->value)),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label(fn (?Submission $record) =>
                    $record && in_array($record->status, [
                        SubmissionTypes::COMPLETED->value,
                        SubmissionTypes::NEEDS_REVISION->value,
                        SubmissionTypes::FLAGGED->value,
                    ]) ? 'View Review' : 'Start Review'
                    )
                    ->icon('heroicon-o-arrow-right-circle')
                    ->button()
                    ->color(fn (?Submission $record) =>
                    $record?->status === SubmissionTypes::PENDING_REVIEW->value ? 'primary' : 'gray'
                    )
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
