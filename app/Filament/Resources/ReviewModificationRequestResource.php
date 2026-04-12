<?php

namespace App\Filament\Resources;

use App\Enums\ReviewModificationStatus;
use App\Filament\Resources\ReviewModificationRequestResource\Pages;
use App\Models\ReviewModificationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReviewModificationRequestResource extends Resource
{
    protected static ?string $model           = ReviewModificationRequest::class;
    protected static ?string $navigationGroup = 'Review Management';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $navigationIcon  = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Modification Requests';
    protected static ?string $modelLabel      = 'Modification Request';
    protected static ?string $pluralModelLabel = 'Modification Requests';

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ReviewModificationRequest::where('status', ReviewModificationStatus::PENDING->value)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    // ── Form (view/edit only — readonly for request details) ─────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Select::make('reviewer_id')
                            ->label('Reviewer')
                            ->relationship('reviewer', 'name')
                            ->disabled()->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                ReviewModificationStatus::PENDING->value  => 'Pending',
                                ReviewModificationStatus::APPROVED->value => 'Approved',
                                ReviewModificationStatus::REJECTED->value => 'Rejected',
                            ])
                            ->disabled()->dehydrated(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->disabled()->dehydrated(false)
                            ->rows(3)->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Admin Response')
                    ->schema([
                        Forms\Components\Textarea::make('admin_comments')
                            ->label('Admin Comments')
                            ->rows(3)->columnSpanFull()
                            ->helperText('Add a note when approving or rejecting.'),
                    ]),
            ]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Request')
                    ->schema([
                        Infolists\Components\Grid::make(['default' => 2, 'sm' => 3])
                            ->schema([
                                Infolists\Components\TextEntry::make('reviewer.name')
                                    ->label('Reviewer')->weight('bold'),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        ReviewModificationStatus::PENDING->value  => 'warning',
                                        ReviewModificationStatus::APPROVED->value => 'success',
                                        ReviewModificationStatus::REJECTED->value => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Requested')->since(),
                            ]),
                        Infolists\Components\TextEntry::make('reason')
                            ->label('Reason')->prose()->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Admin Response')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('admin.name')
                                    ->label('Handled By')->placeholder('Not yet handled'),
                                Infolists\Components\TextEntry::make('approved_at')
                                    ->label('Handled At')
                                    ->dateTime('M j, Y g:i A')->placeholder('—'),
                            ]),
                        Infolists\Components\TextEntry::make('admin_comments')
                            ->label('Admin Comments')->prose()->columnSpanFull()
                            ->placeholder('No comments.'),
                    ]),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewer')->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('review.submission.task.title')
                    ->label('Task')->limit(30)->toggleable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')->limit(50)->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        ReviewModificationStatus::PENDING->value  => 'warning',
                        ReviewModificationStatus::APPROVED->value => 'success',
                        ReviewModificationStatus::REJECTED->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Handled By')->placeholder('—')->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ReviewModificationStatus::PENDING->value  => 'Pending',
                        ReviewModificationStatus::APPROVED->value => 'Approved',
                        ReviewModificationStatus::REJECTED->value => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Modification Request?')
                    ->modalDescription('The reviewer will be able to modify their review once.')
                    ->form([
                        Forms\Components\Textarea::make('admin_comments')
                            ->label('Comments (optional)')->rows(2),
                    ])
                    ->visible(fn (ReviewModificationRequest $record) => $record->isPending())
                    ->action(function (ReviewModificationRequest $record, array $data) {
                        $record->approve(Auth::user(), $data['admin_comments'] ?? null);
                        Notification::make()->title('Request Approved')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_comments')
                            ->label('Reason for rejection')->rows(2)->required(),
                    ])
                    ->visible(fn (ReviewModificationRequest $record) => $record->isPending())
                    ->action(function (ReviewModificationRequest $record, array $data) {
                        $record->reject(Auth::user(), $data['admin_comments']);
                        Notification::make()->title('Request Rejected')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) =>
                        $records->each->approve(Auth::user())
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewModificationRequests::route('/'),
            'view'  => Pages\ViewReviewModificationRequest::route('/{record}'),
        ];
    }
}
