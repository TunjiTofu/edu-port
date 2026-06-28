<?php

namespace App\Filament\Widgets;

use App\Enums\SubmissionTypes;
use App\Models\Review;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ReviewerPerformanceWidget extends BaseWidget
{
    protected static ?string $heading          = 'Reviewer Performance';
    protected static ?int    $sort             = 6;
    protected int|string|array $columnSpan     = 'full';
//    protected static ?string $pollingInterval  = '300s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role_id', Constants::REVIEWER_ID)
                    ->where('is_active', true)
                    ->withCount([
                        'reviewsAsReviewer as total_reviews',
                        'reviewsAsReviewer as pending_reviews' => fn ($q) =>
                        $q->whereHas('submission', fn ($s) =>
                        $s->whereIn('status', [
                            SubmissionTypes::PENDING_REVIEW->value,
                            SubmissionTypes::UNDER_REVIEW->value,
                        ])
                        ),
                        'reviewsAsReviewer as completed_reviews' => fn ($q) =>
                        $q->whereNotNull('reviewed_at'),
                    ])
                    ->withAvg('reviewsAsReviewer as avg_score', 'score')
                    ->addSelect([
                        'avg_turnaround' => Review::selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at))')
                            ->whereColumn('reviewer_id', 'users.id')
                            ->whereNotNull('reviewed_at'),
                        'last_activity'  => Review::select('reviewed_at')
                            ->whereColumn('reviewer_id', 'users.id')
                            ->whereNotNull('reviewed_at')
                            ->latest()->limit(1),
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Reviewer')->searchable()->weight(FontWeight::Medium)->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('total_reviews')
                    ->label('Total')->alignCenter()->badge()->color('primary')->sortable(),

                Tables\Columns\TextColumn::make('pending_reviews')
                    ->label('Pending')->alignCenter()->badge()
                    ->color(fn ($state) => match (true) {
                        $state > 10 => 'danger', $state > 5 => 'warning',
                        $state > 0  => 'info',   default    => 'success',
                    })->sortable(),

                Tables\Columns\TextColumn::make('completed_reviews')
                    ->label('Completed')->alignCenter()->badge()->color('success')->sortable(),

                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Completion %')->alignCenter()
                    ->state(fn ($record) =>
                    $record->total_reviews > 0
                        ? round(($record->completed_reviews / $record->total_reviews) * 100, 1)
                        : 0
                    )
                    ->badge()->suffix('%')
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success', $state >= 70 => 'warning', default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('avg_score')
                    ->label('Avg Score')->alignCenter()->numeric(decimalPlaces: 1)->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 8 => 'success', $state >= 6 => 'info',
                        $state >= 4 => 'warning', default     => 'danger',
                    })->sortable(),

                Tables\Columns\TextColumn::make('avg_turnaround')
                    ->label('Avg Turnaround')->alignCenter()
                    ->formatStateUsing(fn ($state) =>
                    $state ? ($state >= 24 ? round($state / 24, 1) . 'd' : round($state, 1) . 'h') : 'N/A'
                    )
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        ! $state    => 'gray',   $state <= 24  => 'success',
                        $state <= 72 => 'info',  $state <= 168 => 'warning', default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Last Active')
                    ->formatStateUsing(fn ($state) =>
                    $state ? Carbon::parse($state)->diffForHumans() : 'No activity'
                    )
                    ->color(fn ($state) => match (true) {
                        ! $state => 'gray',
                        Carbon::parse($state)->diffInDays(now()) <= 1  => 'success',
                        Carbon::parse($state)->diffInDays(now()) <= 7  => 'info',
                        Carbon::parse($state)->diffInDays(now()) <= 30 => 'warning',
                        default  => 'danger',
                    }),

                Tables\Columns\TextColumn::make('workload_status')
                    ->label('Workload')
                    ->state(fn ($record) => match (true) {
                        $record->pending_reviews > 15 => 'Overloaded',
                        $record->pending_reviews > 10 => 'Heavy',
                        $record->pending_reviews > 5  => 'Moderate',
                        $record->pending_reviews > 0  => 'Light',
                        default                       => 'Available',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Overloaded' => 'danger', 'Heavy'   => 'warning',
                        'Moderate'   => 'info',   'Light'   => 'primary',
                        'Available'  => 'success', default  => 'gray',
                    }),
            ])

            // ── Per-widget year/program filter ────────────────────────────────
            // TableWidget uses Filament table filters rather than the
            // $filter Livewire property pattern used by Chart/StatsWidgets.
            ->filters([
                Tables\Filters\SelectFilter::make('year_program')
                    ->label('Year / Program')
                    ->options(function () {
                        $opts = ['' => '🌐 All Years & Programs'];
                        $currentYear = now()->year;
                        $years = array_unique(array_merge(
                            \App\Models\Submission::selectRaw('DISTINCT YEAR(submitted_at) as yr')
                                ->orderByDesc('yr')->pluck('yr')->filter()->toArray(),
                            [$currentYear, $currentYear - 1, $currentYear - 2]
                        ));
                        rsort($years);
                        foreach ($years as $yr) { $opts["year_{$yr}"] = "📅 {$yr}"; }
                        foreach (TrainingProgram::orderByDesc('year')->get() as $p) {
                            $opts["prog_{$p->id}"] = "🎓 {$p->name}" . ($p->year ? " ({$p->year})" : '');
                        }
                        return $opts;
                    })
                    ->default('year_' . now()->year)
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? '';
                        if (! $val) return $query;

                        if (str_starts_with($val, 'year_')) {
                            $year = (int) substr($val, 5);
                            return $query->whereHas('reviewsAsReviewer.submission', fn ($q) =>
                            $q->whereYear('submitted_at', $year)
                            );
                        }

                        if (str_starts_with($val, 'prog_')) {
                            $progId = (int) substr($val, 5);
                            return $query->whereHas('reviewsAsReviewer.submission.task.section', fn ($q) =>
                            $q->where('training_program_id', $progId)
                            );
                        }

                        return $query;
                    }),
            ])
            ->filtersFormColumns(2)
            ->defaultSort('total_reviews', 'desc')
            ->actions([
                Tables\Actions\Action::make('view_reviews')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('View Submissions')
                    ->url(fn (User $record) => "/admin/submissions?tableFilters[reviewer_id][value]={$record->id}")
                    ->color('primary'),
            ])
            ->emptyStateHeading('No active reviewers')
            ->emptyStateDescription('Activate reviewers to see their performance metrics here.')
            ->emptyStateIcon('heroicon-o-users')
            ->striped();
    }
}
