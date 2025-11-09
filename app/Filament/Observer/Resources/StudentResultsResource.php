<?php

namespace App\Filament\Observer\Resources;

use App\Filament\Observer\Resources\StudentResultsResource\Pages;
use App\Models\User;
use App\Models\Task;
use App\Models\Section;
use App\Services\Utility\Constants;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentResultsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Intending MGs Results';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Intending MG Result';

    protected static ?string $pluralModelLabel = 'Intending MGs Results';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->isObserver();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Intending MG Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('church.name')
                    ->label('Church')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('submitted_tasks')
                    ->label('Tasks Submitted')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function (User $record): string {
                        $submitted = $record->submissions()->count();
                        $total = Task::where('is_active', 1)->count();
                        return "{$submitted}/{$total}";
                    }),

                Tables\Columns\TextColumn::make('pending_tasks')
                    ->label('Not Submitted')
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(function (User $record): int {
                        $submitted = $record->submissions()->pluck('task_id')->toArray();
                        $total = Task::where('is_active', 1)->count();
                        $notSubmitted = Task::where('is_active', 1)
                            ->whereNotIn('id', $submitted)
                            ->count();
                        return $notSubmitted;
                    }),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total Score')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function (User $record): string {
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->select(DB::raw('SUM(CAST(reviews.score AS DECIMAL(10,2))) as total_score'))
                            ->value('total_score') ?? 0;

                        $totalMaxScore = DB::table('tasks')
                            ->where('is_active', 1)
                            ->select(DB::raw('SUM(CAST(max_score AS DECIMAL(10,2))) as total_max'))
                            ->value('total_max') ?? 0;

                        return round($studentScore, 1) . '/' . round($totalMaxScore, 1);
                    })
                    ->color('info'),

                Tables\Columns\TextColumn::make('calculated_score_percentage')
                    ->label('Score /100')
                    ->alignCenter()
                    ->badge()
                    ->sortable()
                    ->getStateUsing(function (User $record): string {
                        // Use the calculated column if available, otherwise calculate it
                        if (isset($record->calculated_score_percentage)) {
                            return number_format($record->calculated_score_percentage, 1) . '/100';
                        }

                        // Fallback calculation if needed
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->select(DB::raw('SUM(CAST(reviews.score AS DECIMAL(10,2))) as total_score'))
                            ->value('total_score') ?? 0;

                        $totalMaxScore = DB::table('tasks')
                            ->where('is_active', 1)
                            ->select(DB::raw('SUM(CAST(max_score AS DECIMAL(10,2))) as total_max'))
                            ->value('total_max') ?? 0;

                        if ($totalMaxScore == 0) {
                            return '0/100';
                        }

                        $percentage = ($studentScore / $totalMaxScore) * 100;
                        return number_format($percentage, 1) . '/100';
                    })
                    ->color(function (User $record): string {
                        // Use calculated column if available
                        if (isset($record->calculated_score_percentage)) {
                            $percentage = $record->calculated_score_percentage;

                            return match (true) {
                                $percentage >= 75 => 'success',
                                $percentage >= 50 => 'warning',
                                $percentage == 0 => 'gray',
                                default => 'danger',
                            };
                        }

                        // Fallback calculation
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->select(DB::raw('SUM(CAST(reviews.score AS DECIMAL(10,2))) as total_score'))
                            ->value('total_score') ?? 0;

                        $totalMaxScore = DB::table('tasks')
                            ->where('is_active', 1)
                            ->select(DB::raw('SUM(CAST(max_score AS DECIMAL(10,2))) as total_max'))
                            ->value('total_max') ?? 0;

                        if ($totalMaxScore == 0) return 'gray';

                        $percentage = ($studentScore / $totalMaxScore) * 100;

                        return match (true) {
                            $percentage >= 75 => 'success',
                            $percentage >= 50 => 'warning',
                            $studentScore == 0 => 'gray',
                            default => 'danger',
                        };
                    }),

                Tables\Columns\TextColumn::make('score_out_of_60')
                    ->label('Score /60')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function (User $record): string {
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->select(DB::raw('SUM(CAST(reviews.score AS DECIMAL(10,2))) as total_score'))
                            ->value('total_score') ?? 0;

                        $totalMaxScore = DB::table('tasks')
                            ->where('is_active', 1)
                            ->select(DB::raw('SUM(CAST(max_score AS DECIMAL(10,2))) as total_max'))
                            ->value('total_max') ?? 0;

                        if ($totalMaxScore == 0) {
                            return '0/60';
                        }

                        $percentage = ($studentScore / $totalMaxScore) * 100;
                        $scoreOutOf60 = ($percentage / 100) * 60;

                        return number_format($scoreOutOf60, 1) . '/60';
                    })
                    ->color(function (User $record): string {
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->select(DB::raw('SUM(CAST(reviews.score AS DECIMAL(10,2))) as total_score'))
                            ->value('total_score') ?? 0;

                        $totalMaxScore = DB::table('tasks')
                            ->where('is_active', 1)
                            ->select(DB::raw('SUM(CAST(max_score AS DECIMAL(10,2))) as total_max'))
                            ->value('total_max') ?? 0;

                        if ($totalMaxScore == 0 || $studentScore == 0) return 'gray';

                        $percentage = ($studentScore / $totalMaxScore) * 100;
                        $scoreOutOf60 = ($percentage / 100) * 60;

                        return match (true) {
                            $scoreOutOf60 >= 45 => 'success',
                            $scoreOutOf60 >= 30 => 'warning',
                            default => 'danger',
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('church_id')
                    ->relationship('church', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('district_id')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_submissions')
                    ->label('Has Submissions')
                    ->query(fn (Builder $query) => $query->has('submissions'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (User $record) {
                        return static::exportStudentPdf($record);
                    }),
            ])
            ->defaultSort('calculated_score_percentage', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentResults::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $totalMaxScore = DB::table('tasks')
            ->where('is_active', 1)
            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?? 1;

        return parent::getEloquentQuery()
            ->where('users.role_id', Constants::STUDENT_ID)
            ->leftJoin('submissions', 'users.id', '=', 'submissions.student_id')
            ->leftJoin('reviews', 'submissions.id', '=', 'reviews.submission_id')
            ->selectRaw("users.*,
                (COALESCE(SUM(CAST(reviews.score AS DECIMAL(10,2))), 0) / {$totalMaxScore} * 100) as calculated_score_percentage")
            ->groupBy('users.id')
            ->with(['church', 'district']);
    }

    protected static function exportStudentPdf(User $student)
    {
        $data = static::getStudentDetailedData($student);

        $pdf = Pdf::loadView('pdf.student-result', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'student-result-' . $student->id . '-' . now()->format('Y-m-d') . '.pdf');
    }

    protected static function getStudentDetailedData(User $student): array
    {
        // Reload student with fresh relationships
        $student = User::with(['church', 'district', 'submissions.task.section', 'submissions.review'])
            ->find($student->id);

        // Get all active tasks
        $allTasks = Task::where('is_active', 1)
            ->with('section')
            ->orderBy('section_id')
            ->orderBy('order_index')
            ->get();

        // Get student's submissions
        $submissions = $student->submissions()
            ->with(['task.section', 'review'])
            ->get()
            ->keyBy('task_id');

        // Calculate total scores
        $studentScore = $submissions->sum(fn($sub) => $sub->review?->score ?? 0);
        $totalMaxScore = $allTasks->sum('max_score');
        $percentage = $totalMaxScore > 0 ? ($studentScore / $totalMaxScore) * 100 : 0;
        $scoreOutOf100 = $percentage;
        $scoreOutOf60 = ($percentage / 100) * 60;

        // Group tasks by section and calculate section scores
        $sections = [];
        foreach ($allTasks->groupBy('section_id') as $sectionId => $sectionTasks) {
            $section = $sectionTasks->first()->section;
            $sectionMaxScore = $sectionTasks->sum('max_score');
            $sectionStudentScore = 0;

            $tasksData = [];
            foreach ($sectionTasks as $task) {
                $submission = $submissions->get($task->id);
                $score = $submission?->review?->score ?? null;
                $comments = $submission?->review?->comments ?? null;
                $status = $submission ? 'Submitted' : 'Not Submitted';

                if ($score !== null) {
                    $sectionStudentScore += $score;
                }

                $tasksData[] = [
                    'title' => $task->title,
                    'max_score' => $task->max_score,
                    'score' => $score,
                    'comments' => $comments,
                    'status' => $status,
                    'submitted_at' => $submission?->submitted_at,
                ];
            }

            $sectionPercentage = $sectionMaxScore > 0 ? ($sectionStudentScore / $sectionMaxScore) * 100 : 0;

            $sections[] = [
                'name' => $section->name,
                'tasks' => $tasksData,
                'total_score' => $sectionStudentScore,
                'max_score' => $sectionMaxScore,
                'percentage' => $sectionPercentage,
            ];
        }

        // Get lists of submitted and not submitted tasks
        $submittedTasks = $submissions->map(function ($submission) {
            return [
                'title' => $submission->task->title,
                'section' => $submission->task->section->name,
                'submitted_at' => $submission->submitted_at,
                'score' => $submission->review?->score,
                'max_score' => $submission->task->max_score,
            ];
        })->values()->toArray();

        $notSubmittedTasks = $allTasks->filter(function ($task) use ($submissions) {
            return !$submissions->has($task->id);
        })->map(function ($task) {
            return [
                'title' => $task->title,
                'section' => $task->section->name,
                'max_score' => $task->max_score,
            ];
        })->values()->toArray();

        return [
            'student' => [
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone,
                'church' => $student->church?->name,
                'district' => $student->district?->name,
            ],
            'summary' => [
                'total_tasks' => $allTasks->count(),
                'submitted_count' => $submissions->count(),
                'not_submitted_count' => count($notSubmittedTasks),
                'total_score' => $studentScore,
                'max_score' => $totalMaxScore,
                'percentage' => $percentage,
                'score_out_of_100' => $scoreOutOf100,
                'score_out_of_60' => $scoreOutOf60,
            ],
            'sections' => $sections,
            'submitted_tasks' => $submittedTasks,
            'not_submitted_tasks' => $notSubmittedTasks,
        ];
    }
}
