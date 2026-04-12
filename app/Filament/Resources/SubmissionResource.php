<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Enums\SubmissionTypes;
use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\Review;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubmissionResource extends Resource
{
    protected static ?string $model          = Submission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Review Management';
    protected static ?int    $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('Submission Details')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->searchable()->preload()->disabled(),

                        Forms\Components\Select::make('task_id')
                            ->relationship('task', 'title')
                            ->searchable()->preload()->disabled(),

                        Forms\Components\TextInput::make('file_name')
                            ->label('File Name')->disabled(),

                        Forms\Components\TextInput::make('file_size')
                            ->label('File Size')
                            ->formatStateUsing(fn ($state) =>
                            $state ? number_format($state / 1024, 1) . ' KB' : '—'
                            )
                            ->disabled(),
                    ])
                    ->columns(2),

                FormSection::make('Review & Assignment')
                    ->schema([
                        // Assign reviewer — admin only action
                        Forms\Components\Select::make('reviewer_id')
                            ->label('Assign Reviewer')
                            ->options(fn () =>
                            User::whereHas('role',
                                fn ($q) => $q->where('name', RoleTypes::REVIEWER->value)
                            )
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                            )
                            ->searchable()->nullable()
                            ->dehydrated(false) // Virtual — we update review separately
                            ->helperText('Assigns or reassigns the reviewer for this submission.'),

                        Forms\Components\Select::make('status')
                            ->options([
                                SubmissionTypes::PENDING_REVIEW->value  => 'Pending Review',
                                SubmissionTypes::UNDER_REVIEW->value    => 'Under Review',
                                SubmissionTypes::COMPLETED->value       => 'Completed',
                                SubmissionTypes::NEEDS_REVISION->value  => 'Needs Revision',
                                SubmissionTypes::FLAGGED->value         => 'Flagged for Plagiarism',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Submission')
                    ->schema([
                        Infolists\Components\Grid::make(['default' => 2, 'sm' => 4])
                            ->schema([
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('Candidate')->weight('bold'),
                                Infolists\Components\TextEntry::make('task.title')
                                    ->label('Task'),
                                Infolists\Components\TextEntry::make('submitted_at')
                                    ->label('Submitted')->dateTime('M j, Y g:i A'),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        SubmissionTypes::COMPLETED->value      => 'success',
                                        SubmissionTypes::PENDING_REVIEW->value => 'info',
                                        SubmissionTypes::UNDER_REVIEW->value   => 'warning',
                                        SubmissionTypes::NEEDS_REVISION->value => 'danger',
                                        SubmissionTypes::FLAGGED->value        => 'danger',
                                        default                                => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucfirst($state))),
                            ]),

                        Infolists\Components\Grid::make(['default' => 2, 'sm' => 4])
                            ->schema([
                                Infolists\Components\TextEntry::make('file_name')->label('File'),
                                Infolists\Components\TextEntry::make('file_type')->label('Type'),
                                Infolists\Components\TextEntry::make('file_size')
                                    ->label('Size')
                                    ->formatStateUsing(fn ($state) =>
                                    $state ? number_format($state / 1024, 1) . ' KB' : '—'
                                    ),
                            ]),

                        Infolists\Components\TextEntry::make('student_notes')
                            ->label('Candidate Notes')->prose()->columnSpanFull()
                            ->visible(fn ($record) => ! empty($record->student_notes)),
                    ]),

                Infolists\Components\Section::make('Review')
                    ->schema([
                        Infolists\Components\Grid::make(['default' => 2, 'sm' => 4])
                            ->schema([
                                Infolists\Components\TextEntry::make('review.reviewer.name')
                                    ->label('Reviewer')
                                    ->placeholder('Not assigned'),
                                Infolists\Components\TextEntry::make('review.score')
                                    ->label('Score')
                                    ->badge()->color('success')
                                    ->formatStateUsing(fn ($state, $record) =>
                                    $state !== null ? $state . ' / ' . $record->task?->max_score : 'Not graded'
                                    ),
                                Infolists\Components\TextEntry::make('review.reviewed_at')
                                    ->label('Reviewed At')
                                    ->dateTime('M j, Y g:i A')
                                    ->placeholder('Not yet reviewed'),
                                Infolists\Components\IconEntry::make('review.is_completed')
                                    ->label('Completed')
                                    ->boolean(),
                            ]),
                        Infolists\Components\TextEntry::make('review.comments')
                            ->label('Reviewer Comments')->prose()->columnSpanFull()
                            ->visible(fn ($record) => ! empty($record->review?->comments)),
                    ]),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Candidate')
                    ->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()->sortable()->limit(30)
                    ->tooltip(fn ($record) => $record->task?->title),

                Tables\Columns\TextColumn::make('task.section.trainingProgram.name')
                    ->label('Program')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y')->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        SubmissionTypes::COMPLETED->value      => 'success',
                        SubmissionTypes::PENDING_REVIEW->value => 'info',
                        SubmissionTypes::UNDER_REVIEW->value   => 'warning',
                        SubmissionTypes::NEEDS_REVISION->value => 'danger',
                        SubmissionTypes::FLAGGED->value        => 'danger',
                        default                                => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucfirst($state))),

                Tables\Columns\TextColumn::make('review.reviewer.name')
                    ->label('Reviewer')
                    ->placeholder('Unassigned')
                    ->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('review.score')
                    ->label('Score')
                    ->badge()->color('success')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                SelectFilter::make('status')
                    ->options([
                        SubmissionTypes::PENDING_REVIEW->value  => 'Pending Review',
                        SubmissionTypes::UNDER_REVIEW->value    => 'Under Review',
                        SubmissionTypes::COMPLETED->value       => 'Completed',
                        SubmissionTypes::NEEDS_REVISION->value  => 'Needs Revision',
                        SubmissionTypes::FLAGGED->value         => 'Flagged',
                    ]),

                SelectFilter::make('training_program')
                    ->label('Program')
                    ->options(fn () => \App\Models\TrainingProgram::pluck('name', 'id'))
                    ->query(fn (Builder $query, $data) =>
                    $data['value']
                        ? $query->whereHas('task.section.trainingProgram',
                        fn ($q) => $q->where('id', $data['value']))
                        : $query
                    ),

                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned')
                    ->query(fn (Builder $q) =>
                    $q->whereDoesntHave('review', fn ($r) => $r->whereNotNull('reviewer_id'))
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('overdue_review')
                    ->label('Awaiting Review >7 days')
                    ->query(fn (Builder $q) =>
                    $q->where('status', SubmissionTypes::PENDING_REVIEW->value)
                        ->where('submitted_at', '<', now()->subDays(7))
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubmissions::route('/'),
            'view'   => Pages\ViewSubmission::route('/{record}'),
            'edit'   => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['student', 'task.section.trainingProgram', 'review.reviewer'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
