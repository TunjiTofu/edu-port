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
                        ->where('is_completed', true)
                        ->with(['reviewRubrics.rubric']); // Load review rubrics with rubric details
                },
                'task' => function ($query) {
                    $query->with([
                        'rubrics' => function ($q) {
                            $q->active()->ordered(); // Only load active rubrics in order
                        },
                        'section.trainingProgram',
                        'resultPublications' => function ($q) {
                            $q->where('is_published', true);
                        }
                    ]);
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
                                    ->formatStateUsing(function ($state, $record) {
                                       return number_format($state / 1024, 2) . ' KB';
                                    }),
                            ]),
                        TextEntry::make('student_notes')
                            ->label('Your Notes')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->student_notes)),
                    ]),

                // Fixed Rubrics Breakdown Section
                // Alternative: Use a real field with a simpler TextEntry approach

                // Adjusted Rubrics Breakdown Section - fits better with overall page layout
                Section::make('Rubrics Assessment')
                    ->description('Detailed breakdown of your performance against each evaluation criteria')
                    ->schema([
                        TextEntry::make('id')
                            ->label('')
                            ->html()
                            ->formatStateUsing(function ($state, $record) {
                                Log::info('TextEntry formatStateUsing called for submission: ' . $record->id);

                                try {
                                    // Check if we have necessary data
                                    if (!$record->review) {
                                        return '<div class="flex items-center justify-center p-6 text-gray-500 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-center">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-gray-400">No review available</p>
                            </div>
                        </div>';
                                    }

                                    if (!$record->task || !$record->task->rubrics || $record->task->rubrics->count() === 0) {
                                        return '<div class="flex items-center justify-center p-6 text-gray-500 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-center">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-gray-400">No rubrics available for this task</p>
                            </div>
                        </div>';
                                    }

                                    $reviewRubrics = $record->review->reviewRubrics;
                                    if (!$reviewRubrics || $reviewRubrics->isEmpty()) {
                                        $reviewRubrics = $record->review->reviewRubrics()->with('rubric')->get();

                                        if ($reviewRubrics->isEmpty()) {
                                            return '<div class="flex items-center justify-center p-6 text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                <div class="text-center">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <p class="text-sm text-amber-700 dark:text-amber-300">Rubrics evaluation not completed</p>
                                </div>
                            </div>';
                                        }
                                    }

                                    // Calculate summary stats
                                    $totalPossible = $record->task->rubrics->sum('max_points');
                                    $totalAwarded = $reviewRubrics->sum('points_awarded');
                                    $checkedCount = $reviewRubrics->where('is_checked', true)->count();
                                    $totalCount = $reviewRubrics->count();
                                    $percentage = $totalPossible > 0 ? round(($totalAwarded / $totalPossible) * 100, 1) : 0;

                                    // Start building HTML with more compact design
                                    $html = '<div class="space-y-4">';

                                    // Compact Summary Header
                                    $gradeColor = $percentage >= 90 ? 'emerald' : ($percentage >= 80 ? 'blue' : ($percentage >= 70 ? 'amber' : 'red'));

                                    $html .= '<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">';
                                    $html .= '<div class="flex items-center justify-between mb-3">';
                                    $html .= '<div class="flex items-center space-x-2">';
                                    $html .= '<div class="w-2 h-2 bg-' . $gradeColor . '-500 rounded-full"></div>';
                                    $html .= '<h4 class="font-medium text-gray-900 dark:text-gray-100">Overall Performance</h4>';
                                    $html .= '</div>';
                                    $html .= '<div class="text-right">';
                                    $html .= '<div class="text-2xl font-bold text-' . $gradeColor . '-600 dark:text-' . $gradeColor . '-400">' . $percentage . '%</div>';
                                    $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">' . $totalAwarded . '/' . $totalPossible . ' points</div>';
                                    $html .= '</div>';
                                    $html .= '</div>';

                                    // Compact progress bar
                                    $html .= '<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-3">';
                                    $html .= '<div class="bg-' . $gradeColor . '-500 h-2 rounded-full transition-all duration-300" style="width: ' . $percentage . '%"></div>';
                                    $html .= '</div>';

                                    // Quick stats in single row
                                    $html .= '<div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">';
                                    $html .= '<span>Criteria Met: <strong class="text-gray-900 dark:text-gray-100">' . $checkedCount . '/' . $totalCount . '</strong></span>';
                                    $html .= '<span>Grade: <strong class="text-' . $gradeColor . '-600 dark:text-' . $gradeColor . '-400">';
                                    if ($percentage >= 90) $html .= 'Excellent';
                                    elseif ($percentage >= 80) $html .= 'Good';
                                    elseif ($percentage >= 70) $html .= 'Satisfactory';
                                    else $html .= 'Needs Improvement';
                                    $html .= '</strong></span>';
                                    $html .= '</div>';
                                    $html .= '</div>';

                                    // Compact Rubrics Grid
                                    $html .= '<div class="grid grid-cols-1 gap-3">';

                                    foreach ($reviewRubrics as $reviewRubric) {
                                        if (!$reviewRubric->rubric) {
                                            continue;
                                        }

                                        $isChecked = $reviewRubric->is_checked;
                                        $points = $reviewRubric->points_awarded ?? 0;
                                        $maxPoints = $reviewRubric->rubric->max_points;
                                        $rubricPercentage = $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0;

                                        // Compact card design
                                        $html .= '<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-sm transition-all duration-200">';

                                        // Single row header
                                        $html .= '<div class="flex items-center justify-between mb-2">';
                                        $html .= '<div class="flex items-center space-x-3">';

                                        // Compact status indicator
                                        if ($isChecked) {
                                            $html .= '<div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">';
                                            $html .= '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>';
                                            $html .= '</div>';
                                        } else {
                                            $html .= '<div class="w-5 h-5 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">';
                                            $html .= '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>';
                                            $html .= '</div>';
                                        }

                                        $html .= '<div class="min-w-0 flex-1">';
                                        $html .= '<h5 class="font-medium text-gray-900 dark:text-gray-100 truncate">' . e($reviewRubric->rubric->title) . '</h5>';
                                        if ($reviewRubric->rubric->description) {
                                            $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">' . e($reviewRubric->rubric->description) . '</p>';
                                        }
                                        $html .= '</div>';
                                        $html .= '</div>';

                                        // Compact points display
                                        $pointsColor = $isChecked ? 'green' : 'gray';
                                        $html .= '<div class="flex items-center space-x-2">';
                                        $html .= '<div class="bg-' . $pointsColor . '-100 dark:bg-' . $pointsColor . '-900/50 text-' . $pointsColor . '-700 dark:text-' . $pointsColor . '-300 px-2 py-1 rounded text-xs font-medium">';
                                        $html .= $points . '/' . $maxPoints . ' pts';
                                        $html .= '</div>';

                                        // Mini progress bar
                                        if ($maxPoints > 0) {
                                            $html .= '<div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">';
                                            $progressColor = $isChecked ? 'bg-green-500' : 'bg-gray-400';
                                            $html .= '<div class="' . $progressColor . ' h-1.5 rounded-full transition-all duration-300" style="width: ' . $rubricPercentage . '%"></div>';
                                            $html .= '</div>';
                                        }

                                        $html .= '<div class="text-xs text-gray-500 dark:text-gray-400 font-medium">' . $rubricPercentage . '%</div>';
                                        $html .= '</div>';
                                        $html .= '</div>';

                                        // Comments section (compact)
                                        if ($reviewRubric->comments) {
                                            $html .= '<div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border-l-4 border-blue-500">';
                                            $html .= '<div class="flex items-start space-x-2">';
                                            $html .= '<svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>';
                                            $html .= '<div class="flex-1">';
                                            $html .= '<div class="text-xs font-medium text-gray-700 dark:text-white mb-1">Reviewer Feedback</div>';
                                            $html .= '<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">' . nl2br(e($reviewRubric->comments)) . '</p>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        }

                                        $html .= '</div>'; // End card
                                    }

                                    $html .= '</div>'; // End grid
                                    $html .= '</div>'; // End container

                                    Log::info('Compact rubrics HTML generated successfully');
                                    return $html;

                                } catch (\Exception $e) {
                                    Log::error('Error processing rubrics summary: ' . $e->getMessage(), [
                                        'submission_id' => $record->id,
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                    return '<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h4 class="text-red-800 dark:text-red-200 font-medium text-sm">Error Loading Rubrics</h4>
                                <p class="text-red-600 dark:text-red-300 text-xs mt-1">' . e($e->getMessage()) . '</p>
                            </div>
                        </div>
                    </div>';
                                }
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(function ($record) {
                        return $record->review &&
                            $record->task &&
                            $record->task->rubrics &&
                            $record->task->rubrics->count() > 0;
                    })
                    ->collapsible()
                    ->collapsed(false),

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
