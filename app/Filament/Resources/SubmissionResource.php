<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Enums\SubmissionTypes;
use App\Filament\Resources\SubmissionResource\Pages;
use App\Filament\Resources\SubmissionResource\RelationManagers;
use App\Models\Review;
use App\Models\Submission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Review Management';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('student.phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        SubmissionTypes::PENDING_REVIEW->value => 'primary',
                        SubmissionTypes::UNDER_REVIEW->value => 'info',
                        SubmissionTypes::COMPLETED->value => 'success',
                        SubmissionTypes::NEEDS_REVISION->value => 'secondary',
                        SubmissionTypes::FLAGGED->value => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('review.score')
                    ->alignCenter()
                    ->badge()
                    ->color(function ($record) {
                        $score = $record->review->score ?? null;
                        $maxScore = $record->task->max_score ?? null;

                        if (null === $score) return 'secondary';
                        if (null === $maxScore) return 'gray';

                        $percentage = ($score / $maxScore) * 100;

                        return match (true) {
                            $percentage >= 75 => 'success',
                            $percentage >= 50 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->formatStateUsing(function ($record) {
                        $score = $record->review->score ?? null;
                        $maxScore = $record->task->max_score ?? null;

                        return match (true) {
                            null === $score => 'N/A',
                            null === $maxScore => (string) $score,
                            default => "{$score}/{$maxScore}",
                        };
                    })
                    ->tooltip(function ($record) {
                        $score = $record->review->score ?? null;
                        $maxScore = $record->task->max_score ?? null;

                        return match (true) {
                            null === $score => 'Not yet graded',
                            null === $maxScore => 'Raw score',
                            default => round(($score / $maxScore) * 100) . '% of maximum score',
                        };
                    }),
                Tables\Columns\TextColumn::make('review.reviewer.name')
                    ->label('Reviewer')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('review.reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'pending' => 'Pending Review',
                        'under_review' => 'Under Review',
                        'completed' => 'Completed',
                        'needs_revision' => 'Needs Revision',
                        'flagged' => 'Flagged for Review',
                    ]),

                SelectFilter::make('task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('section')
                    ->label('Section')
                    ->options(function () {
                        return \App\Models\Section::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            $query->whereHas('task.section', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('reviewer_id')
                    ->label('Reviewer')
                    ->relationship(
                        name: 'review.reviewer',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('role_id', 2)
                    )
                    ->searchable()
                    ->preload(),

                // Tables\Filters\Filter::make('similarity_score')
                //     ->form([
                //         Forms\Components\TextInput::make('min_similarity')
                //             ->label('Minimum Similarity %')
                //             ->numeric()
                //             ->minValue(0)
                //             ->maxValue(100),
                //         Forms\Components\TextInput::make('max_similarity')
                //             ->label('Maximum Similarity %')
                //             ->numeric()
                //             ->minValue(0)
                //             ->maxValue(100),
                //     ])
                //     ->query(function (Builder $query, array $data): Builder {
                //         return $query
                //             ->when(
                //                 $data['min_similarity'],
                //                 fn(Builder $query, $value): Builder => $query->where('similarity_score', '>=', $value),
                //             )
                //             ->when(
                //                 $data['max_similarity'],
                //                 fn(Builder $query, $value): Builder => $query->where('similarity_score', '<=', $value),
                //             );
                //     }),

                Tables\Filters\Filter::make('submitted_date')
                    ->form([
                        Forms\Components\DatePicker::make('submitted_from')
                            ->label('Submitted From'),
                        Forms\Components\DatePicker::make('submitted_until')
                            ->label('Submitted Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['submitted_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['submitted_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn(Submission $record) => $record->file_path . '/' . $record->file_name)
                    ->openUrlInNewTab(),
                // ->visible(fn(Submission $record) => $record->file_path. '/' . $record->file_name && file_exists(public_path($record->file_path . '/' . $record->file_name))),

                // Tables\Actions\Action::make('assign_reviewer')
                //     ->label('Assign Reviewer')
                //     ->icon('heroicon-o-user-plus')
                //     ->color('warning')
                //     ->form([
                //         Forms\Components\Select::make('reviewer_id')
                //             ->label('Select Reviewer')
                //             ->options(function (Submission $record) {
                //                 return \App\Models\User::where('role', RoleTypes::REVIEWER->value)
                //                     ->where('district_id', '!=', $record->student->district_id)
                //                     ->pluck('name', 'id');
                //             })
                //             ->required()
                //             ->searchable(),
                //     ])
                //     ->action(function (Review $record, array $data) {
                //         $record->update([
                //             'reviewer_id' => $data['reviewer_id'],
                //             'status' => 'under_review',
                //         ]);
                //     }),
                // ->visible(fn(Submission $record) => !$record->reviewer_id),
                Tables\Actions\Action::make('assign_reviewer')
                    ->label('Assign Reviewer')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('reviewer_id')
                            ->label('Select Reviewer')
                            ->options(function (Submission $record) {
                                return \App\Models\User::where('role_id', RoleTypes::REVIEWER->value)
                                    ->where('district_id', '!=', $record->student->district_id)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (Submission $record, array $data) {
                        // Update the related Review
                        $record->review()->updateOrCreate(
                            ['submission_id' => $record->id],
                            ['reviewer_id' => $data['reviewer_id']]
                        );

                        // Update the Submission status
                        $record->update([
                            'status' => SubmissionTypes::UNDER_REVIEW->value,
                        ]);

                        Notification::make()
                            ->title('Reviewer assigned successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(function (Submission $record) {
                        return !$record->reviewer_id &&
                            $record->status === SubmissionTypes::PENDING_REVIEW->value;
                    }),

                // Tables\Actions\Action::make('check_plagiarism')
                //     ->label('Check Plagiarism')
                //     ->icon('heroicon-o-magnifying-glass')
                //     ->color('info')
                //     ->requiresConfirmation()
                //     ->action(function (Submission $record) {
                //         app(PlagiarismService::class)->checkSubmission($record);
                //     })
                //     ->visible(fn(Submission $record) => !$record->similarity_score),

                Tables\Actions\Action::make('override_score')
                    ->label('Override Score')
                    ->icon('heroicon-o-pencil-square')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->label('New Score')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('Override Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Submission $record, array $data) {
                        $record->update([
                            'score' => $data['score'],
                            'status' => 'completed',
                            'admin_override' => true,
                            'override_reason' => $data['override_reason'],
                            'overridden_by' => auth()->id(),
                            'overridden_at' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSubmissions::route('/'),
            'create' => Pages\CreateSubmission::route('/create'),
            'view' => Pages\ViewSubmission::route('/{record}'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
