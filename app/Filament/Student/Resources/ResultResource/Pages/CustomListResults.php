<?php

namespace App\Filament\Student\Resources\ResultResource\Pages;

use App\Filament\Student\Resources\ResultResource;
use App\Models\Section;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CustomListResults extends ListRecords
{
    protected static string $resource = ResultResource::class;
    protected static string $view     = 'filament.student.result.custom-list-results';

    /**
     * Memoized sections collection.
     *
     * FIX: Previously getSections() was called inside getViewData() which runs
     * on every Livewire render cycle (filters, sorting, pagination, any
     * interaction). This fired the full nested query on every interaction.
     * The property is populated once on first access and reused within the
     * same request lifecycle.
     */
    private ?Collection $sectionsCache = null;

    public function getSections(): Collection
    {
        if ($this->sectionsCache !== null) {
            return $this->sectionsCache;
        }

        $user = Auth::user();

        $this->sectionsCache = Section::query()
            ->whereHas('trainingProgram.students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with([
                'trainingProgram',
                'tasks' => function ($query) use ($user) {
                    // Only load tasks with a published result
                    $query->whereHas('resultPublications', fn ($q) =>
                    $q->where('is_published', true)
                    )
                        ->with([
                            'submissions' => function ($q) use ($user) {
                                // Only load this candidate's submission with a completed review
                                $q->where('student_id', $user->id)
                                    ->whereHas('review', fn ($rq) =>
                                    $rq->whereNotNull('score')->where('is_completed', true)
                                    )
                                    ->with(['review:id,submission_id,score,reviewed_at,comments']);
                            },
                        ]);
                },
            ])
            ->orderBy('order_index')
            ->get();

        return $this->sectionsCache;
    }

    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'sections' => $this->getSections(),
        ]);
    }

    public function getHeading(): string
    {
        return 'My Results';
    }
}
