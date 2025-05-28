<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Enums\SubmissionTypes;
use App\Filament\Resources\SubmissionResource\Pages;
use App\Filament\Resources\SubmissionResource\RelationManagers;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
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
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\TextEntry;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry as ComponentsTextEntry;

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
            ->schema([
                FormSection::make('Submission Details')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Forms\Components\Select::make('task_id')
                            ->relationship('task', 'title')
                            ->searchable()
                            ->preload(),
                        // ->disabled(),

                        Forms\Components\TextInput::make('file_path')
                            ->label('File Path')
                            ->disabled()
                            ->visible(fn($record) => $record?->file_path),

                        Forms\Components\Textarea::make('text_content')
                            ->label('Text Submission')
                            ->rows(5)
                            ->disabled()
                            ->visible(fn($record) => $record?->text_content),

                        // For view context (read-only)
                        Forms\Components\TextInput::make('reviewer_name')
                            ->label('Assigned Reviewer')
                            ->formatStateUsing(fn($record) => $record->review->reviewer->name ?? 'No reviewer assigned')
                            ->disabled()
                            ->visible(fn($context) => $context === 'view'),

                        // For edit context (select field)
                        Forms\Components\Select::make('review.reviewer_id')
                            ->label('Assign Reviewer')
                            ->relationship(
                                name: 'review.reviewer',  // Relationship name
                                titleAttribute: 'name',   // Display attribute
                                modifyQueryUsing: fn(Builder $query) => $query->where('role_id', Constants::REVIEWER_ID)
                                    ->where('is_active', true)
                                    ->where('church_id', '!=', Auth::user()->church_id)
                                    ->where('district_id', '!=', Auth::user()->district_id)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->required()
                            ->visible(fn($context) => $context === 'edit'),
                    ])->columns(2),

                FormSection::make('Review Information')
                    ->schema([
                        // Forms\Components\TextInput::make('review.reviewer.name')
                        //     ->label('Assigned Reviewer')
                        //     ->disabled()
                        //     ->formatStateUsing(function ($record) {
                        //         return $record->review->reviewer->name ?? 'No reviewer assigned';
                        //     }),

                        // Forms\Components\Select::make('review.reviewer.name')
                        //     ->label('Assigned Reviewer')
                        //     ->relationship(
                        //         name: 'review.reviewer',
                        //         titleAttribute: 'name'
                        //     )
                        //     // ->formatStateUsing(function ($record) {
                        //     //     return $record->review->reviewer->name ?? 'No reviewer assigned';
                        //     // })
                        //     ->searchable()
                        //     ->preload(),



                        // Status field (works for both)
                        Forms\Components\Select::make('status')
                            ->options([
                                SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                                SubmissionTypes::UNDER_REVIEW->value => 'Under Review',
                                SubmissionTypes::COMPLETED->value => 'Completed',
                                SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                                SubmissionTypes::FLAGGED->value => 'Flagged for Plaigiarism',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('review.score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(function ($record) {
                                return $record->task->max_score ?? 10;
                            })
                            ->visible(function ($get) {
                                return in_array($get('status'), ['completed', 'needs_revision']);
                            })
                            ->required(function ($get) {
                                return $get('status') === 'completed';
                            })
                            ->formatStateUsing(fn($record) => $record->review->score ?? 'N/A'),

                        Forms\Components\Textarea::make('review.comments')
                            ->label('Reviewer Comments')
                            ->rows(4)
                            ->visible(function ($get) {
                                return in_array($get('status'), ['under_review', 'completed', 'needs_revision', 'flagged']);
                            })
                            ->required(function ($get) {
                                return $get('status') === 'needs_revision';
                            })
                            ->formatStateUsing(fn($record) => $record->review->comments ?? 'N/A')
                            ->columnSpanFull(),
                    ])->columns(2),

                // FormSection::make('Plagiarism Detection')
                //     ->schema([
                //         Forms\Components\TextInput::make('similarity_score')
                //             ->label('Similarity Score (%)')
                //             ->numeric()
                //             ->disabled()
                //             ->suffix('%'),

                //         Forms\Components\Toggle::make('is_flagged')
                //             ->label('Flagged for Plagiarism')
                //             ->disabled(),

                //         Forms\Components\Textarea::make('similarity_details')
                //             ->label('Similarity Details')
                //             ->rows(3)
                //             ->disabled()
                //             ->helperText('Details about similar submissions found'),
                //     ])->columns(2),

                FormSection::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submitted At')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Reviewed At')
                            ->formatStateUsing(fn($record) => $record->review->reviewed_at?->format('Y-m-d H:i:s') ?? 'N/A')
                            ->disabled(),
                    ])->columns(2),

                FormSection::make('Admin Override')
                    ->schema([
                        Forms\Components\Toggle::make('review.admin_override')
                            ->label('Admin Override?')
                            ->reactive()
                            ->formatStateUsing(fn($record) => $record->review->admin_override ?? false)
                            ->helperText('Override the reviewer\'s decision')
                            ->disabled(),

                        Forms\Components\Select::make('review.overridden_by')
                            ->label('Overridden By')
                            ->options(User::where('role_id', Constants::ADMIN_ID)->pluck('name', 'id')) // Only admins
                            ->default(Auth::user()->id)
                            ->searchable()
                            ->required(fn($get) => $get('review.admin_override'))
                            ->formatStateUsing(fn($record) => $record->review->overridden_by ?? Auth::user()->id)
                            ->visible(fn($get) => $get('review.admin_override'))
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('review.overridden_at')
                            ->label('Override Date')
                            // ->default(now())
                            ->required(fn($get) => $get('review.admin_override'))
                            ->formatStateUsing(fn($record) => $record->review->overridden_at ? Carbon::parse($record->review->overridden_at)->format('Y-m-d H:i:s') : now()?->format('Y-m-d H:i:s'))
                            ->visible(fn($get) => $get('review.admin_override'))
                            ->disabled(),

                        Forms\Components\Textarea::make('review.override_reason')
                            ->label('Override Reason')
                            ->rows(3)
                            ->required(fn($get) => $get('review.admin_override'))
                            ->formatStateUsing(fn($record) => $record->review->override_reason ?? '')
                            ->visible(fn($get) => $get('review.admin_override'))
                            ->disabled()
                            ->helperText('Explain why this override is necessary')
                            ->columnSpanFull(),

                    ])
                    ->collapsible()
                    ->columns(3)
            ]);
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
                    ->label('Score')
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
                        SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                        SubmissionTypes::UNDER_REVIEW->value => 'Under Review',
                        SubmissionTypes::COMPLETED->value => 'Completed',
                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                        SubmissionTypes::FLAGGED->value => 'Flagged for Review',
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
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn(Submission $record) => $record->file_path . '/' . $record->file_name)
                    ->openUrlInNewTab(),
                // ->visible(fn(Submission $record) => $record->file_path. '/' . $record->file_name && file_exists(public_path($record->file_path . '/' . $record->file_name))),

                Tables\Actions\Action::make('assign_reviewer')
                    ->label('Assign Reviewer')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('review.reviewer_id')
                            ->label('Select Reviewer')
                            ->options(function (Submission $record) {
                                return \App\Models\User::where('role_id', Constants::REVIEWER_ID)
                                    ->where('is_active', true)
                                    ->where('church_id', '!=', $record->student->church_id)
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
                            ['reviewer_id' => $data['review']['reviewer_id']]
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

                Tables\Actions\Action::make('override_score')
                    ->label('Override Score')
                    ->icon('heroicon-o-pencil-square')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->label('New Score')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10)
                            ->default(function (?Submission $record) {
                                return $record->review?->score ?? 10;
                            })
                            ->rules([
                                'numeric',
                                'min:0',
                                'max:10',
                            ]),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('Override Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Submission $record, array $data) {

                        // Update the related Review
                        $record->review()->updateOrCreate(
                            ['submission_id' => $record->id],
                            [
                                'score' => $data['score'],
                                'is_completed' => true,
                                'admin_override' => true,
                                'override_reason' => $data['override_reason'],
                                'overridden_by' => Auth::user()->id,
                                'overridden_at' => now()
                            ],
                        );

                        // Update the Submission status
                        $record->update([
                            'status' => SubmissionTypes::COMPLETED->value,
                        ]);
                    }),
                Tables\Actions\ForceDeleteAction::make(), // Permanent delete
                Tables\Actions\RestoreAction::make(), // Restore soft-deleted
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
        // ->defaultSort('submitted_at', 'desc');
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
            ])
            ->with(['student', 'task.section', 'review.reviewer']);
    }
}
