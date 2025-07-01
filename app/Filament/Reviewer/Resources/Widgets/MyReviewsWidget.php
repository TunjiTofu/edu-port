<?php

namespace App\Filament\Reviewer\Resources\Widgets;

use App\Models\Review;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class MyReviewsWidget extends BaseWidget
{
    protected static ?string $heading = 'My Recent Reviews';
    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Review::query()
            ->where('reviewer_id', auth()->id())
            ->with(['submission.student', 'submission.task'])
            ->latest('reviewed_at')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('submission.student.name')
                ->label('Student'),
            Tables\Columns\TextColumn::make('submission.task.title')
                ->label('Task')
                ->limit(30),
            Tables\Columns\TextColumn::make('is_completed')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? 'Completed' : 'Needs Review')
                ->colors([
                    'success' => true,
                    'danger' => false,
                ]),
            Tables\Columns\TextColumn::make('score')
                ->label('Score'),
            Tables\Columns\TextColumn::make('reviewed_at')
                ->label('Reviewed')
                ->since(),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('view_all')
                ->label('View All Submissions')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('filament.reviewer.resources.submissions.index'))
                ->button()
                ->color('primary'),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [5];
    }

    protected function paginateTableQuery(Builder $query): \Illuminate\Pagination\Paginator
    {
        return $query->simplePaginate(5);
    }

    protected function getTablePaginationPageOptions(): array
    {
        return [];
    }
}
