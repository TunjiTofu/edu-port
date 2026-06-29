<?php

namespace App\Filament\Resources;

use App\Enums\SubmissionTypes;
use App\Exports\StudentResultsExport;
use App\Filament\Resources\StudentResultsResource\Pages;
use App\Models\Section;
use App\Models\Task;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\Utility\Constants;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StudentResultsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Intending MGs Results';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel      = 'Intending MG Result';
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
                    ->searchable()->sortable()->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('church.name')
                    ->label('Church')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')->searchable()->toggleable(),

                // ── Candidate status badge ──────────────────────────────────
                Tables\Columns\TextColumn::make('candidate_status')
                    ->label('Status')->badge()
                    ->getStateUsing(fn (User $record) => match (true) {
                        $record->disqualified_at !== null      => 'disqualified',
                        $record->program_completed_at !== null => 'graduated',
                        default                                => 'active',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'graduated'    => '🎓 Graduated',
                        'disqualified' => '🚫 Disqualified',
                        default        => '🟢 Active',
                    })
                    ->color(fn ($state) => match ($state) {
                        'graduated'    => 'success',
                        'disqualified' => 'danger',
                        default        => 'info',
                    }),

                Tables\Columns\TextColumn::make('submitted_tasks')
                    ->label('Tasks Submitted')->alignCenter()->badge()->color('success')
                    ->getStateUsing(function (User $record): string {
                        $submitted = $record->submissions()->count();
                        $total     = Task::where('is_active', 1)->count();
                        return "{$submitted}/{$total}";
                    }),

                Tables\Columns\TextColumn::make('pending_tasks')
                    ->label('Not Submitted')->alignCenter()->badge()->color('danger')
                    ->getStateUsing(function (User $record): int {
                        $submitted = $record->submissions()->pluck('task_id')->toArray();
                        return Task::where('is_active', 1)->whereNotIn('id', $submitted)->count();
                    }),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total Score')->alignCenter()->badge()->color('info')
                    ->getStateUsing(function (User $record): string {
                        $studentScore  = static::getStudentScore($record->id);
                        $totalMaxScore = static::getTotalMaxScore();
                        return round($studentScore, 1) . '/' . round($totalMaxScore, 1);
                    }),

                Tables\Columns\TextColumn::make('calculated_score_percentage')
                    ->label('Score /100')->alignCenter()->badge()->sortable()
                    ->getStateUsing(function (User $record): string {
                        if (isset($record->calculated_score_percentage)) {
                            return number_format($record->calculated_score_percentage, 1) . '/100';
                        }
                        $studentScore  = static::getStudentScore($record->id);
                        $totalMaxScore = static::getTotalMaxScore();
                        if ($totalMaxScore == 0) return '0/100';
                        return number_format(($studentScore / $totalMaxScore) * 100, 1) . '/100';
                    })
                    ->color(function (User $record): string {
                        $pct = isset($record->calculated_score_percentage)
                            ? $record->calculated_score_percentage
                            : static::getScorePercentage($record->id);
                        return match (true) {
                            $pct >= 75  => 'success',
                            $pct >= 50  => 'warning',
                            $pct == 0   => 'gray',
                            default     => 'danger',
                        };
                    }),

                Tables\Columns\TextColumn::make('score_out_of_60')
                    ->label('Score /60')->alignCenter()->badge()
                    ->getStateUsing(function (User $record): string {
                        $studentScore  = static::getStudentScore($record->id);
                        $totalMaxScore = static::getTotalMaxScore();
                        if ($totalMaxScore == 0) return '0/60';
                        $scoreOutOf60 = (($studentScore / $totalMaxScore) * 100 / 100) * 60;
                        return number_format($scoreOutOf60, 1) . '/60';
                    })
                    ->color(function (User $record): string {
                        $studentScore  = static::getStudentScore($record->id);
                        $totalMaxScore = static::getTotalMaxScore();
                        if ($totalMaxScore == 0 || $studentScore == 0) return 'gray';
                        $scoreOutOf60 = (($studentScore / $totalMaxScore) * 100 / 100) * 60;
                        return match (true) {
                            $scoreOutOf60 >= 45 => 'success',
                            $scoreOutOf60 >= 30 => 'warning',
                            default             => 'danger',
                        };
                    }),
            ])
            ->filters([
                // ── Registration year ───────────────────────────────────────
                Tables\Filters\SelectFilter::make('year')
                    ->label('Registration Year')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = User::where('role_id', Constants::STUDENT_ID)
                            ->selectRaw('DISTINCT YEAR(created_at) as yr')
                            ->orderByDesc('yr')
                            ->pluck('yr')
                            ->filter()
                            ->mapWithKeys(fn ($y) => [$y => (string) $y])
                            ->toArray();
                        if (! isset($years[$currentYear])) {
                            $years[$currentYear] = (string) $currentYear;
                        }
                        krsort($years);
                        return ['' => 'All Years'] + $years;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereYear('users.created_at', (int) $data['value']);
                    }),

                // ── Training program ────────────────────────────────────────
                Tables\Filters\SelectFilter::make('training_program')
                    ->label('Program')
                    ->options(fn () =>
                    TrainingProgram::orderByDesc('year')->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($p) =>
                        [$p->id => ($p->year ? "[{$p->year}] " : '') . $p->name]
                        )
                    )
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereHas('enrollments', fn ($q) =>
                        $q->where('training_program_id', $data['value'])
                        );
                    }),

                // ── Programme completion status ─────────────────────────────
                Tables\Filters\SelectFilter::make('candidate_status')
                    ->label('Programme Status')
                    ->options([
                        'active'       => '🟢 Active (Still in Programme)',
                        'graduated'    => '🎓 Graduated',
                        'disqualified' => '🚫 Disqualified',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? '') {
                            'graduated'    => $query->whereNotNull('program_completed_at'),
                            'disqualified' => $query->whereNotNull('disqualified_at'),
                            'active'       => $query->whereNull('program_completed_at')
                                ->whereNull('disqualified_at'),
                            default        => $query,
                        };
                    }),

                // ── Existing filters ────────────────────────────────────────
                Tables\Filters\SelectFilter::make('church_id')
                    ->relationship('church', 'name')->preload(),

                Tables\Filters\SelectFilter::make('district_id')
                    ->relationship('district', 'name')->preload(),

                Tables\Filters\Filter::make('has_submissions')
                    ->label('Has Submissions')
                    ->query(fn (Builder $query) => $query->has('submissions'))
                    ->toggle(),
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\Action::make('export_pdf')
                    ->icon('heroicon-o-document-arrow-down')->iconButton()
                    ->tooltip('Export PDF')->color('danger')
                    ->action(fn (User $record) => static::exportStudentPdf($record)),

                Tables\Actions\Action::make('export_excel')
                    ->icon('heroicon-o-table-cells')->iconButton()
                    ->tooltip('Export Excel')->color('success')
                    ->action(fn (User $record) => static::exportStudentExcel($record)),
            ])
            ->headerActions(
                Auth::user()?->isAdmin() ? [
                    // ── All header exports respect the active table filters ──
                    // $livewire->getFilteredTableQuery() returns the query with all
                    // currently-selected filters (year, program, status, etc.) applied.
                    // This means "Export All" only exports the filtered students.

                    Tables\Actions\Action::make('export_all_detailed_pdf')
                        ->label('Export All Detailed (PDF)')
                        ->icon('heroicon-o-document-text')->color('danger')
                        ->action(function ($livewire) {
                            $students = $livewire->getFilteredTableQuery()
                                ->with(['church', 'district', 'submissions.task.section', 'submissions.review'])
                                ->get();
                            return static::exportStudentsDetailedPdf($students, 'all-students-detailed');
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Export Filtered Students — Detailed Report')
                        ->modalDescription('This will generate a detailed PDF for all students matching the current filters. May take 1–3 minutes for large sets.')
                        ->modalSubmitActionLabel('Generate PDF'),

                    Tables\Actions\Action::make('export_top_students_pdf')
                        ->label('Export Top N Students (PDF)')
                        ->icon('heroicon-o-star')->color('success')
                        ->form([
                            Forms\Components\TextInput::make('limit')
                                ->label('Number of Students')->numeric()->required()
                                ->default(20)->minValue(1)->maxValue(500)
                                ->helperText('Top N students by score, from the current filter results.')
                                ->suffix('students'),
                        ])
                        ->action(function (array $data, $livewire) {
                            $students = $livewire->getFilteredTableQuery()
                                ->with(['church', 'district', 'submissions.task.section', 'submissions.review'])
                                ->get();
                            $topStudents = static::sortedByScore($students)->take((int) $data['limit']);
                            return static::exportStudentsDetailedPdf(
                                $topStudents, 'top-' . $data['limit'] . '-students-detailed'
                            );
                        })
                        ->modalHeading('Export Top Students — Detailed Report')
                        ->modalWidth('md'),

                    Tables\Actions\Action::make('export_range_students_pdf')
                        ->label('Export Student Range (PDF)')
                        ->icon('heroicon-o-queue-list')->color('info')
                        ->form([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('start')
                                    ->label('Start Position')->numeric()->required()
                                    ->default(1)->minValue(1)
                                    ->helperText('Starting rank (1 = highest score)'),
                                Forms\Components\TextInput::make('end')
                                    ->label('End Position')->numeric()->required()
                                    ->default(20)->minValue(1)
                                    ->helperText('Ending rank'),
                            ]),
                        ])
                        ->action(function (array $data, $livewire) {
                            $students = $livewire->getFilteredTableQuery()
                                ->with(['church', 'district', 'submissions.task.section', 'submissions.review'])
                                ->get();
                            $rangeStudents = static::sortedByScore($students)
                                ->slice((int) $data['start'] - 1, (int) $data['end'] - (int) $data['start'] + 1);
                            return static::exportStudentsDetailedPdf(
                                $rangeStudents,
                                'students-' . $data['start'] . '-to-' . $data['end'] . '-detailed'
                            );
                        })
                        ->modalHeading('Export Student Range — Detailed Report')
                        ->modalWidth('md'),

                    Tables\Actions\Action::make('export_all_pdf')
                        ->label('Export All Summary (PDF)')
                        ->icon('heroicon-o-document-arrow-down')->color('warning')
                        ->action(function ($livewire) {
                            $students = $livewire->getFilteredTableQuery()
                                ->with(['church', 'district'])
                                ->get();
                            $allData = static::sortedByScore(
                                $students->map(fn ($s) => static::getStudentDetailedData($s))
                            );
                            $pdf = Pdf::loadView('pdf.all-students-results', ['students' => $allData])
                                ->setPaper('a4', 'portrait')
                                ->setOption('isHtml5ParserEnabled', true)
                                ->setOption('isRemoteEnabled', false);
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'students-summary-' . now()->format('Y-m-d') . '.pdf'
                            );
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Export Filtered Students — Summary')
                        ->modalDescription('Exports a summary table for all students matching the current filters.')
                        ->modalSubmitActionLabel('Generate PDF'),

                    Tables\Actions\Action::make('export_all_excel')
                        ->label('Export All (Excel)')
                        ->icon('heroicon-o-table-cells')->color('success')
                        ->action(function ($livewire) {
                            ini_set('memory_limit', '512M');
                            $students = $livewire->getFilteredTableQuery()
                                ->with(['church', 'district', 'submissions.task.section', 'submissions.review'])
                                ->get();
                            $allData = static::sortedByScore(
                                $students->map(fn ($s) => static::getStudentDetailedData($s))
                            )->values()->toArray();
                            return Excel::download(
                                new StudentResultsExport($allData),
                                'students-results-' . now()->format('Y-m-d') . '.xlsx'
                            );
                        }),
                ] : []
            )
            ->defaultSort('calculated_score_percentage', 'desc');
    }

    // ── Score helpers ─────────────────────────────────────────────────────────

    private static function getStudentScore(int $studentId): float
    {
        return DB::table('submissions')
            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
            ->where('submissions.student_id', $studentId)
            ->whereNotNull('reviews.score')
            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
    }

    private static function getTotalMaxScore(): float
    {
        return DB::table('tasks')->where('is_active', 1)
            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?? 0;
    }

    private static function getScorePercentage(int $studentId): float
    {
        $studentScore  = static::getStudentScore($studentId);
        $totalMaxScore = static::getTotalMaxScore();
        return $totalMaxScore > 0 ? ($studentScore / $totalMaxScore) * 100 : 0;
    }

    /**
     * Sort a collection of students OR student data arrays by score descending.
     * Works for both User collections and the mapped data arrays used by exports.
     */
    private static function sortedByScore($collection): \Illuminate\Support\Collection
    {
        return collect($collection)->sortByDesc(function ($item) {
            if (is_array($item)) {
                return $item['summary']['score_out_of_100'] ?? 0;
            }
            return isset($item->calculated_score_percentage)
                ? $item->calculated_score_percentage
                : static::getScorePercentage($item->id);
        })->values();
    }

    /**
     * Shared detailed PDF export — used by all three detailed PDF actions.
     * Accepts a Collection of User models, maps them to data arrays, sorts by
     * score, and streams the PDF.
     */
    private static function exportStudentsDetailedPdf($students, string $filenamePrefix): mixed
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $allData = static::sortedByScore(
            collect($students)->map(fn ($s) => static::getStudentDetailedData($s))
        )->values();

        $pdf = Pdf::loadView('pdf.all-students-detailed', ['students' => $allData])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filenamePrefix . '-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    // ── Per-student exports (unchanged from original) ─────────────────────────

    protected static function exportStudentPdf(User $student): mixed
    {
        $data = static::getStudentDetailedData($student);
        $pdf  = Pdf::loadView('pdf.student-result', $data);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'student-result-' . $student->id . '-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    protected static function exportStudentExcel(User $student): mixed
    {
        $data = static::getStudentDetailedData($student);
        return Excel::download(
            new StudentResultsExport([$data]),
            'student-result-' . $student->id . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public static function getRelations(): array { return []; }

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
            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?: 1;

        return parent::getEloquentQuery()
            ->where('users.role_id', Constants::STUDENT_ID)
            ->leftJoin('submissions', 'users.id', '=', 'submissions.student_id')
            ->leftJoin('reviews', 'submissions.id', '=', 'reviews.submission_id')
            ->selectRaw("users.*,
                (COALESCE(SUM(CAST(reviews.score AS DECIMAL(10,2))), 0) / {$totalMaxScore} * 100)
                    as calculated_score_percentage")
            ->groupBy('users.id')
            ->with(['church', 'district']);
    }

    protected static function getStudentDetailedData(User $student): array
    {
        $student = User::with([
            'church', 'district',
            'submissions.task.section',
            'submissions.review',
        ])->find($student->id);

        $allTasks   = Task::where('is_active', 1)->with('section')
            ->orderBy('section_id')->orderBy('order_index')->get();
        $submissions = $student->submissions()->with(['task.section', 'review'])->get()->keyBy('task_id');

        $studentScore  = $submissions->sum(fn ($sub) => $sub->review?->score ?? 0);
        $totalMaxScore = $allTasks->sum('max_score');
        $percentage    = $totalMaxScore > 0 ? ($studentScore / $totalMaxScore) * 100 : 0;

        $sections = [];
        foreach ($allTasks->groupBy('section_id') as $sectionId => $sectionTasks) {
            $section             = $sectionTasks->first()->section;
            $sectionMaxScore     = $sectionTasks->sum('max_score');
            $sectionStudentScore = 0;
            $tasksData           = [];

            foreach ($sectionTasks as $task) {
                $submission   = $submissions->get($task->id);
                $score        = $submission?->review?->score ?? null;
                $comments     = $submission?->review?->comments ?? null;
                if ($score !== null) $sectionStudentScore += $score;
                $tasksData[] = [
                    'title'        => $task->title,
                    'max_score'    => $task->max_score,
                    'score'        => $score,
                    'comments'     => $comments,
                    'status'       => $submission ? 'Submitted' : 'Not Submitted',
                    'submitted_at' => $submission?->submitted_at,
                ];
            }

            $sections[] = [
                'name'       => $section->name,
                'tasks'      => $tasksData,
                'total_score' => $sectionStudentScore,
                'max_score'  => $sectionMaxScore,
                'percentage' => $sectionMaxScore > 0 ? ($sectionStudentScore / $sectionMaxScore) * 100 : 0,
            ];
        }

        $notSubmittedTasks = $allTasks->filter(fn ($t) => ! $submissions->has($t->id))
            ->map(fn ($t) => ['title' => $t->title, 'section' => $t->section->name, 'max_score' => $t->max_score])
            ->values()->toArray();

        $submittedTasks = $submissions->map(fn ($s) => [
            'title'        => $s->task->title,
            'section'      => $s->task->section->name,
            'submitted_at' => $s->submitted_at,
            'score'        => $s->review?->score,
            'max_score'    => $s->task->max_score,
        ])->values()->toArray();

        return [
            'student' => [
                'name'     => $student->name,
                'email'    => $student->email,
                'phone'    => $student->phone,
                'church'   => $student->church?->name,
                'district' => $student->district?->name,
            ],
            'summary' => [
                'total_tasks'       => $allTasks->count(),
                'submitted_count'   => $submissions->count(),
                'not_submitted_count' => count($notSubmittedTasks),
                'total_score'       => $studentScore,
                'max_score'         => $totalMaxScore,
                'percentage'        => $percentage,
                'score_out_of_100'  => $percentage,
                'score_out_of_60'   => ($percentage / 100) * 60,
            ],
            'sections'           => $sections,
            'submitted_tasks'    => $submittedTasks,
            'not_submitted_tasks' => $notSubmittedTasks,
        ];
    }
}
