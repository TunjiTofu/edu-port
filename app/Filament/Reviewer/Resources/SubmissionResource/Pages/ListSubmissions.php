<?php

namespace App\Filament\Reviewer\Resources\SubmissionResource\Pages;

use App\Filament\Reviewer\Resources\SubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
//use App\Filament\Reviewer\Resources\SubmissionResource;
//use Filament\Actions;
//use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

//    protected static string $resource = SubmissionResource::class;
//
//    public function getTabs(): array
//    {
//        return [
//            'all' => Tab::make('All Submissions'),
//            'pending' => Tab::make('Pending Review')
//                ->modifyQueryUsing(fn (Builder $query) =>
//                $query->whereDoesntHave('reviews', function ($q) {
//                    $q->where('reviewer_id', auth()->id());
//                })
//                ),
//            'reviewed' => Tab::make('Reviewed')
//                ->modifyQueryUsing(fn (Builder $query) =>
//                $query->whereHas('reviews', function ($q) {
//                    $q->where('reviewer_id', auth()->id());
//                })
//                ),
//            'completed' => Tab::make('Completed')
//                ->modifyQueryUsing(fn (Builder $query) =>
//                $query->whereHas('reviews', function ($q) {
//                    $q->where('reviewer_id', auth()->id())
//                        ->where('is_completed', true);
//                })
//                ),
//            'needs_revision' => Tab::make('Needs Revision')
//                ->modifyQueryUsing(fn (Builder $query) =>
//                $query->whereHas('reviews', function ($q) {
//                    $q->where('reviewer_id', auth()->id())
//                        ->where('is_completed', false);
//                })
//                ),
//        ];
//    }
}
