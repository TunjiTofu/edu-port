<?php

namespace App\Filament\Reviewer\Resources;

use App\Enums\ReviewModificationStatus;
use App\Filament\Reviewer\Resources\ModificationRequestResource\Pages;
use App\Filament\Reviewer\Resources\ReviewQueueResource\Pages\ReviewWorkspace;
use App\Models\ReviewModificationRequest;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ModificationRequestResource extends Resource
{
    protected static ?string $model = ReviewModificationRequest::class;

    protected static ?string $navigationIcon  = 'heroicon-o-lock-open';
    protected static ?string $navigationLabel = 'My Modification Requests';
    protected static ?string $modelLabel      = 'Modification Request';
    protected static ?string $slug            = 'modification-requests';
    protected static ?int    $navigationSort  = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('reviewer_id', Auth::id())
            ->with(['review.submission.student', 'review.submission.task.section', 'admin']);
    }

    /**
     * Badge shows count of pending requests awaiting admin decision.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status', ReviewModificationStatus::PENDING->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('review.submission.task.title')
                    ->label('Task')
                    ->weight('bold')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('review.submission.student.name')
                    ->label('Candidate')
                    ->icon('heroicon-m-user-circle')
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Your Reason')
                    ->limit(60)
                    ->tooltip(fn (?ReviewModificationRequest $record) => $record?->reason)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        ReviewModificationStatus::PENDING->value  => 'warning',
                        ReviewModificationStatus::APPROVED->value => 'success',
                        ReviewModificationStatus::REJECTED->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        ReviewModificationStatus::PENDING->value  => '⏳ Pending',
                        ReviewModificationStatus::APPROVED->value => '✅ Approved',
                        ReviewModificationStatus::REJECTED->value => '❌ Rejected',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('admin_comments')
                    ->label('Admin Comment')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('—')
                    ->color('gray')
                    ->tooltip(fn (?ReviewModificationRequest $record) => $record?->admin_comments),

                Tables\Columns\IconColumn::make('used_at')
                    ->label('Used')
                    ->boolean()
                    ->getStateUsing(fn (?ReviewModificationRequest $record) => $record?->used_at !== null)
                    ->visible(fn (?ReviewModificationRequest $record) =>
                        $record?->status === ReviewModificationStatus::APPROVED->value
                    )
                    ->tooltip(fn (?ReviewModificationRequest $record) =>
                    $record?->used_at
                        ? 'You already used this approval to update the review.'
                        : 'Approved — you can update the review now.'
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->color('gray')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ReviewModificationStatus::PENDING->value  => '⏳ Pending',
                        ReviewModificationStatus::APPROVED->value => '✅ Approved',
                        ReviewModificationStatus::REJECTED->value => '❌ Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open_review')
                    ->label('Open Review')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->button()
                    ->color('primary')
                    ->visible(fn (?ReviewModificationRequest $record) =>
                        $record?->status === ReviewModificationStatus::APPROVED->value
                        && $record?->used_at === null
                    )
                    ->url(fn (?ReviewModificationRequest $record) =>
                    $record ? ReviewWorkspace::getUrl(['record' => $record->review->submission_id]) : null
                    ),
            ])
            ->emptyStateHeading('No Modification Requests')
            ->emptyStateDescription("You haven't requested any changes to completed reviews.")
            ->emptyStateIcon('heroicon-o-lock-open');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModificationRequests::route('/'),
        ];
    }
}
