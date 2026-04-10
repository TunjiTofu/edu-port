<?php

namespace App\Filament\Student\Resources;

use App\Enums\ProgramEnrollmentStatus;
use App\Filament\Student\Resources\AvailableTrainingProgramResource\Pages;
use App\Models\ProgramEnrollment;
use App\Models\TrainingProgram;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AvailableTrainingProgramResource extends Resource
{
    protected static ?string $model           = TrainingProgram::class;
    protected static ?string $navigationIcon  = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Available Programs';
    protected static ?string $navigationGroup = 'Learning';
    protected static ?int    $navigationSort  = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isStudent();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->active()
            ->notEnrolledBy(Auth::id())
            ->withCount(['sections', 'enrollments']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Card layout — mobile first, single column
                    Tables\Columns\Layout\Split::make([

                        // Program image — small circle on the left
                        Tables\Columns\ImageColumn::make('image_url')
                            ->label('')
                            ->circular()
                            ->size(64)
                            ->defaultImageUrl(asset('images/logo.png'))
                            ->grow(false)
                            ->extraImgAttributes(['class' => 'ring-2 ring-green-500/30 shadow-md']),

                        Tables\Columns\Layout\Stack::make([
                            // Program name
                            Tables\Columns\TextColumn::make('name')
                                ->label('')
                                ->weight('bold')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                                ->searchable()
                                ->color('gray-900'),

                            // Description
                            Tables\Columns\TextColumn::make('description')
                                ->label('')
                                ->color('gray')
                                ->wrap()
                                ->limit(120),

                            // Badges row — sections + enrolled count
                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\TextColumn::make('sections_count')
                                        ->badge()
                                        ->color('info')
                                        ->formatStateUsing(fn ($state) => "📚 {$state} " . str('Section')->plural($state)),

                                    Tables\Columns\TextColumn::make('enrollments_count')
                                        ->badge()
                                        ->color('success')
                                        ->formatStateUsing(fn ($state) => "👥 {$state} Enrolled"),
                                ]),

                            // Registration deadline badge
                            Tables\Columns\TextColumn::make('registration_deadline')
                                ->badge()
                                ->color(function ($state) {
                                    if (! $state) return 'gray';
                                    $days = now()->diffInDays($state, false);
                                    return match (true) {
                                        $days < 0  => 'danger',
                                        $days <= 7 => 'warning',
                                        default    => 'success',
                                    };
                                })

                                ->formatStateUsing(function ($state) {
                                    if (! $state) return '🗓 No deadline';

                                    $carbon = $state instanceof \Carbon\Carbon ? $state : \Carbon\Carbon::parse($state);

                                    $days = now()->diffInDays($carbon, false);
                                    $hours = round(now()->diffInHours($carbon, false),1);

                                    $label = match (true) {
                                        $days < 0  => '⛔ Deadline passed',
                                        $days < 1 && $hours > 0 => "⚠️ {$hours}hours left — " . $carbon->format('M j, Y g:i A'),
                                        $days == 0 && $hours <= 0 => '⚠️ Closes today',
                                        $days <= 7 => "⚠️ {$days} days left — " . $carbon->format('M j, Y'),
                                        default    => '🗓 Deadline: ' . $carbon->format('M j, Y'),
                                    };

                                    return $label;
                                }),

                            // Start / End dates
                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\TextColumn::make('start_date')
                                        ->label('Starts')
                                        ->icon('heroicon-o-play-circle')
                                        // FIX: format Carbon date properly
                                        ->formatStateUsing(fn ($state) =>
                                        $state instanceof \Carbon\Carbon
                                            ? $state->format('M j, Y')
                                            : \Carbon\Carbon::parse($state)->format('M j, Y')
                                        ),

                                    Tables\Columns\TextColumn::make('end_date')
                                        ->label('Ends')
                                        ->icon('heroicon-o-stop-circle')
                                        ->formatStateUsing(fn ($state) =>
                                        $state instanceof \Carbon\Carbon
                                            ? $state->format('M j, Y')
                                            : \Carbon\Carbon::parse($state)->format('M j, Y')
                                        ),
                                ]),
                        ])->space(2)->grow(true),
                    ])->from('sm'),
                ])->space(3),
            ])
            ->contentGrid([
                'default' => 1,
                'sm'      => 1,
                'md'      => 2,   // 2 cards per row on tablet
                'xl'      => 2,   // keep 2 on desktop for readability
            ])
            ->filters([
                Tables\Filters\Filter::make('registration_deadline')
                    ->form([
                        Forms\Components\DatePicker::make('deadline_from')->label('Deadline from'),
                        Forms\Components\DatePicker::make('deadline_to')->label('Deadline to'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['deadline_from'],
                            fn ($q, $date) => $q->whereDate('registration_deadline', '>=', $date))
                        ->when($data['deadline_to'],
                            fn ($q, $date) => $q->whereDate('registration_deadline', '<=', $date))
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('enroll')
                    ->label('Enroll')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Enroll in Program')
                    ->modalDescription(fn ($record) => "Enroll in '{$record->name}'? It will be added to your learning dashboard.")
                    ->modalSubmitActionLabel('Enroll Now')
                    ->action(fn (TrainingProgram $record) => static::handleEnroll($record)),

                Tables\Actions\ViewAction::make()
                    ->label('Details')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Available Programs')
            ->emptyStateDescription('There are no programs open for enrollment right now.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function handleEnroll(TrainingProgram $record): void
    {
        $candidateId = Auth::id();
        $context     = [
            'program_id'      => $record->id,
            'program_name'    => $record->name,
            'candidate_id'    => $candidateId,
            'candidate_email' => Auth::user()?->email,
            'ip'              => request()->ip(),
        ];

        Log::info('Enrollment: attempt', array_merge($context, ['event' => 'enrollment_attempt']));

        try {
            if (! $record->isRegistrationOpen()) {
                Log::info('Enrollment: rejected — registration closed', array_merge($context, ['event' => 'enrollment_rejected_registration_closed']));
                Notification::make()->title('Registration Closed')->body('Registration for this program has closed.')->warning()->send();
                return;
            }

            if (! $record->hasAvailableCapacity()) {
                Log::info('Enrollment: rejected — program full', array_merge($context, ['event' => 'enrollment_rejected_program_full']));
                Notification::make()->title('Program Full')->body('This program has reached its maximum enrollment capacity.')->warning()->send();
                return;
            }

            if (ProgramEnrollment::where('student_id', $candidateId)->where('training_program_id', $record->id)->exists()) {
                Log::warning('Enrollment: duplicate attempt', array_merge($context, ['event' => 'enrollment_rejected_already_enrolled']));
                Notification::make()->title('Already Enrolled')->body('You are already enrolled in this program.')->warning()->send();
                return;
            }

            $enrollment = ProgramEnrollment::create([
                'student_id'          => $candidateId,
                'training_program_id' => $record->id,
                'enrolled_at'         => now(),
                'status'              => ProgramEnrollmentStatus::ACTIVE->value,
            ]);

            Log::info('Enrollment: success', array_merge($context, ['event' => 'enrollment_success', 'enrollment_id' => $enrollment->id]));

            Notification::make()->title('Enrolled!')->body("You're now enrolled in '{$record->name}'.'")->success()->send();

            redirect()->to('/student/training-programs');

        } catch (\Exception $e) {
            Log::error('Enrollment: error', array_merge($context, ['event' => 'enrollment_error', 'error' => $e->getMessage()]));
            Notification::make()->title('Enrollment Failed')->body('An error occurred. Please try again.')->danger()->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $count = TrainingProgram::active()->notEnrolledBy(Auth::id())->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvailableTrainingPrograms::route('/'),
            'view'  => Pages\ViewAvailableTrainingProgram::route('/{record}'),
        ];
    }
}
