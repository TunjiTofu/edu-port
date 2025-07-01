<?php
//
//namespace App\Filament\Widgets;
//
//use App\Models\Review;
//use App\Models\User;
//use App\Services\Utility\Constants;
//use Carbon\Carbon;
//use Filament\Tables;
//use Filament\Tables\Table;
//use Filament\Widgets\TableWidget as BaseWidget;
//use Filament\Support\Enums\FontWeight;
//use Illuminate\Support\Facades\DB;
//
//class ReviewerPerformanceWidget extends BaseWidget
//{
//    protected static ?string $heading = 'Reviewer Performance';
//    protected static ?int $sort = 6;
//    protected int | string | array $columnSpan = 'full';
//    protected static ?string $pollingInterval = '300s'; // 5 minutes
//
//    public function table(Table $table): Table
//    {
//        return $table
//            ->query(
//                User::query()
//                    ->where('role_id', Constants::REVIEWER_ID)
//                    ->where('is_active', true)
//                    ->withCount([
//                        'reviewsAsReviewer as total_reviews',
//                        'reviewsAsReviewer as pending_reviews' => function ($query) {
//                            $query->whereHas('submission', function ($q) {
//                                $q->where('status', 'under_review');
//                            });
//                        },
//                        'reviewsAsReviewer as completed_reviews' => function ($query) {
//                            $query->whereNotNull('reviewed_at');
//                        }
//                    ])
//                    ->withAvg('reviewsAsReviewer as avg_score', 'score')
//                    ->with([
//                        'reviewsAsReviewer' => function ($query) {
//                            $query->whereNotNull('reviewed_at')
//                                ->selectRaw('reviewer_id, AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_turnaround')
//                                ->groupBy('reviewer_id');
//                        }
//                    ])
//            )
//            ->columns([
//                Tables\Columns\TextColumn::make('name')
//                    ->label('Reviewer')
//                    ->searchable()
//                    ->weight(FontWeight::Medium)
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('email')
//                    ->label('Email')
//                    ->searchable()
//                    ->toggleable(),
//
//                Tables\Columns\TextColumn::make('total_reviews')
//                    ->label('Total Reviews')
//                    ->alignCenter()
//                    ->badge()
//                    ->color('primary')
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('pending_reviews')
//                    ->label('Pending')
//                    ->alignCenter()
//                    ->badge()
//                    ->color(fn($state) => match (true) {
//                        $state > 10 => 'danger',
//                        $state > 5 => 'warning',
//                        $state > 0 => 'info',
//                        default => 'success'
//                    })
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('completed_reviews')
//                    ->label('Completed')
//                    ->alignCenter()
//                    ->badge()
//                    ->color('success')
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('completion_rate')
//                    ->label('Completion %')
//                    ->alignCenter()
//                    ->state(function ($record) {
//                        if ($record->total_reviews == 0) return 0;
//                        return round(($record->completed_reviews / $record->total_reviews) * 100, 1);
//                    })
//                    ->badge()
//                    ->color(fn($state) => match (true) {
//                        $state >= 90 => 'success',
//                        $state >= 70 => 'warning',
//                        default => 'danger'
//                    })
//                    ->suffix('%')
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('avg_score')
//                    ->label('Avg Score Given')
//                    ->alignCenter()
//                    ->numeric(decimalPlaces: 1)
//                    ->badge()
//                    ->color(fn($state) => match (true) {
//                        $state >= 8 => 'success',
//                        $state >= 6 => 'info',
//                        $state >= 4 => 'warning',
//                        default => 'danger'
//                    })
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('avg_turnaround')
//                    ->label('Avg Turnaround')
//                    ->alignCenter()
//                    ->state(function ($record) {
//                        $reviews = $record->reviewsAsReviewer
//                            ->whereNotNull('reviewed_at');
//
//                        if ($reviews->isEmpty()) {
//                            return 'N/A';
//                        }
//
//                        $totalHours = 0;
//                        $count = 0;
//
//                        foreach ($reviews as $review) {
//                            if ($review->reviewed_at && $review->created_at) {
//                                $hours = Carbon::parse($review->created_at)
//                                    ->diffInHours(Carbon::parse($review->reviewed_at));
//                                $totalHours += $hours;
//                                $count++;
//                            }
//                        }
//
//                        if ($count == 0) return 'N/A';
//
//                        $avgHours = $totalHours / $count;
//
//                        if ($avgHours >= 24) {
//                            return round($avgHours / 24, 1) . 'd';
//                        }
//
//                        return round($avgHours, 1) . 'h';
//                    })
//                    ->badge()
//                    ->color(function ($record) {
//                        $reviews = $record->reviewsAsReviewer
//                            ->whereNotNull('reviewed_at');
//
//                        if ($reviews->isEmpty()) return 'gray';
//
//                        $totalHours = 0;
//                        $count = 0;
//
//                        foreach ($reviews as $review) {
//                            if ($review->reviewed_at && $review->created_at) {
//                                $hours = Carbon::parse($review->created_at)
//                                    ->diffInHours(Carbon::parse($review->reviewed_at));
//                                $totalHours += $hours;
//                                $count++;
//                            }
//                        }
//
//                        if ($count == 0) return 'gray';
//
//                        $avgHours = $totalHours / $count;
//
//                        return match (true) {
//                            $avgHours <= 24 => 'success', // 1 day or less
//                            $avgHours <= 72 => 'info',    // 3 days or less
//                            $avgHours <= 168 => 'warning', // 1 week or less
//                            default => 'danger'
//                        };
//                    }),
//
//                Tables\Columns\TextColumn::make('last_activity')
//                    ->label('Last Activity')
//                    ->state(function ($record) {
//                        $lastReview = $record->reviewsAsReviewer()
//                            ->whereNotNull('reviewed_at')
//                            ->latest('reviewed_at')
//                            ->first();
//
//                        return $lastReview ? $lastReview->reviewed_at->diffForHumans() : 'No activity';
//                    })
//                    ->color(function ($record) {
//                        $lastReview = $record->reviewsAsReviewer()
//                            ->whereNotNull('reviewed_at')
//                            ->latest('reviewed_at')
//                            ->first();
//
//                        if (!$lastReview) return 'gray';
//
//                        $daysAgo = $lastReview->reviewed_at->diffInDays(now());
//
//                        return match (true) {
//                            $daysAgo <= 1 => 'success',
//                            $daysAgo <= 7 => 'info',
//                            $daysAgo <= 30 => 'warning',
//                            default => 'danger'
//                        };
//                    }),
//
//                Tables\Columns\TextColumn::make('workload_status')
//                    ->label('Workload')
//                    ->state(function ($record) {
//                        return match (true) {
//                            $record->pending_reviews > 15 => 'Overloaded',
//                            $record->pending_reviews > 10 => 'Heavy',
//                            $record->pending_reviews > 5 => 'Moderate',
//                            $record->pending_reviews > 0 => 'Light',
//                            default => 'Available'
//                        };
//                    })
//                    ->badge()
//                    ->color(fn(string $state): string => match ($state) {
//                        'Overloaded' => 'danger',
//                        'Heavy' => 'warning',
//                        'Moderate' => 'info',
//                        'Light' => 'primary',
//                        'Available' => 'success',
//                        default => 'gray',
//                    }),
//            ])
//            ->defaultSort('total_reviews', 'desc')
//            ->actions([
//                Tables\Actions\Action::make('view_reviews')
//                    ->label('View Reviews')
//                    ->icon('heroicon-o-eye')
//                    ->url(fn(User $record): string => "/admin/submissions?tableFilters[reviewer_id][value]={$record->id}")
//                    ->color('primary'),
//            ])
//            ->emptyStateHeading('No active reviewers')
//            ->emptyStateDescription('Activate reviewers to see their performance metrics here.')
//            ->emptyStateIcon('heroicon-o-users')
//            ->striped();
//    }
//}


namespace App\Filament\Widgets;

use App\Models\Review;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;

class ReviewerPerformanceWidget extends BaseWidget
{
    protected static ?string $heading = 'Reviewer Performance';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role_id', Constants::REVIEWER_ID)
                    ->where('is_active', true)
                    ->withCount([
                        'reviewsAsReviewer as total_reviews',
                        'reviewsAsReviewer as pending_reviews' => function ($query) {
                            $query->whereHas('submission', function ($q) {
                                $q->where('status', 'pending_review');
                            });
                        },
                        'reviewsAsReviewer as completed_reviews' => function ($query) {
                            $query->whereNotNull('reviewed_at');
                        }
                    ])
                    ->withAvg('reviewsAsReviewer as avg_score', 'score')
                    ->addSelect([
                        'avg_turnaround' => Review::selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at))')
                            ->whereColumn('reviewer_id', 'users.id')
                            ->whereNotNull('reviewed_at'),
                        'last_activity' => Review::select('reviewed_at')
                            ->whereColumn('reviewer_id', 'users.id')
                            ->whereNotNull('reviewed_at')
                            ->latest()
                            ->limit(1)
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Reviewer')
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_reviews')
                    ->label('Total Reviews')
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pending_reviews')
                    ->label('Pending')
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state > 10 => 'danger',
                        $state > 5 => 'warning',
                        $state > 0 => 'info',
                        default => 'success'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_reviews')
                    ->label('Completed')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Completion %')
                    ->alignCenter()
                    ->state(function ($record) {
                        if ($record->total_reviews == 0) return 0;
                        return round(($record->completed_reviews / $record->total_reviews) * 100, 1);
                    })
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger'
                    })
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_score')
                    ->label('Avg Score Given')
                    ->alignCenter()
                    ->numeric(decimalPlaces: 1)
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state >= 8 => 'success',
                        $state >= 6 => 'info',
                        $state >= 4 => 'warning',
                        default => 'danger'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_turnaround')
                    ->label('Avg Turnaround')
                    ->alignCenter()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        if ($state >= 24) return round($state / 24, 1) . 'd';
                        return round($state, 1) . 'h';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        return match (true) {
                            $state <= 24 => 'success',
                            $state <= 72 => 'info',
                            $state <= 168 => 'warning',
                            default => 'danger'
                        };
                    }),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->formatStateUsing(function ($state) {
                        return $state ? Carbon::parse($state)->diffForHumans() : 'No activity';
                    })
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        $daysAgo = Carbon::parse($state)->diffInDays(now());
                        return match (true) {
                            $daysAgo <= 1 => 'success',
                            $daysAgo <= 7 => 'info',
                            $daysAgo <= 30 => 'warning',
                            default => 'danger'
                        };
                    }),

                Tables\Columns\TextColumn::make('workload_status')
                    ->label('Workload')
                    ->state(function ($record) {
                        return match (true) {
                            $record->pending_reviews > 15 => 'Overloaded',
                            $record->pending_reviews > 10 => 'Heavy',
                            $record->pending_reviews > 5 => 'Moderate',
                            $record->pending_reviews > 0 => 'Light',
                            default => 'Available'
                        };
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Overloaded' => 'danger',
                        'Heavy' => 'warning',
                        'Moderate' => 'info',
                        'Light' => 'primary',
                        'Available' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('total_reviews', 'desc')
            ->actions([
                Tables\Actions\Action::make('view_reviews')
                    ->label('View Reviews')
                    ->icon('heroicon-o-eye')
                    ->url(fn(User $record): string => "/admin/submissions?tableFilters[reviewer_id][value]={$record->id}")
                    ->color('primary'),
            ])
            ->emptyStateHeading('No active reviewers')
            ->emptyStateDescription('Activate reviewers to see their performance metrics here.')
            ->emptyStateIcon('heroicon-o-users')
            ->striped();
    }
}
