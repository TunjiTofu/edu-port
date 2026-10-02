<?php

namespace App\Filament\Resources\ResultPublicationResource\Pages;

use App\Filament\Resources\ResultPublicationResource;
use App\Models\ResultPublication;
use App\Models\Task;
use App\Models\TrainingProgram;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListResultPublications extends ListRecords
{
    protected static string $resource = ResultPublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Publish ALL results at once ────────────────────────────────
            // Optionally filtered by program. Creates records for tasks that
            // don't have one yet, updates existing unpublished records.
            Actions\Action::make('publish_all')
                ->label('Publish All Results')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->modalHeading('Publish All Results')
                ->modalDescription('This will publish results for all tasks matching the selected filter. Students will immediately be able to see their scores.')
                ->modalSubmitActionLabel('Yes, Publish All')
                ->modalWidth('lg')
                ->form([
                    Select::make('training_program_id')
                        ->label('Program (optional)')
                        ->placeholder('All programs')
                        ->options(fn () =>
                        TrainingProgram::orderByDesc('year')->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($p) =>
                            [$p->id => ($p->year ? "[{$p->year}] " : '') . $p->name]
                            )
                        )
                        ->helperText('Leave blank to publish results for ALL active tasks across all programs.'),
                ])
                ->action(function (array $data) {
                    $programId = $data['training_program_id'] ?? null;
                    $adminId   = Auth::id();
                    $now       = now();

                    // Get all active tasks (optionally scoped to program)
                    $tasks = Task::where('is_active', true)
                        ->when($programId, fn ($q) =>
                        $q->whereHas('section', fn ($sq) =>
                        $sq->where('training_program_id', $programId)
                        )
                        )
                        ->pluck('id');

                    $published = 0;
                    $created   = 0;

                    DB::transaction(function () use ($tasks, $adminId, $now, &$published, &$created) {
                        foreach ($tasks as $taskId) {
                            $record = ResultPublication::where('task_id', $taskId)->first();

                            if ($record) {
                                if (! $record->is_published) {
                                    $record->update([
                                        'is_published' => true,
                                        'published_at' => $now,
                                        'published_by' => $adminId,
                                    ]);
                                    $published++;
                                }
                            } else {
                                ResultPublication::create([
                                    'task_id'      => $taskId,
                                    'is_published' => true,
                                    'published_at' => $now,
                                    'published_by' => $adminId,
                                ]);
                                $created++;
                            }
                        }
                    });

                    $total = $published + $created;

                    Notification::make()
                        ->title('Results Published')
                        ->body("{$total} result(s) published ({$created} new, {$published} updated).")
                        ->success()
                        ->send();
                }),

            // ── Unpublish ALL results ──────────────────────────────────────
            Actions\Action::make('unpublish_all')
                ->label('Unpublish All')
                ->icon('heroicon-o-eye-slash')
                ->color('danger')
                ->outlined()
                ->modalHeading('Unpublish All Results')
                ->modalDescription('This will hide results from all students. You can re-publish anytime.')
                ->modalSubmitActionLabel('Yes, Unpublish All')
                ->form([
                    Select::make('training_program_id')
                        ->label('Program (optional)')
                        ->placeholder('All programs')
                        ->options(fn () =>
                        TrainingProgram::orderByDesc('year')->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($p) =>
                            [$p->id => ($p->year ? "[{$p->year}] " : '') . $p->name]
                            )
                        )
                        ->helperText('Leave blank to unpublish ALL results.'),
                ])
                ->action(function (array $data) {
                    $programId = $data['training_program_id'] ?? null;

                    $count = ResultPublication::where('is_published', true)
                        ->when($programId, fn ($q) =>
                        $q->whereHas('task.section', fn ($sq) =>
                        $sq->where('training_program_id', $programId)
                        )
                        )
                        ->update([
                            'is_published' => false,
                            'published_at' => null,
                            'published_by' => null,
                        ]);

                    Notification::make()
                        ->title('Results Unpublished')
                        ->body("{$count} result(s) unpublished. Students can no longer see their scores.")
                        ->warning()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
