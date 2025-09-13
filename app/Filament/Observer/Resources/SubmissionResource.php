<?php

namespace App\Filament\Observer\Resources;

use App\Enums\SubmissionTypes;
use App\Filament\Observer\Resources\SubmissionResource\Pages;
use App\Filament\Observer\Resources\SubmissionResource\RelationManagers;
use App\Models\Submission;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Review Management';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isObserver();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('Submission Details')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Forms\Components\Select::make('task_id')
                            ->relationship('task', 'title')
                            ->searchable()
                            ->preload(),
                        // ->disabled(),

                        Forms\Components\TextInput::make('file_path')
                            ->label('File Path')
                            ->disabled()
                            ->visible(fn($record) => $record?->file_path),

                        Forms\Components\Textarea::make('text_content')
                            ->label('Text Submission')
                            ->rows(5)
                            ->disabled()
                            ->visible(fn($record) => $record?->text_content),

                        // For view context (read-only)
                        Forms\Components\TextInput::make('reviewer_name')
                            ->label('Assigned Reviewer')
                            ->formatStateUsing(fn($record) => $record->review->reviewer->name ?? 'No reviewer assigned')
                            ->disabled()
                            ->visible(fn($context) => $context === 'view'),

                        // For edit context (select field)
                        Forms\Components\Select::make('review.reviewer_id')
                            ->label('Assign Reviewer')
                            ->relationship(
                                name: 'review.reviewer',  // Relationship name
                                titleAttribute: 'name',   // Display attribute
                                modifyQueryUsing: fn(Builder $query) => $query->where('role_id', Constants::REVIEWER_ID)
                                    ->where('is_active', true)
                                    ->where('church_id', '!=', Auth::user()->church_id)
                                    ->where('district_id', '!=', Auth::user()->district_id)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->required()
                            ->visible(fn($context) => $context === 'edit'),
                    ])->columns(2),

                FormSection::make('Review Information')
                    ->schema([

                        // Status field (works for both)
                        Forms\Components\Select::make('status')
                            ->options([
                                SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                                SubmissionTypes::UNDER_REVIEW->value => 'Under Review',
                                SubmissionTypes::COMPLETED->value => 'Completed',
                                SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                                SubmissionTypes::FLAGGED->value => 'Flagged for Plaigiarism',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('review.score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(function ($record) {
                                return $record->task->max_score ?? 10;
                            })
                            ->visible(function ($get) {
                                return in_array($get('status'), ['completed', 'needs_revision']);
                            })
                            ->required(function ($get) {
                                return $get('status') === 'completed';
                            })
                            ->formatStateUsing(fn($record) => $record->review->score ?? 'N/A'),

                        Forms\Components\Textarea::make('review.comments')
                            ->label('Reviewer Comments')
                            ->rows(4)
                            ->visible(function ($get) {
                                return in_array($get('status'), ['under_review', 'completed', 'needs_revision', 'flagged']);
                            })
                            ->required(function ($get) {
                                return $get('status') === 'needs_revision';
                            })
                            ->formatStateUsing(fn($record) => $record->review->comments ?? 'N/A')
                            ->columnSpanFull(),
                    ])->columns(2),

                FormSection::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submitted At')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Reviewed At')
                            ->formatStateUsing(fn($record) => $record->review?->reviewed_at?->format('Y-m-d H:i:s') ?? 'N/A')
                            ->disabled(),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('task.section.name')
                    ->label('Section')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        SubmissionTypes::PENDING_REVIEW->value => 'primary',
                        SubmissionTypes::UNDER_REVIEW->value => 'info',
                        SubmissionTypes::COMPLETED->value => 'success',
                        SubmissionTypes::NEEDS_REVISION->value => 'secondary',
                        SubmissionTypes::FLAGGED->value => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('review.score')
                    ->label('Score')
                    ->alignCenter()
                    ->badge()
                    ->color(function ($record) {
                        $score = $record->review->score ?? null;
                        $maxScore = $record->task->max_score ?? null;

                        if (null === $score) return 'secondary';
                        if (null === $maxScore) return 'gray';

                        $percentage = ($score / $maxScore) * 100;

                        return match (true) {
                            $percentage >= 75 => 'success',
                            $percentage >= 50 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->formatStateUsing(function ($record) {
                        $score = $record->review->score ?? null;
                        $maxScore = $record->task->max_score ?? null;

                        return match (true) {
                            null === $score => 'N/A',
                            null === $maxScore => (string) $score,
                            default => "{$score}/{$maxScore}",
                        };
                    })
                    ->tooltip(function ($record) {
                        $score = $record->review->score ?? null;
                        $maxScore = $record->task->max_score ?? null;

                        return match (true) {
                            null === $score => 'Not yet graded',
                            null === $maxScore => 'Raw score',
                            default => round(($score / $maxScore) * 100) . '% of maximum score',
                        };
                    }),
                Tables\Columns\TextColumn::make('review.reviewer.name')
                    ->label('Reviewer')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('review.reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([
                SelectFilter::make('status')
                    ->options([
                        SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                        SubmissionTypes::UNDER_REVIEW->value => 'Under Review',
                        SubmissionTypes::COMPLETED->value => 'Completed',
                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                        SubmissionTypes::FLAGGED->value => 'Flagged for Review',
                    ]),

                SelectFilter::make('task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('section')
                    ->label('Section')
                    ->options(function () {
                        return \App\Models\Section::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            $query->whereHas('task.section', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('reviewer_id')
                    ->label('Reviewer')
                    ->relationship(
                        name: 'review.reviewer',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('role_id', 2)
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('submitted_date')
                    ->form([
                        Forms\Components\DatePicker::make('submitted_from')
                            ->label('Submitted From'),
                        Forms\Components\DatePicker::make('submitted_until')
                            ->label('Submitted Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['submitted_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['submitted_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListSubmissions::route('/'),
            'view' => Pages\ViewSubmission::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['student', 'task.section', 'review.reviewer']);
    }
}
