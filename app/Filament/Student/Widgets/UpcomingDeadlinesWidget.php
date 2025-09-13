<?php

namespace App\Filament\Student\Widgets;

use App\Enums\SubmissionTypes;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Task;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class UpcomingDeadlinesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Upcoming Deadlines';
    protected static ?string $description = 'Tasks that need your attention';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->whereHas('section.trainingProgram.enrollments', function ($query) {
                        $query->where('student_id', auth()->id());
                    })
                    ->whereDoesntHave('submissions', function ($query) {
                        $query->where('student_id', auth()->id());
                    })
                    ->where(function ($query) {
                        $query->where('due_date', '>=', now())
                            ->orWhereNull('due_date');
                    })
                    ->with(['section.trainingProgram'])
                    ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('due_date', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('section.trainingProgram.name')
                    ->label('Program')
                    ->badge()
                    ->color('info')
                    ->limit(20),

                Tables\Columns\TextColumn::make('title')
                    ->label('Task')
                    ->weight('bold')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'No deadline';

                        $daysLeft = now()->diffInDays($state, false);
                        $wholeDays = (int)round($daysLeft); // Convert to whole number

                        if ($daysLeft == 0) return 'Due today';
                        if ($daysLeft == 1) return 'Due tomorrow';
                        if ($daysLeft > 0) return "Due in {$wholeDays} days";
                        return 'Overdue';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';

                        $daysLeft = now()->diffInDays($state, false);
                        return match(true) {
                            $daysLeft < 0 => 'danger',
                            $daysLeft <= 1 => 'danger',
                            $daysLeft <= 3 => 'warning',
                            $daysLeft <= 7 => 'info',
                            default => 'success'
                        };
                    }),

            ])
            ->actions([
                Tables\Actions\Action::make('submit')
                    ->label('Submit Now')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Wizard::make([
                            Wizard\Step::make('Upload File')
                                ->icon('heroicon-o-document-arrow-up')
                                ->schema([
                                    FileUpload::make('file')
                                        ->label('Upload File')
                                        ->acceptedFileTypes([
                                            'application/pdf',
                                            'application/msword',
                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                        ])
                                        ->maxSize(10240)
                                        ->required()
                                        ->directory('submissions')
                                        ->preserveFilenames()
                                        ->disk('local')
                                        ->storeFileNamesIn('original_file_name')
                                        ->helperText('Accepted formats: PDF, DOC, DOCX. Max size: 10MB.'),

                                    Textarea::make('notes')
                                        ->label('Student Notes (Optional)')
                                        ->rows(3)
                                        ->placeholder('Add any notes or comments...'),
                                ]),

                            Wizard\Step::make('Confirm Submission')
                                ->icon('heroicon-o-check-circle')
                                ->schema([
                                    Placeholder::make('file_info')
                                        ->label('File to submit')
                                        ->content(fn ($get) => $get('original_file_name') ?: 'No file selected'),

                                    Placeholder::make('notes_info')
                                        ->label('Your notes')
                                        ->content(fn ($get) => $get('notes') ?: 'No notes provided'),

                                    Checkbox::make('confirm_submission')
                                        ->label('I confirm that I want to submit this assignment')
                                        ->required()
                                        ->accepted(),
                                ]),
                        ])
                    ])
                    ->action(function ($record, $data) {
                        try {
                            $fileDetails = static::processSubmissionFile($data, $record);

                            Submission::create([
                                'task_id' => $record->id,
                                'student_id' => Auth::id(),
                                'content_text' => null,
                                'file_name' => $fileDetails['file_name'],
                                'file_path' => $fileDetails['file_path'].'/'.$fileDetails['file_name'],
                                'file_size' => $fileDetails['file_size'],
                                'file_type' => $fileDetails['file_type'],
                                'student_notes' => $data['notes'] ?? null,
                                'submitted_at' => now(),
                                'status' => SubmissionTypes::PENDING_REVIEW->value,
                            ]);

                            Notification::make()
                                ->title('Submission Successful')
                                ->body('Your assignment has been submitted successfully.')
                                ->success()
                                ->send();

                            return ['refresh' => true, 'close' => true];
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Submission Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('No Pending Tasks')
            ->emptyStateDescription('Great job! You\'re all caught up with your assignments.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function processSubmissionFile($data, $record, $isResubmit = false, $existingSubmission = null): array
    {
        $sectionId = $record->section->id;
        $taskId = $record->id;
        $userId = Auth::id();
        $userName = str_replace(' ', '_', Auth::user()->name);
        $timestamp = now()->format('Y-m-d_H-i-s');

        // Define file paths
        $tempPath = $data['file'];
        $finalDir = "submissions/{$sectionId}/{$taskId}";
        $originalName = str_replace(' ', '_', $data['original_file_name']);
        $sanitizedName = "{$userName}-{$timestamp}-{$originalName}";
        $newPath = "{$finalDir}/{$sanitizedName}";

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($finalDir);

        // Move file from temp to permanent location
        if (!Storage::disk('public')->exists($tempPath)) {
            throw new \Exception("Uploaded file not found at: {$tempPath}");
        }

        Storage::disk('public')->move($tempPath, $newPath);

        // Delete old file on resubmit
        if ($isResubmit && $existingSubmission && Storage::exists($existingSubmission->file_path)) {
            Storage::delete($existingSubmission->file_path);
        }

        // Return file details
        return [
            'file_name' => $sanitizedName,
            'file_path' => $finalDir,
            'file_size' => Storage::disk('public')->size($newPath),
            'file_type' => Storage::disk('public')->mimeType($newPath),
        ];
    }
}
