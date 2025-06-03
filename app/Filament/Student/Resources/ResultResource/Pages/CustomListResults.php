<?php

namespace App\Filament\Student\Resources\ResultResource\Pages;

use App\Filament\Student\Resources\ResultResource;
use App\Models\Section;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class CustomListResults extends ListRecords
{
    protected static string $resource = ResultResource::class;
    protected static string $view = 'filament.student.result.custom-list-results';

    public function getSections()
    {
        $user = Auth::user();

        return Section::query()
            ->whereHas('trainingProgram.students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with([
                'trainingProgram',
                'tasks' => function ($query) use ($user) {
                    $query->whereHas('resultPublications', function ($q) {
                        $q->where('is_published', true);
                    })
                        ->with([
                            'submissions' => function ($q) use ($user) {
                                $q->where('student_id', $user->id)
                                    ->whereHas('review', function ($reviewQuery) {
                                        $reviewQuery->whereNotNull('score')
                                            ->where('is_completed', true);
                                    })
                                    ->with('review');
                            }
                        ]);
                }
            ])
            ->orderBy('order_index')
            ->get();
    }

    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'sections' => $this->getSections(),
        ]);
    }

    public function getHeading(): string
    {
        return static::$resource::getPluralModelLabel();
    }
}
