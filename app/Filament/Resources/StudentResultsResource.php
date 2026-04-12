<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResultsResource\Pages;
use App\Models\User;
use App\Models\Task;
use App\Models\Section;
use App\Services\Utility\Constants;
use App\Enums\SubmissionTypes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentResultsExport;

class StudentResultsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Intending MGs Results';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Intending MG Result';

    protected static ?string $pluralModelLabel = 'Intending MGs Results';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && ($user->isAdmin() || $user->isObserver());
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

                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function (User $record) {
                        return static::exportStudentExcel($record);
                    }),
            ])
            ->headerActions(
                Auth::user()?->isAdmin() ? [
                    Tables\Actions\Action::make('export_all_detailed_pdf')
                        ->label('Export All Detailed (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->color('danger')
                        ->action(function () {
                            return static::exportAllStudentsDetailedPdf();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Export All Intending MGs - Detailed Report')
                        ->modalDescription('This will generate a comprehensive PDF with individual detailed pages for each student. For 86 students, this will create ~87 pages and may take 1-3 minutes. Please be patient.')
                        ->modalSubmitActionLabel('Generate PDF'),

                    Tables\Actions\Action::make('export_top_students_pdf')
                        ->label('Export Top N Students (PDF)')
                        ->icon('heroicon-o-star')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('limit')
                                ->label('Number of Students')
                                ->numeric()
                                ->required()
                                ->default(20)
                                ->minValue(1)
                                ->maxValue(100)
                                ->helperText('Enter the number of top students to export (sorted by highest score)')
                                ->suffix('students'),
                        ])
                        ->action(function (array $data) {
                            return static::exportTopStudentsDetailedPdf($data['limit']);
                        })
                        ->modalHeading('Export Top Students - Detailed Report')
                        ->modalDescription('Enter how many top students (by score) you want to export. Recommended: 10-30 for faster generation.')
                        ->modalSubmitActionLabel('Generate PDF')
                        ->modalWidth('md'),

                    Tables\Actions\Action::make('export_range_students_pdf')
                        ->label('Export Student Range (PDF)')
                        ->icon('heroicon-o-queue-list')
                        ->color('info')
                        ->form([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('start')
                                        ->label('Start Position')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(1)
                                        ->helperText('Starting position (1 = highest score)'),
                                    Forms\Components\TextInput::make('end')
                                        ->label('End Position')
                                        ->numeric()
                                        ->required()
                                        ->default(20)
                                        ->minValue(1)
                                        ->helperText('Ending position (e.g., 20 = 20th student)'),
                                ]),
                        ])
                        ->action(function (array $data) {
                            return static::exportRangeStudentsDetailedPdf($data['start'], $data['end']);
                        })
                        ->modalHeading('Export Student Range - Detailed Report')
                        ->modalDescription('Export students from position X to position Y (sorted by score). Example: Start=21, End=40 exports students ranked 21st to 40th.')
                        ->modalSubmitActionLabel('Generate PDF')
                        ->modalWidth('md'),

                    Tables\Actions\Action::make('export_all_pdf')
                        ->label('Export All Summary (PDF)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('warning')
                        ->action(function () {
                            return static::exportAllStudentsPdf();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Export All Intending MGs - Summary Only')
                        ->modalDescription('This will generate a summary table of all Intending MGs (much faster than detailed export).')
                        ->modalSubmitActionLabel('Generate PDF'),

                    Tables\Actions\Action::make('export_all_excel')
                        ->label('Export All (Excel)')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function () {
                            return static::exportAllStudentsExcel();
                        }),
                ] : []
            )
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

    protected static function exportStudentExcel(User $student)
    {
        $data = static::getStudentDetailedData($student);

        return Excel::download(
            new StudentResultsExport([$data]),
            'student-result-' . $student->id . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    protected static function exportAllStudentsPdf()
    {
        // Increase memory and time limits
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $students = User::where('role_id', Constants::STUDENT_ID)
            ->with(['church', 'district'])
            ->get();

        $allData = $students->map(function ($student) {
            $student->load(['submissions.task.section', 'submissions.review']);
            return static::getStudentDetailedData($student);
        });

        // Sort by score descending (highest to lowest)
        $allData = $allData->sortByDesc(function ($studentData) {
            return $studentData['summary']['score_out_of_100'];
        })->values();

        $pdf = Pdf::loadView('pdf.all-students-results', ['students' => $allData])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'all-students-summary-' . now()->format('Y-m-d') . '.pdf');
    }

    protected static function exportAllStudentsDetailedPdf()
    {
        // Increase memory and time limits for large exports
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300'); // 5 minutes

        $students = User::where('role_id', Constants::STUDENT_ID)
            ->with(['church', 'district'])
            ->get();

        // Process in smaller chunks to avoid memory issues
        $allData = $students->map(function ($student) {
            // Load submissions separately to avoid memory bloat
            $student->load(['submissions.task.section', 'submissions.review']);
            return static::getStudentDetailedData($student);
        });

        // Sort by score descending (highest to lowest)
        $allData = $allData->sortByDesc(function ($studentData) {
            return $studentData['summary']['score_out_of_100'];
        })->values();

        // Use setPaper and setOption to optimize PDF generation
        $pdf = Pdf::loadView('pdf.all-students-detailed', ['students' => $allData])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('chroot', [public_path()]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'all-students-detailed-' . now()->format('Y-m-d') . '.pdf');
    }

    protected static function exportTopStudentsDetailedPdf(int $limit = 20)
    {
        // Increase memory and time limits
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180'); // 3 minutes

        $students = User::where('role_id', Constants::STUDENT_ID)
            ->with(['church', 'district'])
            ->get();

        // Get all student data
        $allData = $students->map(function ($student) {
            $student->load(['submissions.task.section', 'submissions.review']);
            return static::getStudentDetailedData($student);
        });

        // Sort by score descending and take only top N
        $topStudents = $allData->sortByDesc(function ($studentData) {
            return $studentData['summary']['score_out_of_100'];
        })->take($limit)->values();

        $pdf = Pdf::loadView('pdf.all-students-detailed', ['students' => $topStudents])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        return response()->streamDownload(function () use ($pdf, $limit) {
            echo $pdf->output();
        }, 'top-' . $limit . '-students-detailed-' . now()->format('Y-m-d') . '.pdf');
    }

    protected static function exportRangeStudentsDetailedPdf(int $start = 1, int $end = 20)
    {
        // Increase memory and time limits
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180'); // 3 minutes

        $students = User::where('role_id', Constants::STUDENT_ID)
            ->with(['church', 'district'])
            ->get();

        // Get all student data
        $allData = $students->map(function ($student) {
            $student->load(['submissions.task.section', 'submissions.review']);
            return static::getStudentDetailedData($student);
        });

        // Sort by score descending
        $sortedStudents = $allData->sortByDesc(function ($studentData) {
            return $studentData['summary']['score_out_of_100'];
        })->values();

        // Get the range (convert to 0-indexed)
        $rangeStudents = $sortedStudents->slice($start - 1, $end - $start + 1)->values();

        $pdf = Pdf::loadView('pdf.all-students-detailed', ['students' => $rangeStudents])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        return response()->streamDownload(function () use ($pdf, $start, $end) {
            echo $pdf->output();
        }, 'students-' . $start . '-to-' . $end . '-detailed-' . now()->format('Y-m-d') . '.pdf');
    }

    protected static function exportAllStudentsExcel()
    {
        $students = User::where('role_id', Constants::STUDENT_ID)
            ->with(['church', 'district', 'submissions.task.section', 'submissions.review'])
            ->get();

        $allData = $students->map(function ($student) {
            return static::getStudentDetailedData($student);
        });

        // Sort by score descending (highest to lowest)
        $allData = $allData->sortByDesc(function ($studentData) {
            return $studentData['summary']['score_out_of_100'];
        })->values()->toArray();

        return Excel::download(
            new StudentResultsExport($allData),
            'all-students-results-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    protected static function getStudentDetailedData(User $student): array
    {
        // Reload student with fresh relationships (in case query was modified)
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
