<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewModificationRequestResource\Pages;
use App\Models\ReviewModificationRequest;
use App\Models\User;
use App\Enums\ReviewModificationStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class ReviewModificationRequestResource extends Resource
{
    protected static ?string $model = ReviewModificationRequest::class;

    protected static ?string $navigationGroup = 'Review Management';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Modification Requests';

    protected static ?string $modelLabel = 'Review Modification Request';

    protected static ?string $pluralModelLabel = 'Review Modification Requests';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
//                        Forms\Components\Select::make('review_id')
//                            ->label('Review')
//                            ->relationship('review', 'id')
//                            ->searchable()
//                            ->preload()
//                            ->disabled()
//                            ->dehydrated(false),

                        Forms\Components\Select::make('reviewer_id')
                            ->label('Reviewer')
                            ->relationship('reviewer', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                ReviewModificationStatus::PENDING->value => 'Pending',
                                ReviewModificationStatus::APPROVED->value => 'Approved',
                                ReviewModificationStatus::REJECTED->value => 'Rejected',
                            ])
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Modification')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3)
                        ->columnSpanFull()
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Admin Response')
                    ->schema([
                        Forms\Components\Select::make('admin_id')
                            ->label('Admin')
                            ->relationship('admin', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && $record->admin_id),

                        Forms\Components\Textarea::make('admin_comments')
                            ->label('Admin Comments')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && $record->admin_comments)
                            ->rows(3),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved At')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && $record->approved_at),
                    ])
                    ->visible(fn ($record) => $record && !$record->isPending())
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student_name')
                    ->label('Student')
                    ->getStateUsing(function ($record) {
                        return $record->review->submission->student->name ?? 'Unknown Student';
                    })
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->review->submission->student->name ?? 'N/A'),


                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->reason),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Reviewed By')
                    ->placeholder('Not reviewed')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Processed At')
                    ->dateTime()
                    ->placeholder('Not processed')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\Filter::make('pending_only')
                    ->label('Pending Only')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ReviewModificationRequest $record): bool => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('admin_comments')
                            ->label('Comments (Optional)')
                            ->placeholder('Add any comments about this approval...')
                            ->rows(3),
                    ])
                    ->action(function (ReviewModificationRequest $record, array $data): void {
                        $record->approve(
                            admin: auth()->user(),
                            comments: $data['admin_comments'] ?? null
                        );

                        Notification::make()
                            ->title('Request Approved')
                            ->body('The modification request has been approved successfully.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Modification Request')
                    ->modalDescription('Are you sure you want to approve this modification request?'),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ReviewModificationRequest $record): bool => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('admin_comments')
                            ->label('Reason for Rejection')
                            ->placeholder('Please provide a reason for rejecting this request...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ReviewModificationRequest $record, array $data): void {
                        $record->reject(
                            admin: auth()->user(),
                            comments: $data['admin_comments']
                        );

                        Notification::make()
                            ->title('Request Rejected')
                            ->body('The modification request has been rejected.')
                            ->danger()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reject Modification Request')
                    ->modalDescription('Are you sure you want to reject this modification request?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('admin_comments')
                                ->label('Comments (Optional)')
                                ->placeholder('Add any comments for all selected approvals...')
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data): void {
                            $pendingRecords = $records->filter(fn ($record) => $record->isPending());

                            foreach ($pendingRecords as $record) {
                                $record->approve(
                                    admin: auth()->user(),
                                    comments: $data['admin_comments'] ?? null
                                );
                            }

                            Notification::make()
                                ->title('Requests Approved')
                                ->body($pendingRecords->count() . ' modification requests have been approved.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Requests')
                        ->modalDescription('Are you sure you want to approve all selected pending modification requests?'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewModificationRequests::route('/'),
            'view' => Pages\ViewReviewModificationRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'reviewer',
                'admin',
                'review.submission.student'
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();

        if ($pendingCount > 10) {
            return 'danger';
        } elseif ($pendingCount > 5) {
            return 'warning';
        }

        return 'primary';
    }
}
