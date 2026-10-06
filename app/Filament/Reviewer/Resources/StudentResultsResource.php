<?php

namespace App\Filament\Reviewer\Resources;

use App\Filament\Reviewer\Resources\StudentResultsResource\Pages;
use App\Models\User;
use App\Models\Task;
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

    protected static ?string $navigationIcon   = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel  = 'Intending MGs Result';
    protected static ?string $navigationGroup  = 'Reports';
    protected static ?int    $navigationSort   = 1;
    protected static ?string $modelLabel       = 'Intending MG Result';
    protected static ?string $pluralModelLabel = 'Intending MGs Results';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->isReviewer();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
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
                    ->label('Candidate Name')
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
                    ->alignCenter()->badge()->color('success')
                    ->getStateUsing(function (User $record): string {
                        $programId = $record->enrollments()->latest('enrolled_at')->value('training_program_id');
                        $submitted = $record->submissions()->count();
                        $total     = $programId
                            ? Task::where('is_active', 1)
                                ->whereHas('section', fn ($q) => $q->where('training_program_id', $programId))
                                ->count()
                            : 0;
                        return "{$submitted}/{$total}";
                    }),

                Tables\Columns\TextColumn::make('pending_tasks')
                    ->label('Not Submitted')
                    ->alignCenter()->badge()->color('danger')
                    ->getStateUsing(function (User $record): int {
                        $programId    = $record->enrollments()->latest('enrolled_at')->value('training_program_id');
                        $submittedIds = $record->submissions()->pluck('task_id')->toArray();
                        return $programId
                            ? Task::where('is_active', 1)
                                ->whereHas('section', fn ($q) => $q->where('training_program_id', $programId))
                                ->whereNotIn('id', $submittedIds)
                                ->count()
                            : 0;
                    }),

                // ── Portfolio score ────────────────────────────────────────
                Tables\Columns\TextColumn::make('total_score')
                    ->label('Portfolio Raw')
                    ->alignCenter()->badge()->color('info')
                    ->getStateUsing(function (User $record): string {
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;

                        $totalMaxScore = DB::table('tasks')
                            ->join('sections', 'tasks.section_id', '=', 'sections.id')
                            ->join('program_enrollments', 'sections.training_program_id', '=', 'program_enrollments.training_program_id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->where('tasks.is_active', 1)
                            ->sum(DB::raw('CAST(tasks.max_score AS DECIMAL(10,2))')) ?? 0;

                        return round($studentScore, 1) . '/' . round($totalMaxScore, 1);
                    }),

                Tables\Columns\TextColumn::make('calculated_score_percentage')
                    ->label('Portfolio %')
                    ->alignCenter()->badge()->sortable()
                    ->getStateUsing(function (User $record): string {
                        if (isset($record->calculated_score_percentage)) {
                            return number_format($record->calculated_score_percentage, 1) . '%';
                        }
                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')
                            ->join('sections', 'tasks.section_id', '=', 'sections.id')
                            ->join('program_enrollments', 'sections.training_program_id', '=', 'program_enrollments.training_program_id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->where('tasks.is_active', 1)
                            ->sum(DB::raw('CAST(tasks.max_score AS DECIMAL(10,2))')) ?? 0;
                        if ($totalMaxScore == 0) return '0%';
                        return number_format(($studentScore / $totalMaxScore) * 100, 1) . '%';
                    })
                    ->color(function (User $record): string {
                        // Colour vs portfolio_cutoff from the student's enrolled programme
                        $programCutoff = DB::table('program_enrollments')
                            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->latest('program_enrollments.enrolled_at')
                            ->value('training_programs.portfolio_cutoff');

                        $pct = isset($record->calculated_score_percentage)
                            ? (float) $record->calculated_score_percentage
                            : 0;

                        if ($programCutoff !== null) {
                            return $pct >= (float) $programCutoff ? 'success' : 'danger';
                        }

                        return match (true) {
                            $pct >= 75 => 'success',
                            $pct >= 50 => 'warning',
                            $pct == 0  => 'gray',
                            default    => 'danger',
                        };
                    }),

                Tables\Columns\TextColumn::make('score_out_of_weight')
                    ->label('Portfolio /wt')
                    ->alignCenter()->badge()
                    ->getStateUsing(function (User $record): string {
                        // Fetch weight from enrolled programme
                        $portfolioWeight = (float) (DB::table('program_enrollments')
                            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->latest('program_enrollments.enrolled_at')
                            ->value('training_programs.portfolio_weight') ?? 60);

                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')
                            ->join('sections', 'tasks.section_id', '=', 'sections.id')
                            ->join('program_enrollments', 'sections.training_program_id', '=', 'program_enrollments.training_program_id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->where('tasks.is_active', 1)
                            ->sum(DB::raw('CAST(tasks.max_score AS DECIMAL(10,2))')) ?? 0;

                        if ($totalMaxScore == 0) return "0/{$portfolioWeight}";
                        $scaled = round(($studentScore / $totalMaxScore) * $portfolioWeight, 1);
                        return "{$scaled}/{$portfolioWeight}";
                    })
                    ->color(function (User $record): string {
                        $portfolioWeight = (float) (DB::table('program_enrollments')
                            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->latest('program_enrollments.enrolled_at')
                            ->value('training_programs.portfolio_weight') ?? 60);

                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')
                            ->join('sections', 'tasks.section_id', '=', 'sections.id')
                            ->join('program_enrollments', 'sections.training_program_id', '=', 'program_enrollments.training_program_id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->where('tasks.is_active', 1)
                            ->sum(DB::raw('CAST(tasks.max_score AS DECIMAL(10,2))')) ?? 0;

                        if ($totalMaxScore == 0 || $studentScore == 0) return 'gray';
                        $scaled = ($studentScore / $totalMaxScore) * $portfolioWeight;
                        $threshold75 = $portfolioWeight * 0.75;
                        $threshold50 = $portfolioWeight * 0.50;
                        return match (true) {
                            $scaled >= $threshold75 => 'success',
                            $scaled >= $threshold50 => 'warning',
                            default                 => 'danger',
                        };
                    }),

                // ── Interview score ────────────────────────────────────────
                Tables\Columns\TextColumn::make('interview_score_display')
                    ->label('Interview /wt')
                    ->alignCenter()->badge()
                    ->getStateUsing(function (User $record): string {
                        // Only visible when the admin has published the score
                        if (! $record->interview_score_published) {
                            return 'Pending';
                        }
                        if ($record->interview_score === null) {
                            return 'Not set';
                        }

                        $interviewWeight = (float) (DB::table('program_enrollments')
                            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->latest('program_enrollments.enrolled_at')
                            ->value('training_programs.interview_weight') ?? 40);

                        $contribution = round((float) $record->interview_score * ($interviewWeight / 100), 1);
                        return "{$contribution}/{$interviewWeight}";
                    })
                    ->color(function (User $record): string {
                        if (! $record->interview_score_published || $record->interview_score === null) {
                            return 'gray';
                        }
                        $interviewWeight = (float) (DB::table('program_enrollments')
                            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->latest('program_enrollments.enrolled_at')
                            ->value('training_programs.interview_weight') ?? 40);

                        $contribution = (float) $record->interview_score * ($interviewWeight / 100);
                        return match (true) {
                            $contribution >= $interviewWeight * 0.75 => 'success',
                            $contribution >= $interviewWeight * 0.50 => 'warning',
                            default                                   => 'danger',
                        };
                    }),

                // ── Combined total /100 ────────────────────────────────────
                Tables\Columns\TextColumn::make('total_combined_score')
                    ->label('Total /100')
                    ->alignCenter()->badge()
                    ->getStateUsing(function (User $record): string {
                        if (! $record->interview_score_published || $record->interview_score === null) {
                            return 'Pending';
                        }

                        $config = DB::table('program_enrollments')
                            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->latest('program_enrollments.enrolled_at')
                            ->select(['training_programs.portfolio_weight', 'training_programs.interview_weight'])
                            ->first();

                        $portfolioWeight = (float) ($config?->portfolio_weight ?? 60);
                        $interviewWeight = (float) ($config?->interview_weight ?? 40);

                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')
                            ->join('sections', 'tasks.section_id', '=', 'sections.id')
                            ->join('program_enrollments', 'sections.training_program_id', '=', 'program_enrollments.training_program_id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->where('tasks.is_active', 1)
                            ->sum(DB::raw('CAST(tasks.max_score AS DECIMAL(10,2))')) ?? 0;

                        $portfolioPct          = $totalMaxScore > 0 ? ($studentScore / $totalMaxScore) * 100 : 0;
                        $portfolioContribution = $portfolioPct * ($portfolioWeight / 100);
                        $interviewContribution = (float) $record->interview_score * ($interviewWeight / 100);
                        $total = round($portfolioContribution + $interviewContribution, 1);

                        return "{$total}/100";
                    })
                    ->color(function (User $record): string {
                        if (! $record->interview_score_published || $record->interview_score === null) {
                            return 'gray';
                        }

                        $config = DB::table('program_enrollments')
                            ->join('training_programs', 'program_enrollments.training_program_id', '=', 'training_programs.id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->latest('program_enrollments.enrolled_at')
                            ->select([
                                'training_programs.portfolio_weight',
                                'training_programs.interview_weight',
                                'training_programs.total_cutoff',
                            ])
                            ->first();

                        $portfolioWeight = (float) ($config?->portfolio_weight ?? 60);
                        $interviewWeight = (float) ($config?->interview_weight ?? 40);
                        $totalCutoff     = $config?->total_cutoff !== null ? (float) $config->total_cutoff : null;

                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')
                            ->join('sections', 'tasks.section_id', '=', 'sections.id')
                            ->join('program_enrollments', 'sections.training_program_id', '=', 'program_enrollments.training_program_id')
                            ->where('program_enrollments.student_id', $record->id)
                            ->whereNull('program_enrollments.deleted_at')
                            ->where('tasks.is_active', 1)
                            ->sum(DB::raw('CAST(tasks.max_score AS DECIMAL(10,2))')) ?? 0;

                        $portfolioPct          = $totalMaxScore > 0 ? ($studentScore / $totalMaxScore) * 100 : 0;
                        $portfolioContribution = $portfolioPct * ($portfolioWeight / 100);
                        $interviewContribution = (float) $record->interview_score * ($interviewWeight / 100);
                        $total = $portfolioContribution + $interviewContribution;

                        if ($totalCutoff !== null) {
                            return $total >= $totalCutoff ? 'success' : 'danger';
                        }

                        return match (true) {
                            $total >= 75 => 'success',
                            $total >= 50 => 'warning',
                            default      => 'danger',
                        };
                    }),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(fn (User $record) => static::exportStudentPdf($record)),
            ])
            ->defaultSort(function ($query) {
                $query->orderByRaw("(
                    COALESCE((
                        SELECT SUM(CAST(r.score AS DECIMAL(10,2)))
                        FROM submissions s
                        JOIN reviews r ON s.id = r.submission_id
                        WHERE s.student_id = users.id AND r.score IS NOT NULL
                    ), 0)
                    /
                    NULLIF((
                        SELECT SUM(CAST(t.max_score AS DECIMAL(10,2)))
                        FROM tasks t
                        INNER JOIN sections sec ON t.section_id = sec.id
                        INNER JOIN program_enrollments pe ON sec.training_program_id = pe.training_program_id
                        WHERE pe.student_id = users.id
                          AND pe.deleted_at IS NULL
                          AND t.is_active = 1
                    ), 0)
                    * 100
                ) DESC");
            });
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
        $reviewer = Auth::user();

        $query = parent::getEloquentQuery()
            ->where('users.role_id', Constants::STUDENT_ID)
            ->whereNull('users.program_completed_at')
            ->whereNull('users.disqualified_at')
            ->leftJoin('submissions', 'users.id', '=', 'submissions.student_id')
            ->leftJoin('reviews', 'submissions.id', '=', 'reviews.submission_id')
            ->selectRaw("users.*,
                (
                    COALESCE(SUM(CAST(reviews.score AS DECIMAL(10,2))), 0)
                    /
                    NULLIF((
                        SELECT SUM(CAST(t.max_score AS DECIMAL(10,2)))
                        FROM tasks t
                        INNER JOIN sections s ON t.section_id = s.id
                        INNER JOIN program_enrollments pe
                            ON s.training_program_id = pe.training_program_id
                        WHERE pe.student_id = users.id
                          AND pe.deleted_at IS NULL
                          AND t.is_active = 1
                          AND t.deleted_at IS NULL
                    ), 0)
                    * 100
                ) as calculated_score_percentage")
            ->groupBy('users.id')
            ->with(['church', 'district']);

        if ($reviewer?->district_id) {
            $query->where('users.district_id', $reviewer->district_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    protected static function exportStudentPdf(User $student): mixed
    {
        $data = static::getStudentDetailedData($student);
        $pdf  = Pdf::loadView('pdf.student-result', $data);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'student-result-' . $student->id . '-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    protected static function getStudentDetailedData(User $student): array
    {
        $student = User::with(['church', 'district', 'submissions.task.section', 'submissions.review'])
            ->find($student->id);

        $programId = $student->enrollments()->latest('enrolled_at')->value('training_program_id');

        // Load programme scoring config
        $programConfig = $programId
            ? DB::table('training_programs')->where('id', $programId)
                ->select(['portfolio_weight', 'interview_weight', 'portfolio_cutoff', 'total_cutoff'])
                ->first()
            : null;

        $portfolioWeight = (float) ($programConfig?->portfolio_weight ?? 60);
        $interviewWeight = (float) ($programConfig?->interview_weight ?? 40);
        $portfolioCutoff = $programConfig?->portfolio_cutoff !== null ? (float) $programConfig->portfolio_cutoff : null;
        $totalCutoff     = $programConfig?->total_cutoff     !== null ? (float) $programConfig->total_cutoff     : null;

        $allTasks = $programId
            ? Task::where('is_active', 1)
                ->whereHas('section', fn ($q) => $q->where('training_program_id', $programId))
                ->with('section')
                ->orderBy('section_id')->orderBy('order_index')
                ->get()
            : collect();

        $submissions = $student->submissions()->with(['task.section', 'review'])->get()->keyBy('task_id');

        $studentScore  = $submissions->sum(fn ($sub) => $sub->review?->score ?? 0);
        $totalMaxScore = $allTasks->sum('max_score');
        $percentage    = $totalMaxScore > 0 ? ($studentScore / $totalMaxScore) * 100 : 0;

        // Interview score (only if published)
        $interviewScoreRaw     = $student->interview_score_published ? $student->interview_score : null;
        $interviewContribution = $interviewScoreRaw !== null
            ? round((float) $interviewScoreRaw * ($interviewWeight / 100), 1)
            : null;
        $portfolioContribution = round($percentage * ($portfolioWeight / 100), 1);
        $totalScore            = $interviewContribution !== null
            ? round($portfolioContribution + $interviewContribution, 1)
            : null;

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
                'name'        => $section->name,
                'tasks'       => $tasksData,
                'total_score' => $sectionStudentScore,
                'max_score'   => $sectionMaxScore,
                'percentage'  => $sectionMaxScore > 0 ? ($sectionStudentScore / $sectionMaxScore) * 100 : 0,
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
                'total_tasks'              => $allTasks->count(),
                'submitted_count'          => $submissions->count(),
                'not_submitted_count'      => count($notSubmittedTasks),
                'total_score'              => $studentScore,
                'max_score'                => $totalMaxScore,
                'percentage'               => $percentage,
                'portfolio_weight'         => $portfolioWeight,
                'interview_weight'         => $interviewWeight,
                'portfolio_cutoff'         => $portfolioCutoff,
                'total_cutoff'             => $totalCutoff,
                'score_out_of_100'         => $percentage,
                'portfolio_contribution'   => $portfolioContribution,
                'interview_score_raw'      => $interviewScoreRaw,
                'interview_contribution'   => $interviewContribution,
                'total_combined'           => $totalScore,
                'portfolio_qualifies'      => $portfolioCutoff !== null ? $percentage >= $portfolioCutoff : null,
                'investiture_qualifies'    => ($totalCutoff !== null && $totalScore !== null) ? $totalScore >= $totalCutoff : null,
            ],
            'sections'            => $sections,
            'submitted_tasks'     => $submittedTasks,
            'not_submitted_tasks' => $notSubmittedTasks,
        ];
    }
}
