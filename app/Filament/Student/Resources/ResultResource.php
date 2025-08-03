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
use Illuminate\Support\Facades\Log;

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
                'task.rubrics',
                'task.section.trainingProgram',
                'task.resultPublications' => function ($query) {
                    $query->where('is_published', true);
                }
            ]);

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
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('task.instructions')
                            ->label('Instructions')
                            ->html()
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


                // NEW: Rubrics Breakdown Section
                Section::make('Rubrics Breakdown')
                    ->schema([
                        TextEntry::make('rubrics_summary')
                            ->label('')
                            ->html()
                            ->formatStateUsing(function ($record) {
                                Log::alert('Rubricssss');
                                // Check if we have necessary data
                                if (!$record->review || !$record->task->rubrics->count()) {

                                    Log::info('No rubrics available for this task');;
                                    return 'No rubrics available for this task';
                                }

                                $reviewRubrics = $record->review->reviewRubrics;

                                if ($reviewRubrics->isEmpty()) {
                                    Log::info('Rubrics evaluation not completed');;
                                    return 'Rubrics evaluation not completed';
                                }

                                $html = '<div class="space-y-4">';

                                Log::info('Rubrics available for this task');
                                foreach ($reviewRubrics as $reviewRubric) {
                                    Log::info('Rubric: ' . $reviewRubric->rubric->title);
                                    $isChecked = $reviewRubric->is_checked;
                                    $checkIcon = $isChecked ? '✅' : '❌';
                                    $statusColor = $isChecked ? 'text-green-600' : 'text-red-600';

                                    $html .= '<div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">';
                                    $html .= '<div class="flex items-start justify-between mb-2">';
                                    $html .= '<h4 class="font-semibold text-gray-900 dark:text-gray-100">' . $checkIcon . ' ' . e($reviewRubric->rubric->title) . '</h4>';

                                    if ($isChecked && $reviewRubric->points_awarded) {
                                        $html .= '<span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 text-sm font-medium px-2 py-1 rounded">';
                                        $html .= $reviewRubric->points_awarded . '/' . $reviewRubric->rubric->max_points . ' points';
                                        $html .= '</span>';
                                    } else {
                                        $html .= '<span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-medium px-2 py-1 rounded">';
                                        $html .= '0/' . $reviewRubric->rubric->max_points . ' points';
                                        $html .= '</span>';
                                    }

                                    $html .= '</div>';

                                    if ($reviewRubric->rubric->description) {
                                        $html .= '<p class="text-sm text-gray-600 dark:text-gray-300 mb-2">' . e($reviewRubric->rubric->description) . '</p>';
                                    }

                                    if ($reviewRubric->comments) {
                                        $html .= '<div class="mt-2 p-2 bg-white dark:bg-gray-700 rounded border dark:border-gray-600">';
                                        $html .= '<strong class="text-sm text-gray-700 dark:text-gray-200">Reviewer Comments:</strong>';
                                        $html .= '<p class="text-sm text-gray-800 dark:text-gray-100 mt-1">' . nl2br(e($reviewRubric->comments)) . '</p>';
                                        $html .= '</div>';
                                    }

                                    $html .= '</div>';
                                }

                                // Summary
                                $totalPossible = $record->task->rubrics->sum('max_points');
                                $totalAwarded = $reviewRubrics->sum('points_awarded');
                                $checkedCount = $reviewRubrics->where('is_checked', true)->count();
                                $totalCount = $reviewRubrics->count();
                                $percentage = $totalPossible > 0 ? round(($totalAwarded / $totalPossible) * 100, 1) : 0;

                                $html .= '<div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg">';
                                $html .= '<h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Rubrics Summary</h4>';
                                $html .= '<div class="grid grid-cols-3 gap-4 text-sm">';
                                $html .= '<div><span class="font-medium">Criteria Met:</span> <span class="ml-1">' . $checkedCount . '/' . $totalCount . '</span></div>';
                                $html .= '<div><span class="font-medium">Total Points:</span> <span class="ml-1">' . $totalAwarded . '/' . $totalPossible . '</span></div>';
                                $html .= '<div><span class="font-medium">Percentage:</span> <span class="ml-1">' . $percentage . '%</span></div>';
                                $html .= '</div>';
                                $html .= '</div>';

                                $html .= '</div>';

                                return $html; // Return plain HTML string
                            })
                            ->columnSpanFull(),
                    ]),
//                    ->visible(function ($record) {
//                        return $record->review &&
//                            $record->task->rubrics->count() > 0 &&
//                            $record->review->reviewRubrics->count() > 0;
//                    }),



                Section::make('Review & Results')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('review.score')
                                    ->label('Final Score')
                                    ->badge()
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
                            ->label('Overall Reviewer Comments')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->review?->comments)),
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
