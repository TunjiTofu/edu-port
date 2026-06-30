<?php

namespace App\Filament\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Resources\StudentSubmissionsResource\Pages;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\Submission;
use App\Services\Utility\Constants;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentSubmissionsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Student Submissions';
    protected static ?string $navigationGroup = 'Review Management';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $modelLabel      = 'Student Submission';
    protected static ?string $pluralModelLabel = 'Student Submissions';

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled(),
        ]);
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

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('church.name')
                    ->label('Church')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Candidate status badge ──────────────────────────────────
                Tables\Columns\TextColumn::make('candidate_status')
                    ->label('Status')
                    ->badge()
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

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Submissions')
                    ->counts('submissions')
                    ->alignCenter()->badge()->color('info')->sortable(),

                Tables\Columns\TextColumn::make('completed_submissions_count')
                    ->label('Completed')
                    ->counts([
                        'submissions' => fn (Builder $query) =>
                        $query->where('status', SubmissionTypes::COMPLETED->value),
                    ])
                    ->alignCenter()->badge()->color('success')->sortable(),

                Tables\Columns\TextColumn::make('pending_submissions_count')
                    ->label('Pending')
                    ->counts([
                        'submissions' => fn (Builder $query) =>
                        $query->whereIn('status', [
                            SubmissionTypes::PENDING_REVIEW->value,
                            SubmissionTypes::UNDER_REVIEW->value,
                        ]),
                    ])
                    ->alignCenter()->badge()->color('warning')->sortable(),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total Score')
                    ->alignCenter()->badge()
                    ->getStateUsing(function (User $record): string {
                        $studentScore = static::getStudentScore($record->id);
                        $totalMaxScore = static::getTotalMaxScore();
                        return round($studentScore, 1) . '/' . round($totalMaxScore, 1);
                    })
                    ->color(function (User $record): string {
                        $studentScore  = static::getStudentScore($record->id);
                        $totalMaxScore = static::getTotalMaxScore();
                        if ($totalMaxScore == 0) return 'gray';
                        $pct = ($studentScore / $totalMaxScore) * 100;
                        return match (true) {
                            $pct >= 75  => 'success',
                            $pct >= 50  => 'warning',
                            $studentScore == 0 => 'gray',
                            default     => 'danger',
                        };
                    })
                    ->tooltip(function (User $record): string {
                        $totalMaxScore = static::getTotalMaxScore();
                        if ($totalMaxScore == 0) return 'No tasks available';
                        $pct = round((static::getStudentScore($record->id) / $totalMaxScore) * 100, 1);
                        return "{$pct}% of total possible score";
                    }),

                Tables\Columns\TextColumn::make('average_score')
                    ->label('Avg Score')
                    ->alignCenter()->badge()
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

                // ── Candidate programme status ──────────────────────────────
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

                Tables\Filters\Filter::make('has_pending')
                    ->label('Has Pending Submissions')
                    ->query(fn (Builder $query) =>
                    $query->whereHas('submissions', fn ($q) =>
                    $q->whereIn('status', [
                        SubmissionTypes::PENDING_REVIEW->value,
                        SubmissionTypes::UNDER_REVIEW->value,
                    ])
                    )
                    )
                    ->toggle(),
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\Action::make('view_submissions')
                    ->icon('heroicon-o-document-text')
                    ->iconButton()
                    ->tooltip('View Submissions')
                    ->color('primary')
                    ->url(fn (User $record): string =>
                    static::getUrl('submissions', ['record' => $record->id])
                    ),
            ])
            ->defaultSort('submissions_count', 'desc');
    }

    // ── Score helpers (DRY — avoids repeating DB queries per column) ────────

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

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'       => Pages\ListStudentSubmissions::route('/'),
            'submissions' => Pages\ManageStudentSubmissions::route('/{record}/submissions'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role_id', Constants::STUDENT_ID)
            ->withCount([
                'submissions',
                'submissions as completed_submissions_count' => fn ($query) =>
                $query->where('status', SubmissionTypes::COMPLETED->value),
                'submissions as pending_submissions_count' => fn ($query) =>
                $query->whereIn('status', [
                    SubmissionTypes::PENDING_REVIEW->value,
                    SubmissionTypes::UNDER_REVIEW->value,
                ]),
            ]);
    }
}
