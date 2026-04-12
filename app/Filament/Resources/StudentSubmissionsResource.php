<?php

namespace App\Filament\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Resources\StudentSubmissionsResource\Pages;
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

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Student Submissions';

    protected static ?string $navigationGroup = 'Review Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Student Submission';

    protected static ?string $pluralModelLabel = 'Student Submissions';

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->disabled(),
            ]);
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

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('church.name')
                    ->label('Church')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Submissions')
                    ->counts('submissions')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_submissions_count')
                    ->label('Completed')
                    ->counts([
                        'submissions' => fn (Builder $query) => $query->where('status', SubmissionTypes::COMPLETED->value)
                    ])
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pending_submissions_count')
                    ->label('Pending')
                    ->counts([
                        'submissions' => fn (Builder $query) => $query->whereIn('status', [
                            SubmissionTypes::PENDING_REVIEW->value,
                            SubmissionTypes::UNDER_REVIEW->value
                        ])
                    ])
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total Score')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function (User $record): string {
                        // Get total score from graded submissions
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->select(DB::raw('SUM(CAST(reviews.score AS DECIMAL(10,2))) as total_score'))
                            ->value('total_score') ?? 0;

                        // Get total of ALL tasks' max_score (not just submitted ones)
                        $totalMaxScore = DB::table('tasks')
                            ->where('is_active', 1)
                            ->select(DB::raw('SUM(CAST(max_score AS DECIMAL(10,2))) as total_max'))
                            ->value('total_max') ?? 0;

                        $totalScore = round($studentScore, 1);
                        $maxScore = round($totalMaxScore, 1);

                        return "{$totalScore}/{$maxScore}";
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

                        if ($totalMaxScore == 0) {
                            return 'gray';
                        }

                        $percentage = ($studentScore / $totalMaxScore) * 100;

                        return match (true) {
                            $percentage >= 75 => 'success',
                            $percentage >= 50 => 'warning',
                            $studentScore == 0 => 'gray',
                            default => 'danger',
                        };
                    })
                    ->tooltip(function (User $record): string {
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
                            return 'No tasks available';
                        }

                        $percentage = round(($studentScore / $totalMaxScore) * 100, 1);
                        return "{$percentage}% of total possible score";
                    }),

                Tables\Columns\TextColumn::make('average_score')
                    ->label('Avg Score')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function (User $record): string {
                        // Get student's total score
                        $studentScore = DB::table('submissions')
                            ->join('reviews', 'submissions.id', '=', 'reviews.submission_id')
                            ->where('submissions.student_id', $record->id)
                            ->whereNotNull('reviews.score')
                            ->select(DB::raw('SUM(CAST(reviews.score AS DECIMAL(10,2))) as total_score'))
                            ->value('total_score') ?? 0;

                        // Get total of ALL tasks' max_score
                        $totalMaxScore = DB::table('tasks')
                            ->where('is_active', 1)
                            ->select(DB::raw('SUM(CAST(max_score AS DECIMAL(10,2))) as total_max'))
                            ->value('total_max') ?? 0;

                        if ($totalMaxScore == 0) {
                            return '0/60';
                        }

                        // Calculate percentage
                        $percentage = ($studentScore / $totalMaxScore) * 100;

                        // Convert to score out of 60
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

                        if ($totalMaxScore == 0 || $studentScore == 0) {
                            return 'gray';
                        }

                        $percentage = ($studentScore / $totalMaxScore) * 100;
                        $scoreOutOf60 = ($percentage / 100) * 60;

                        return match (true) {
                            $scoreOutOf60 >= 45 => 'success',  // 75% of 60
                            $scoreOutOf60 >= 30 => 'warning',  // 50% of 60
                            default => 'danger',
                        };
                    })
                    ->tooltip(function (User $record): string {
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
                            return 'No tasks available';
                        }

                        $percentage = round(($studentScore / $totalMaxScore) * 100, 1);
                        return "Total: {$studentScore}/{$totalMaxScore} = {$percentage}% converted to 60-point scale";
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

                Tables\Filters\Filter::make('has_pending')
                    ->label('Has Pending Submissions')
                    ->query(fn (Builder $query) => $query->whereHas('submissions', function ($q) {
                        $q->whereIn('status', [
                            SubmissionTypes::PENDING_REVIEW->value,
                            SubmissionTypes::UNDER_REVIEW->value
                        ]);
                    }))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_submissions')
                    ->label('View Submissions')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn (User $record): string =>
                    static::getUrl('submissions', ['record' => $record->id])
                    ),
            ])
            ->defaultSort('submissions_count', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentSubmissions::route('/'),
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
                    SubmissionTypes::UNDER_REVIEW->value
                ]),
            ]);
    }
}
