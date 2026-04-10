<?php

namespace App\Filament\Student\Resources\TaskResource\Pages;

use App\Filament\Student\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label('Submit Assignment')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size('lg')
                // Only show if not yet submitted
                ->visible(fn () =>
                $this->record->submissions()
                    ->where('student_id', Auth::id())
                    ->doesntExist()
                )
                ->form(fn () => TaskResource::submissionWizard())
                ->action(fn ($data) => TaskResource::handleSubmission($this->record, $data)),

            Actions\Action::make('submitted_badge')
                ->label('Submitted ✓')
                ->color('success')
                ->disabled()
                ->visible(fn () =>
                $this->record->submissions()
                    ->where('student_id', Auth::id())
                    ->exists()
                ),
        ];
    }

    protected function resolveRecord($key): Task
    {
        return Task::with([
            'section.trainingProgram',
            'activeRubrics',
            'submissions' => fn ($q) => $q
                ->where('student_id', Auth::id())
                ->with(['review.reviewRubrics.rubric']),
        ])->findOrFail($key);
    }

    public function getTitle(): string
    {
        return $this->record->title;
    }
}
