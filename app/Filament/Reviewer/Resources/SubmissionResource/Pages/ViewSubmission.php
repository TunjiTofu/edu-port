<?php

namespace App\Filament\Reviewer\Resources\SubmissionResource\Pages;

use App\Filament\Reviewer\Resources\SubmissionResource;
use App\Models\Submission;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString as Html;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Review Submission'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Submission Information')
                    ->schema([
                        TextEntry::make('student.id')
                            ->label('Student ID'),
                        TextEntry::make('student.name')
                            ->label('Student Name'),
                        TextEntry::make('student.email')
                            ->label('Student Email'),
                        TextEntry::make('student.district.name')
                            ->label('Student District'),
                        TextEntry::make('student.church.name')
                            ->label('Student Church'),
                        TextEntry::make('task.title')
                            ->label('Task'),
                        TextEntry::make('task.instructions')
                            ->label('Instruction'),
                        TextEntry::make('task.section.name')
                            ->label('Section'),
                        TextEntry::make('task.section.trainingProgram.name')
                            ->label('Training Program'),
                        TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->dateTime(),
                    ])
                    ->columns(4),

                Section::make('Submission Content')
                    ->schema([
                        TextEntry::make('submission_notes')
                            ->label('Student Notes')
                            ->placeholder('No notes provided')
                            ->columnSpanFull(),

                        TextEntry::make('file_name')
                            ->label('Submitted File')
                            ->formatStateUsing(function ($state, Submission $record) {
                                // File icon based on file type
                                $icon = match(true) {
                                    str_contains($record->file_type, 'pdf') => '📕',
                                    str_contains($record->file_type, 'word') => '📄',
                                    str_contains($record->file_type, 'image') => '🖼️',
                                    default => '📁'
                                };

                                try {
//                                    'final_exists' => Storage::disk(config('filesystems.default'))->exists($newPath),
//                'file_size' => Storage::disk(config('filesystems.default'))->size($newPath),
//                'file_type' => Storage::disk(config('filesystems.default'))->mimeType($newPath)


//                                    $fileSize = Storage::disk('local')->size($record->file_path);
//                                    dd($record->file_path.'/'.$record->file_name);
                                    $fileSize = Storage::disk(config('filesystems.default'))->size($record->file_path.'/'.$record->file_name);
                                    $formattedSize = $fileSize > 1024
                                        ? round($fileSize / 1024) . ' KB'
                                        : $fileSize . ' bytes';

                                    return "{$icon} {$state} ({$formattedSize})";
                                } catch (\Exception $e) {
                                    return "{$icon} {$state} (File unavailable)";
                                }
                            })
                            ->url(function (Submission $record) {
                                try {
                                    if (Storage::disk(config('filesystems.default'))->exists($record->file_path.'/'.$record->file_name)) {
                                        return route('submission.download', $record);
                                    }
                                    Notification::make()
                                        ->title('File not found')
                                        ->danger()
                                        ->send();
                                    return null;
                                } catch (\Exception $e) {
                                    return null;
                                }
                            })
                            ->openUrlInNewTab()
                            ->extraAttributes([
                                'class' => 'hover:text-primary-500 cursor-pointer underline transition',
                                'title' => 'Click to download',
                            ])
                            ->helperText(function (Submission $record) {
                                $uploadTime = $record->submitted_at->diffForHumans();
                                return "Submitted {$uploadTime} • Click to download";
                            })
                            ->columnSpanFull(),

                        TextEntry::make('file_type')
                            ->label('File Type')
                            ->formatStateUsing(fn ($state) => Str::upper($state))
                            ->hidden(fn (Submission $record) => empty($record->file_type)),

                        TextEntry::make('submitted_at')
                            ->label('Submission Time')
                            ->dateTime()
                            ->hidden(fn (Submission $record) => empty($record->submitted_at)),
                    ])
                    ->columns(2),

//                Section::make('Similarity Analysis')
//                    ->schema([
//                        TextEntry::make('similarity_score')
//                            ->label('Similarity Score')
//                            ->formatStateUsing(fn ($state) => $state ? $state . '%' : 'Not checked')
//                            ->color(fn ($state) => match (true) {
//                                $state > 70 => 'danger',
//                                $state > 50 => 'warning',
//                                default => 'success'
//                            }),
//                        TextEntry::make('similarity_details')
//                            ->label('Similar Submissions')
//                            ->placeholder('No similar submissions found'),
//                    ])
//                    ->visible(fn ($record) => $record->similarity_checked),

                Section::make('Review Status')
                    ->schema([
                        TextEntry::make('reviews.0.is_completed')
                            ->label('Status')
                            ->formatStateUsing(fn ($state, $record) =>
                            $record->reviews->isEmpty() ? 'Not reviewed' :
                                ($state ? 'Yes' : 'No')
                            )
                            ->color(fn ($state, $record) =>
                            $record->reviews->isEmpty() ? 'warning' :
                                ($state ? 'success' : 'danger')
                            ),
                        TextEntry::make('reviews.0.score')
                            ->label('Score')
                            ->placeholder('No score assigned'),
                        TextEntry::make('reviews.0.comments')
                            ->label('Comments')
                            ->placeholder('No comments provided'),
                        TextEntry::make('reviews.0.reviewed_at')
                            ->label('Reviewed At')
                            ->dateTime()
                            ->placeholder('Not reviewed yet'),
                    ])
                    ->columns(2),
            ]);
    }
}
