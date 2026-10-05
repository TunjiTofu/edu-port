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

    // FIX 1: was `return false` with the real logic commented out
    public static function canViewAny(): bool
    {
        return false;
//        $user = Auth::user();
//        return $user && $user->isReviewer();
    }

    // FIX 2: was returning false — hidden from sidebar entirely
    public static function shouldRegisterNavigation(): bool
    {
        return false;
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
                        $submitted = $record->submissions()->count();
                        $total     = Task::where('is_active', 1)->count();
                        return "{$submitted}/{$total}";
                    }),

                Tables\Columns\TextColumn::make('pending_tasks')
                    ->label('Not Submitted')
                    ->alignCenter()->badge()->color('danger')
                    ->getStateUsing(function (User $record): int {
                        $submitted = $record->submissions()->pluck('task_id')->toArray();
                        return Task::where('is_active', 1)->whereNotIn('id', $submitted)->count();
                    }),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total Score')
                    ->alignCenter()->badge()->color('info')
                    ->getStateUsing(function (User $record): string {
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;

                        $totalMaxScore = DB::table('tasks')->where('is_active', 1)
                            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?? 0;

                        return round($studentScore, 1) . '/' . round($totalMaxScore, 1);
                    }),

                Tables\Columns\TextColumn::make('calculated_score_percentage')
                    ->label('Score /100')
                    ->alignCenter()->badge()->sortable()
                    ->getStateUsing(function (User $record): string {
                        if (isset($record->calculated_score_percentage)) {
                            return number_format($record->calculated_score_percentage, 1) . '/100';
                        }
                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')->where('is_active', 1)
                            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?? 0;
                        if ($totalMaxScore == 0) return '0/100';
                        return number_format(($studentScore / $totalMaxScore) * 100, 1) . '/100';
                    })
                    ->color(function (User $record): string {
                        $pct = isset($record->calculated_score_percentage)
                            ? $record->calculated_score_percentage
                            : 0;
                        return match (true) {
                            $pct >= 75 => 'success',
                            $pct >= 50 => 'warning',
                            $pct == 0  => 'gray',
                            default    => 'danger',
                        };
                    }),

                Tables\Columns\TextColumn::make('score_out_of_60')
                    ->label('Score /60')
                    ->alignCenter()->badge()
                    ->getStateUsing(function (User $record): string {
                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')->where('is_active', 1)
                            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?? 0;
                        if ($totalMaxScore == 0) return '0/60';
                        $scoreOutOf60 = (($studentScore / $totalMaxScore) * 100 / 100) * 60;
                        return number_format($scoreOutOf60, 1) . '/60';
                    })
                    ->color(function (User $record): string {
                        $studentScore  = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->sum(DB::raw('CAST(reviews.score AS DECIMAL(10,2))')) ?? 0;
                        $totalMaxScore = DB::table('tasks')->where('is_active', 1)
                            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?? 0;
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
                Tables\Filters\SelectFilter::make('church_id')
                    ->relationship('church', 'name')
                    ->preload(),
                // District filter intentionally omitted —
                // reviewers are already scoped to their own district in getEloquentQuery()
            ])
            ->actions([
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(fn (User $record) => static::exportStudentPdf($record)),
            ])
            // FIX 3: defaultSort('calculated_score_percentage', 'desc') caused a 30-second
            // timeout in the admin panel because the alias isn't available when Filament
            // applies defaultSort before getEloquentQuery() merges it. Use a subquery closure.
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
                        FROM tasks t WHERE t.is_active = 1
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
        $totalMaxScore = DB::table('tasks')
            ->where('is_active', 1)
            ->sum(DB::raw('CAST(max_score AS DECIMAL(10,2))')) ?: 1;

        $query = parent::getEloquentQuery()
            ->where('users.role_id', Constants::STUDENT_ID)
            ->leftJoin('submissions', 'users.id', '=', 'submissions.student_id')
            ->leftJoin('reviews', 'submissions.id', '=', 'reviews.submission_id')
            ->selectRaw("users.*,
                (
                    COALESCE(SUM(CAST(reviews.score AS DECIMAL(10,2))), 0)
                    / {$totalMaxScore} * 100
                ) as calculated_score_percentage")
            ->groupBy('users.id')
            ->with(['church', 'district']);

        // Reviewers only see candidates from their own district
        $reviewer = Auth::user();
        if ($reviewer && $reviewer->district_id) {
            $query->where('users.district_id', $reviewer->district_id);
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

        $allTasks    = Task::where('is_active', 1)->with('section')
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
                'total_tasks'         => $allTasks->count(),
                'submitted_count'     => $submissions->count(),
                'not_submitted_count' => count($notSubmittedTasks),
                'total_score'         => $studentScore,
                'max_score'           => $totalMaxScore,
                'percentage'          => $percentage,
                'score_out_of_100'    => $percentage,
                'score_out_of_60'     => ($percentage / 100) * 60,
            ],
            'sections'            => $sections,
            'submitted_tasks'     => $submittedTasks,
            'not_submitted_tasks' => $notSubmittedTasks,
        ];
    }
}
