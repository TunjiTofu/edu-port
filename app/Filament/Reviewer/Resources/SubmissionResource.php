<?php

namespace App\Filament\Reviewer\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Reviewer\Resources\SubmissionResource\Pages;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Submissions to Review';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isReviewer();
    }

    public static function getEloquentQuery(): Builder
    {
        $reviewer = auth()->user();

//        return parent::getEloquentQuery()
//            ->whereHas('student', function ($query) use ($reviewer) {
//                // Reviewer cannot review submissions from their own district
//                $query->where('district_id', '!=', $reviewer->district_id);
//            })
//            ->with(['student', 'task.section.trainingProgram', 'reviews' => function ($query) use ($reviewer) {
//                $query->where('reviewer_id', $reviewer->id);
//            }]);

        return parent::getEloquentQuery()
            ->whereHas('reviews', function ($query) use ($reviewer) {
                $query->where('reviewer_id', $reviewer->id);
            })
            ->with([
                'student',
                'task.section.trainingProgram',
                'review',
                'reviews' => function ($query) use ($reviewer) {
                    $query->where('reviewer_id', $reviewer->id);
                }
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Details')
                    ->schema([
                        Forms\Components\TextInput::make('student.name')
                            ->label('Student Name')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->name;
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('student.id')
                            ->label('Student ID')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->id;
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('student.district.name')
                            ->label('Student District')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->district->name;
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('student.church.name')
                            ->label('Student Church')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->student->church->name;
                            })
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Submission Details')
                    ->schema([
                        Forms\Components\TextInput::make('task.title')
                            ->label('Task')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->title;
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('task.section.title')
                            ->label('Section')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->section->name;
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('task.section.trainingProgram.name')
                            ->label('Training Program')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->section->trainingProgram->name;
                            })
                            ->disabled(),
                        Forms\Components\TextInput::make('task.max_score')
                            ->label('Maximum Score')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->task->max_score . ' points';
                            })
                            ->disabled(),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('file_display')
                                ->label('Submitted File')
                                ->disabled()
                                ->formatStateUsing(function ($record) {
                                    return $record->file_path.'/'.$record->file_name;
                                })
                                ->helperText('Click the download button (↓)'),

                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('download')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(function (Submission $record) {
                                        return $record ? static::getDownloadUrl($record) : null;
                                    })
                                    ->openUrlInNewTab()
                            ])->fullWidth()
                        ]),

                        Forms\Components\Textarea::make('student_notes')
                            ->label('Student Notes')
                            ->disabled()
                            ->rows(3),
                    ])
                ->columns(2),

                Forms\Components\Section::make('Similarity Check')
                    ->schema([
                        Forms\Components\Placeholder::make('similarity_score')
                            ->label('Similarity Score')
                            ->content(fn (Submission $record): string =>
                            $record->similarity_score ? $record->similarity_score . '%' : 'Not checked'
                            ),
                        Forms\Components\Textarea::make('similarity_details')
                            ->label('Similar Submissions')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->columns(2)
                    ->visible(fn (Submission $record): bool => $record->similarity_score !== null),

                Forms\Components\Section::make('Review')
                    ->schema([
                        Forms\Components\Select::make('review_status')
                            ->label('Review Status')
                            ->options([
                                SubmissionTypes::COMPLETED->value => 'Mark as Completed',
                                SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                                SubmissionTypes::UNDER_REVIEW->value => 'Still in Review'
                            ])
                            ->default(SubmissionTypes::UNDER_REVIEW->value )
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
//                                if ($state !== SubmissionTypes::COMPLETED->value) {
//                                    $set('score', 0.0);
//                                }
                                if ($state === SubmissionTypes::UNDER_REVIEW->value) {
                                    $set('review.score', null);
                                }
                            }),

                        Forms\Components\TextInput::make('review.score')
                            ->label('Score')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->score;
                            })
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.1')
                            ->minValue(0)
                            ->maxValue(fn (Forms\Get $get, $record) => $record ? $record->task->max_score : 10)
                            ->visible(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]))
                            ->required(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]))
                            ->validationMessages([
                                'numeric' => 'The score must be a valid number.',
                                'required' => 'Please provide a valid numeric score for this review.',
                                'min' => 'The score cannot be negative.',
                                'max' => 'The score cannot exceed the maximum allowed for this task.',
                            ])
                            ->helperText(fn ($record) => $record ? "Maximum score for this task: {$record->task->max_score} points" : '')
                            ->placeholder('Enter score (numbers only)'),

                        Forms\Components\Textarea::make('comments')
                            ->label('Review Comments')
                            ->required(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value]))
                            ->rows(4)
                            ->helperText(function (Forms\Get $get) {
                                return match($get('review_status')) {
                                    'completed' => 'Provide feedback on why this submission was approved.',
                                    'needs_revision' => 'Explain what needs to be improved or corrected.',
                                    default => 'Optional comments about the submission.'
                                };
                            }),

                        Forms\Components\Toggle::make('notify_student')
                            ->label('Notify Student')
                            ->default(true)
                            ->helperText('Send email notification to student about the review')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('review_status'), [SubmissionTypes::COMPLETED->value, SubmissionTypes::NEEDS_REVISION->value])),
                    ])
                    ->columns(2),

                // Admin-only section for additional review controls
//                Forms\Components\Section::make('Administrative Controls')
//                    ->schema([
//                        Forms\Components\Select::make('assign_additional_reviewer')
//                            ->label('Assign Additional Reviewer')
//                            ->options(function () {
//                                return \App\Models\User::where('role', 'reviewer')
//                                    ->where('id', '!=', auth()->id())
//                                    ->pluck('name', 'id');
//                            })
//                            ->placeholder('Select a reviewer for second opinion')
//                            ->helperText('Optional: Assign another reviewer for complex cases'),
//
//                        Forms\Components\Textarea::make('admin_notes')
//                            ->label('Administrative Notes')
//                            ->rows(2)
//                            ->helperText('Internal notes (not visible to student)'),
//                    ])
//                    ->columns(2)
//                    ->visible(fn () => auth()->user()->role === 'admin'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.district.name')
                    ->label('District')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task.section.trainingProgram.name')
                    ->label('Program')
                    ->toggleable()
                    ->sortable(),
//                Tables\Columns\IconColumn::make('similarity_checked')
//                    ->label('Similarity')
//                    ->boolean()
//                    ->trueIcon('heroicon-o-check-circle')
//                    ->falseIcon('heroicon-o-x-circle')
//                    ->trueColor('success')
//                    ->falseColor('danger'),
//                Tables\Columns\TextColumn::make('similarity_score')
//                    ->label('Score %')
//                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '-')
//                    ->color(fn ($state) => match (true) {
//                        $state > 70 => 'danger',
//                        $state > 50 => 'warning',
//                        default => 'success'
//                    }),
                Tables\Columns\TextColumn::make('submission.status')
                    ->label('Submission Status')
                    ->getStateUsing(function (Submission $record) {
                        return $record->status;
                    })
                    ->badge()
                    ->colors([
                        'gray' => SubmissionTypes::PENDING_REVIEW->value,
                        'info' => SubmissionTypes::UNDER_REVIEW->value ,
                        'warning' => SubmissionTypes::NEEDS_REVISION->value,
                        'success' => SubmissionTypes::COMPLETED->value,
                        'danger' => SubmissionTypes::FLAGGED->value,

                    ]),

                Tables\Columns\TextColumn::make('review.is_completed')
                    ->label('Review Completed')
                    ->getStateUsing(function (Submission $record) {
                        return $record->review?->is_completed ? 'Yes' : 'No';
                    })
                    ->badge()
                    ->colors([
                        'success' => 'Yes',
                        'danger' => 'No',
                    ]),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('review.reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->placeholder('Not reviewed yet')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('task.section.training_program_id')
                    ->label('Training Program')
                    ->relationship('task.section.trainingProgram', 'name'),
                SelectFilter::make('task.section_id')
                    ->label('Section')
                    ->relationship('task.section', 'name'),
                SelectFilter::make('student.district_id')
                    ->label('Student District')
                    ->relationship('student.district', 'name'),
                Filter::make('pending_review')
                    ->label('Pending Review')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereHas('reviews', function ($q) {
                            $q->where('reviewer_id', auth()->id())
                                ->where('is_completed', false);
                        })
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View & Review'),
//                Tables\Actions\EditAction::make()
//                    ->label('View & Review')
//                    ->icon('heroicon-m-eye'),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-m-arrow-down-tray')
//                    ->url(function (Submission $record) {
////                        if (!Storage::exists($record->file_path)) {
////                            Notification::make()
////                                ->title('File not found')
////                                ->danger()
////                                ->send();
////                            return null;
////                        }
//                        return Storage::url($record->file_path.'/'.$record->file_name);
//                    })
//                    ->url(fn (Submission $record) => route('submission.download', $record))
                    ->url(function (Submission $record) {
                        return $record ? static::getDownloadUrl($record) : null;
                    })
                    ->openUrlInNewTab()
                    ->hidden(fn (Submission $record): bool => !Storage::exists($record->file_path.'/'.$record->file_name)),

//                Tables\Actions\Action::make('download') // Changed from Action to Tables\Actions\Action
//                ->label('Download')
//                    ->icon('heroicon-m-arrow-down-tray')
//                    ->url(fn (Submission $record): string => Storage::url($record->file_path.'/'.$record->file_name))
//                    ->openUrlInNewTab(),


//                Tables\Actions\Action::make('check_similarity') // Changed from Action to Tables\Actions\Action
//                ->label('Check Similarity')
//                    ->icon('heroicon-m-magnifying-glass')
//                    ->action(function (Submission $record) {
//                        // Trigger similarity check
////                        app(\App\Services\SimilarityCheckService::class)->checkSimilarity($record);
////
////                        Notification::make()
////                            ->title('Similarity check initiated')
////                            ->success()
////                            ->send();
//                    })
//                    ->visible(fn (Submission $record): bool => !$record->similarity_checked),
            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
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
//            'index' => Pages\ListSubmissions::route('/'),
//            'create' => Pages\CreateSubmission::route('/create'),
//            'edit' => Pages\EditSubmission::route('/{record}/edit'),

            'index' => Pages\ListSubmissions::route('/'),
            'view' => Pages\ViewSubmission::route('/{record}'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Reviewers cannot create submissions
    }

//    public static function canDelete(Model $record): bool
//    {
//        return false; // Reviewers cannot delete submissions
//    }

    protected static function getDownloadUrl(Submission $submission): ?string
    {
        try {
            $fullPath = $submission->file_path.'/'.$submission->file_name;

            // Check if file exists first
            if (!Storage::disk(config('filesystems.default'))->exists($fullPath)) {
                Log::error("File not found at path: {$fullPath}");
                return null;
            }

            // Generate temporary URL with proper expiration
            return Storage::disk(config('filesystems.default'))
                ->temporaryUrl(
                    $fullPath,
                    now()->addMinutes(30),
                    [
                        'ResponseContentDisposition' => 'attachment; filename="'.$submission->file_name.'"'
                    ]
                );
        } catch (\Exception $e) {
            Log::error("Failed to generate download URL: ".$e->getMessage());
            return null;
        }
    }
}
