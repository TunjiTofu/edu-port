<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Enums\SubmissionTypes;
use App\Filament\Resources\SubmissionResource\Pages;
use App\Filament\Resources\SubmissionResource\RelationManagers;
use App\Mail\BulkReviewerAssignedMail;
use App\Models\Review;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use App\Services\Utility\Constants;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\TextEntry;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry as ComponentsTextEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Review Management';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
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
                        // Forms\Components\TextInput::make('review.reviewer.name')
                        //     ->label('Assigned Reviewer')
                        //     ->disabled()
                        //     ->formatStateUsing(function ($record) {
                        //         return $record->review->reviewer->name ?? 'No reviewer assigned';
                        //     }),

                        // Forms\Components\Select::make('review.reviewer.name')
                        //     ->label('Assigned Reviewer')
                        //     ->relationship(
                        //         name: 'review.reviewer',
                        //         titleAttribute: 'name'
                        //     )
                        //     // ->formatStateUsing(function ($record) {
                        //     //     return $record->review->reviewer->name ?? 'No reviewer assigned';
                        //     // })
                        //     ->searchable()
                        //     ->preload(),



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

                // FormSection::make('Plagiarism Detection')
                //     ->schema([
                //         Forms\Components\TextInput::make('similarity_score')
                //             ->label('Similarity Score (%)')
                //             ->numeric()
                //             ->disabled()
                //             ->suffix('%'),

                //         Forms\Components\Toggle::make('is_flagged')
                //             ->label('Flagged for Plagiarism')
                //             ->disabled(),

                //         Forms\Components\Textarea::make('similarity_details')
                //             ->label('Similarity Details')
                //             ->rows(3)
                //             ->disabled()
                //             ->helperText('Details about similar submissions found'),
                //     ])->columns(2),

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

                FormSection::make('Admin Override')
                    ->schema([
                        Forms\Components\Toggle::make('review.admin_override')
                            ->label('Admin Override?')
                            ->reactive()
                            ->formatStateUsing(fn($record) => $record->review?->admin_override ?? false)
                            ->helperText('Override the reviewer\'s decision')
                            ->disabled(),

                        Forms\Components\Select::make('review.overridden_by')
                            ->label('Overridden By')
                            ->options(User::where('role_id', Constants::ADMIN_ID)->pluck('name', 'id')) // Only admins
                            ->default(Auth::user()->id)
                            ->searchable()
                            ->required(fn($get) => $get('review.admin_override'))
                            ->formatStateUsing(fn($record) => $record->review?->overridden_by ?? Auth::user()->id)
                            ->visible(fn($get) => $get('review.admin_override'))
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('review.overridden_at')
                            ->label('Override Date')
                            // ->default(now())
                            ->required(fn($get) => $get('review.admin_override'))
                            ->formatStateUsing(fn($record) => $record->review?->overridden_at ? Carbon::parse($record->review->overridden_at)->format('Y-m-d H:i:s') : now()?->format('Y-m-d H:i:s'))
                            ->visible(fn($get) => $get('review.admin_override'))
                            ->disabled(),

                        Forms\Components\Textarea::make('review.override_reason')
                            ->label('Override Reason')
                            ->rows(3)
                            ->required(fn($get) => $get('review.admin_override'))
                            ->formatStateUsing(fn($record) => $record->review?->override_reason ?? '')
                            ->visible(fn($get) => $get('review.admin_override'))
                            ->disabled()
                            ->helperText('Explain why this override is necessary')
                            ->columnSpanFull(),

                    ])
                    ->collapsible()
                    ->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ── KEY PERFORMANCE FIX ──────────────────────────────────────────
            // Without deferLoading, the table renders all rows synchronously
            // before the HTTP response is sent. With 2000+ submissions each
            // requiring URL generation and closure evaluation, this exceeded
            // the 30-second PHP execution limit on production shared hosting.
            // deferLoading renders the page shell instantly, then Livewire
            // fetches table data asynchronously — each well within the limit.
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('student.phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

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
                    ->color(fn (string $state): string => match ($state) {
                        SubmissionTypes::PENDING_REVIEW->value => 'primary',
                        SubmissionTypes::UNDER_REVIEW->value   => 'info',
                        SubmissionTypes::COMPLETED->value      => 'success',
                        SubmissionTypes::NEEDS_REVISION->value => 'secondary',
                        SubmissionTypes::FLAGGED->value        => 'danger',
                        default                                 => 'gray',
                    }),

                // Resubmission indicator — null state renders nothing (no badge)
                Tables\Columns\TextColumn::make('resubmission_badge')
                    ->label('')
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(fn (?Submission $record): ?string =>
                    $record?->is_resubmission ? '↩ Resubmitted' : null
                    ),

                // ── Score column ─────────────────────────────────────────────
                // Previously had 3 separate closures all injecting $record
                // (color + formatStateUsing + tooltip = 6,000+ reflection calls
                // per page with 2000 rows). Now: one getStateUsing computes a
                // synthetic state string; color and formatStateUsing use simple
                // string matching with no record injection.
                Tables\Columns\TextColumn::make('score_display')
                    ->label('Score')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function (?Submission $record): string {
                        $score    = $record?->review?->score;
                        $maxScore = $record?->task?->max_score;
                        if ($score === null)    return 'pending';
                        if ($maxScore === null) return 'raw:' . $score;
                        $pct = round(($score / $maxScore) * 100);
                        return "scored:{$score}/{$maxScore}:{$pct}";
                    })
                    ->color(fn (string $state): string => match (true) {
                        $state === 'pending'                           => 'secondary',
                        str_starts_with($state, 'raw:')               => 'gray',
                        (int) explode(':', $state)[2] >= 75           => 'success',
                        (int) explode(':', $state)[2] >= 50           => 'warning',
                        default                                        => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match (true) {
                        $state === 'pending'          => 'N/A',
                        str_starts_with($state, 'raw:') => substr($state, 4),
                        default                          => explode(':', $state)[1],
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
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $years = \App\Models\Submission::selectRaw('DISTINCT YEAR(submitted_at) as yr')
                            ->orderByDesc('yr')->pluck('yr', 'yr')
                            ->map(fn ($y) => (string) $y)->toArray();
                        $currentYear = now()->year;
                        $years[$currentYear] = (string) $currentYear;
                        krsort($years);
                        return ['' => 'All Years'] + $years;
                    })
                    ->default((string) now()->year)
                    ->query(fn (Builder $query, array $data) =>
                    $data['value'] ? $query->whereYear('submitted_at', (int) $data['value']) : $query
                    ),

                SelectFilter::make('status')
                    ->options([
                        SubmissionTypes::PENDING_REVIEW->value => 'Pending Review',
                        SubmissionTypes::UNDER_REVIEW->value   => 'Under Review',
                        SubmissionTypes::COMPLETED->value      => 'Completed',
                        SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
                        SubmissionTypes::FLAGGED->value        => 'Flagged for Review',
                    ]),

                SelectFilter::make('task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('section')
                    ->label('Section')
                    ->options(fn () => \App\Models\Section::pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('task.section', fn ($q) =>
                            $q->where('id', $data['value'])
                            );
                        }
                    }),

                SelectFilter::make('reviewer_id')
                    ->label('Reviewer')
                    ->relationship('review.reviewer', 'name',
                        fn (Builder $query) => $query->where('role_id', Constants::REVIEWER_ID)
                    )
                    ->preload(),

                Tables\Filters\Filter::make('submitted_date')
                    ->form([
                        Forms\Components\DatePicker::make('submitted_from')->label('Submitted From'),
                        Forms\Components\DatePicker::make('submitted_until')->label('Submitted Until'),
                    ])
                    ->query(fn (Builder $query, array $data) =>
                    $query
                        ->when($data['submitted_from'] ?? null,
                            fn ($q, $date) => $q->whereDate('submitted_at', '>=', $date)
                        )
                        ->when($data['submitted_until'] ?? null,
                            fn ($q, $date) => $q->whereDate('submitted_at', '<=', $date)
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('View'),
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Edit'),

                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->iconButton()
                    ->tooltip('Download File')
                    ->url(fn (?Submission $record) => $record ? static::getDownloadUrl($record) : null)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('assign_reviewer')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->iconButton()
                    ->tooltip(fn (?Submission $record) => $record?->review?->reviewer_id ? 'Reassign Reviewer' : 'Assign Reviewer')
                    ->form([
                        Forms\Components\Select::make('review.reviewer_id')
                            ->label('Select Reviewer')
                            ->helperText('Sorted by lowest pending workload.')
                            ->options(fn () => static::reviewerOptionsWithWorkload())
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (Submission $record, array $data) {
                        $reviewerId = $data['review']['reviewer_id'];
                        $record->review()->updateOrCreate(
                            ['submission_id' => $record->id],
                            ['reviewer_id'   => $reviewerId]
                        );
                        $record->update(['status' => SubmissionTypes::UNDER_REVIEW->value]);
                        Notification::make()->title('Reviewer assigned successfully')->success()->send();
                    })
                    ->visible(fn (?Submission $record) => $record?->status !== SubmissionTypes::COMPLETED->value),

                Tables\Actions\Action::make('override_score')
                    ->icon('heroicon-o-pencil-square')
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Override Score')
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->label('New Score')
                            ->numeric()->required()
                            ->minValue(0)->maxValue(10)
                            ->default(fn (?Submission $record) => $record?->review?->score ?? 10),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('Override Reason')
                            ->required()->rows(3),
                    ])
                    ->action(function (Submission $record, array $data) {
                        $record->review()->updateOrCreate(
                            ['submission_id' => $record->id],
                            [
                                'score'           => $data['score'],
                                'is_completed'    => true,
                                'admin_override'  => true,
                                'override_reason' => $data['override_reason'],
                                'overridden_by'   => Auth::user()->id,
                                'overridden_at'   => now(),
                            ]
                        );
                        $record->update(['status' => SubmissionTypes::COMPLETED->value]);
                    }),

                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Delete'),
                Tables\Actions\ForceDeleteAction::make()->iconButton()->tooltip('Permanently Delete'),
                Tables\Actions\RestoreAction::make()->iconButton()->tooltip('Restore'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    // ── Conflict Report — runs only when modal is opened ──────
                    Tables\Actions\BulkAction::make('conflict_report')
                        ->label('Reviewer Conflict Report')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('warning')
                        ->modalHeading('Reviewer Conflict Analysis')
                        ->modalDescription('Shows how many selected candidates share a church or district with each reviewer.')
                        ->modalSubmitActionLabel('Close')
                        ->form([
                            Forms\Components\Placeholder::make('conflict_table')
                                ->label('')
                                ->content(function ($livewire) {
                                    $selectedIds = collect($livewire->selectedTableRecords ?? []);
                                    if ($selectedIds->isEmpty()) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<p class="text-gray-500 text-sm">No submissions selected.</p>'
                                        );
                                    }
                                    $candidates = \App\Models\Submission::whereIn('id', $selectedIds)
                                        ->with('student')->get()
                                        ->map(fn ($s) => $s->student)->filter();
                                    $total       = $candidates->count();
                                    $churchIds   = $candidates->pluck('church_id')->filter()->countBy()->toArray();
                                    $districtIds = $candidates->pluck('district_id')->filter()->countBy()->toArray();
                                    $reviewerRoleId = \App\Models\Role::where('name', RoleTypes::REVIEWER->value)->value('id');
                                    $reviewers = User::where('role_id', $reviewerRoleId)
                                        ->where('is_active', true)
                                        ->with(['church', 'district'])
                                        ->withCount(['reviews as pending_count' => fn ($q) =>
                                        $q->whereHas('submission', fn ($s) =>
                                        $s->whereIn('status', [
                                            SubmissionTypes::PENDING_REVIEW->value,
                                            SubmissionTypes::UNDER_REVIEW->value,
                                        ])
                                        )
                                        ])
                                        ->get()
                                        ->map(function ($r) use ($churchIds, $districtIds, $total) {
                                            $cp  = $total > 0 ? round((($churchIds[$r->church_id] ?? 0) / $total) * 100) : 0;
                                            $dp  = $total > 0 ? round((($districtIds[$r->district_id] ?? 0) / $total) * 100) : 0;
                                            return [
                                                'name'       => $r->name,
                                                'church'     => $r->church?->name ?? '—',
                                                'district'   => $r->district?->name ?? '—',
                                                'pending'    => $r->pending_count,
                                                'church_n'   => $churchIds[$r->church_id] ?? 0,
                                                'church_pct' => $cp,
                                                'dist_n'     => $districtIds[$r->district_id] ?? 0,
                                                'dist_pct'   => $dp,
                                                'risk'       => match (true) {
                                                    $cp >= 50              => 'high',
                                                    $cp > 0 || $dp >= 30  => 'medium',
                                                    $dp > 0               => 'low',
                                                    default                => 'none',
                                                },
                                            ];
                                        })
                                        ->sortBy('church_pct');
                                    $rows = $reviewers->map(fn ($r) =>
                                        '<tr>
                                            <td style="padding:8px 10px;font-weight:600;font-size:12px;">' . e($r['name']) .
                                        '<div style="font-size:10px;color:#9ca3af;">' . e($r['church']) . ' · ' . e($r['district']) . '</div></td>
                                            <td style="padding:8px 10px;text-align:center;">' . $r['pending'] . '</td>
                                            <td style="padding:8px 10px;font-weight:600;color:' . ($r['church_pct'] > 0 ? '#dc2626' : '#16a34a') . ';">'
                                        . $r['church_n'] . '/' . $total . ' (' . $r['church_pct'] . '%)</td>
                                            <td style="padding:8px 10px;font-weight:600;color:' . ($r['dist_pct'] > 0 ? '#d97706' : '#16a34a') . ';">'
                                        . $r['dist_n'] . '/' . $total . ' (' . $r['dist_pct'] . '%)</td>
                                            <td style="padding:8px 10px;text-align:center;">' .
                                        match ($r['risk']) {
                                            'high'   => '🔴 High',
                                            'medium' => '🟡 Medium',
                                            'low'    => '🟢 Low',
                                            default  => '✅ None',
                                        } . '</td>
                                        </tr>'
                                    )->implode('');
                                    return new \Illuminate\Support\HtmlString('
                                        <div style="margin-bottom:12px;padding:10px 14px;background:#f9fafb;border-radius:6px;border:1px solid #e5e7eb;font-size:12px;">
                                            <strong>' . $total . ' candidate(s)</strong> from <strong>' . count($churchIds) . ' church(es)</strong> across <strong>' . count($districtIds) . ' district(s)</strong>.
                                        </div>
                                        <div style="overflow-x:auto;border:1px solid #e5e7eb;border-radius:8px;">
                                            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                                <thead><tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                                                    <th style="padding:8px 10px;text-align:left;">Reviewer</th>
                                                    <th style="padding:8px 10px;text-align:center;">⏳ Pending</th>
                                                    <th style="padding:8px 10px;text-align:left;color:#dc2626;">⛪ Church</th>
                                                    <th style="padding:8px 10px;text-align:left;color:#d97706;">🏘 District</th>
                                                    <th style="padding:8px 10px;text-align:center;">Risk</th>
                                                </tr></thead>
                                                <tbody>' . $rows . '</tbody>
                                            </table>
                                        </div>');
                                }),
                        ])
                        ->action(fn () => null)
                        ->deselectRecordsAfterCompletion(false),

                    // ── Bulk assign ──────────────────────────────────────────
                    Tables\Actions\BulkAction::make('bulk_assign_reviewer')
                        ->label('Assign Reviewer')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('reviewer_id')
                                ->label('Select Reviewer')
                                ->helperText('Sorted by lowest pending workload. Run "Conflict Report" first to check church/district overlaps.')
                                ->options(fn () => static::reviewerOptionsWithWorkload())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $reviewerId      = $data['reviewer_id'];
                            $reviewer        = User::find($reviewerId);
                            $assignedRecords = collect();

                            foreach ($records as $record) {
                                $existing = $record->review;
                                if ($existing) {
                                    $existing->updateQuietly(['reviewer_id' => $reviewerId]);
                                } else {
                                    Review::withoutEvents(fn () => Review::create([
                                        'submission_id' => $record->id,
                                        'reviewer_id'   => $reviewerId,
                                    ]));
                                }
                                if ($record->status === SubmissionTypes::PENDING_REVIEW->value) {
                                    $record->updateQuietly(['status' => SubmissionTypes::UNDER_REVIEW->value]);
                                }
                                $assignedRecords->push($record);
                            }

                            if ($reviewer && $assignedRecords->isNotEmpty()) {
                                $submissionsData = Submission::whereIn('id', $assignedRecords->pluck('id'))
                                    ->with(['student', 'task.section.trainingProgram'])
                                    ->get()
                                    ->map(fn ($s) => [
                                        'candidate' => $s->student?->name ?? '—',
                                        'task'      => $s->task?->title ?? '—',
                                        'section'   => $s->task?->section?->name ?? '—',
                                        'program'   => $s->task?->section?->trainingProgram?->name ?? '—',
                                        'submitted' => $s->submitted_at?->format('M j, Y') ?? '—',
                                    ])->values()->all();

                                $reviewerEmail = $reviewer->email;
                                $reviewerId    = $reviewer->id;

                                dispatch(function () use ($reviewer, $submissionsData, $reviewerEmail, $reviewerId) {
                                    try {
                                        Mail::to($reviewerEmail)->send(new BulkReviewerAssignedMail($reviewer, $submissionsData));
                                        Log::info('BulkAssign: summary email sent', [
                                            'event' => 'bulk_assign_email_sent', 'reviewer_id' => $reviewerId, 'count' => count($submissionsData),
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error('BulkAssign: email failed', ['event' => 'bulk_assign_email_failed', 'error' => $e->getMessage()]);
                                    }
                                })->afterResponse();
                            }

                            Notification::make()
                                ->title('Reviewer Assigned')
                                ->body("{$assignedRecords->count()} submission(s) assigned to {$reviewer?->name}.")
                                ->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
/*
* Reviewer dropdown options with current pending workload shown.
* e.g. "David Adewale (3 pending)" — helps admins distribute evenly.
* Sorted lightest-load first.
*/
    private static function reviewerOptionsWithWorkload(): array
    {
        $reviewerRoleId = \App\Models\Role::where('name', RoleTypes::REVIEWER->value)->value('id');

        return User::where('role_id', $reviewerRoleId)
            ->where('is_active', true)
            ->withCount(['reviews as pending_count' => fn ($q) =>
            $q->whereHas('submission', fn ($s) =>
            $s->whereIn('status', [
                SubmissionTypes::PENDING_REVIEW->value,
                SubmissionTypes::UNDER_REVIEW->value,
            ])
            )
            ])
            ->orderBy('pending_count') // lightest load first
            ->get()
            ->mapWithKeys(fn ($u) =>
            [$u->id => $u->name . ' (' . $u->pending_count . ' pending)']
            )
            ->toArray();
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
            'create' => Pages\CreateSubmission::route('/create'),
            'view' => Pages\ViewSubmission::route('/{record}'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
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

    protected static function getDownloadUrl(Submission $submission): ?string
    {
        // Use a server-side route instead of pre-generating temporary storage
        // URLs (which called Storage::exists() + Storage::temporaryUrl() for
        // EVERY row on page load). With 2000+ submissions that caused a 30-second
        // timeout on production. The route checks file existence only when clicked.
        return route('admin.submissions.file', $submission);
    }
}
