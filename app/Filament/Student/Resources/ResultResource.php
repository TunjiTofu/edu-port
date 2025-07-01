<?php

namespace App\Filament\Student\Resources;

use App\Filament\Student\Resources\ResultResource\Pages;
use App\Models\Submission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;

class ResultResource extends Resource
{
    protected static ?string $model = Submission::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'My Results';
    protected static ?string $navigationGroup = 'Performance';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Result';
    protected static ?string $pluralModelLabel = 'Results';

    public static function canViewAny(): bool
    {
        return Auth::user()?->isStudent();
    }

    public static function getEloquentQuery(): Builder
    {

        return parent::getEloquentQuery()
            ->where('student_id', Auth::id())
            ->whereHas('review', function ($query) {
                $query->whereNotNull('score')
                    ->where('is_completed', true);
            })
            ->whereHas('task.resultPublications', function ($query) {
                $query->where('is_published', true);
            })
            ->with([
                'review' => function ($query) {
                    $query->whereNotNull('score')
                        ->where('is_completed', true);
                },
                'task.section.trainingProgram',
                'task.resultPublications' => function ($query) {
                    $query->where('is_published', true);
                }
            ]);

//        return parent::getEloquentQuery()
//            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
//            ->join('result_publications', 'submissions.task_id', '=', 'result_publications.task_id')
//            ->where('submissions.student_id', Auth::id())
//            ->whereNotNull('reviews.score')
//            ->where('reviews.is_completed', true)
//            ->where('result_publications.is_published', true)
//            ->select('submissions.*') // Select only submission columns to avoid conflicts
//            ->with([
//                'review',
//                'task.section.trainingProgram',
//                'task.resultPublications'
//            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form is not needed for viewing results, but required by Filament
                Forms\Components\Placeholder::make('readonly')
                    ->label('This is a read-only resource for viewing your results.')
            ]);
    }

//    public static function table(Table $table): Table
//    {
//        return $table
//            ->columns([
//
//                ViewColumn::make('task_info')
//                    ->label('Task')
//                    ->view('filament.student.table.task-description', static function ($record) {
//                        return [
//                            'title' => $record->task->title,
////                             'program' => $record->task->section->trainingProgram?->name,
//                            'section' => $record->task->section?->name,
//                        ];
//                    }),
//
//                TextColumn::make('review.score')
//                    ->label('Score')
//                    ->badge()
//                    ->color(fn (string $state): string => match (true) {
//                        $state >= 7.5 => 'success',
//                        $state >= 5 => 'warning',
//                        default => 'danger',
//                    })
//                    ->formatStateUsing(fn ($state, $record) => $state . '/' . $record->task->max_score),
//
//                TextColumn::make('submitted_at')
//                    ->label('Submitted')
//                    ->dateTime('M j, Y g:i A')
//                    ->sortable(),
//
//                TextColumn::make('review.reviewed_at')
//                    ->label('Reviewed')
//                    ->dateTime('M j, Y g:i A')
//                    ->sortable(),
//            ])
//            ->filters([
//                Tables\Filters\SelectFilter::make('task.section.trainingProgram')
//                    ->relationship('task.section.trainingProgram', 'name')
//                    ->label('Program'),
//
//                Tables\Filters\Filter::make('score_range')
//                    ->form([
//                        Forms\Components\Select::make('score_range')
//                            ->options([
//                                'excellent' => 'Excellent (7.5-10)',
//                                'good' => 'Good (5-7.4)',
//                                'needs_improvement' => 'Needs Improvement (0-4.9)',
//                            ])
//                            ->placeholder('All Scores'),
//                    ])
//                    ->query(function (Builder $query, array $data): Builder {
//                        return $query->when(
//                            $data['score_range'],
//                            function ($query, $range) {
//                                return $query->whereHas('review', function ($reviewQuery) use ($range) {
//                                    match ($range) {
//                                        'excellent' => $reviewQuery->whereBetween('score', [7.5, 10]),
//                                        'good' => $reviewQuery->whereBetween('score', [5, 7.4]),
//                                        'needs_improvement' => $reviewQuery->where('score', '<', 4.9),
//                                    };
//                                });
//                            }
//                        );
//                    }),
//            ])
//            ->actions([
//                Tables\Actions\ViewAction::make(),
//            ]);
////            ->defaultSort('review.reviewed_at', 'desc');
//    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Task Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('task.title')
                                    ->label('Task Title'),
                                TextEntry::make('task.section.trainingProgram.name')
                                    ->label('Program'),
                                TextEntry::make('task.section.name')
                                    ->label('Section'),
                                TextEntry::make('task.due_date')
                                    ->label('Due Date')
                                    ->date(),
                            ]),
                        TextEntry::make('task.description')
                            ->label('Task Description')
                            ->columnSpanFull(),
                        TextEntry::make('task.instructions')
                            ->label('Instructions')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->task->instructions)),
                    ]),

                Section::make('Submission Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('file_name')
                                    ->label('Submitted File'),
                                TextEntry::make('submitted_at')
                                    ->label('Submission Date')
                                    ->dateTime(),
                                TextEntry::make('file_type')
                                    ->label('File Type'),
                                TextEntry::make('file_size')
                                    ->label('File Size')
                                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),
                            ]),
                        TextEntry::make('student_notes')
                            ->label('Your Notes')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->student_notes)),
                    ]),

                Section::make('Review & Results')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('review.score')
                                    ->label('Score')
                                    ->badge()
//                                    ->color(fn ($state, $record): string => match (true) {
//                                        $state >= 7.5 => 'success',
//                                        $state >= 5 => 'warning',
//                                        default => 'danger',
//                                    })
                                    ->color(function ($state, $record) {
                                        $maxScore = $record->task->max_score;
                                        $score = $record->score;
                                        $scorePercentage = ($score / $maxScore) * 100;
                                        if (!$scorePercentage) return 'danger';
                                        if ($scorePercentage >= 75 && $record->task->resultPublication->is_published) return 'success';
                                        if ($scorePercentage >= 50 && $record->task->resultPublication->is_published) return 'warning';
                                        return 'gray';
                                    })
                                    ->formatStateUsing(fn ($state, $record) => $state . '/' . $record->task->max_score),
                                TextEntry::make('review.reviewed_at')
                                    ->label('Review Date')
                                    ->dateTime(),
                            ]),
                        TextEntry::make('review.comments')
                            ->label('Reviewer Comments')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->review?->comments)),
//                        TextEntry::make('review.reviewer.name')
//                            ->label('Reviewed By')
//                            ->visible(fn ($record) => !empty($record->review?->reviewer)),
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
            'index' => Pages\CustomListResults::route('/'),
            'view' => Pages\ViewResult::route('/{record}'),
        ];
    }

    // Disable create, edit, delete actions since this is read-only
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
