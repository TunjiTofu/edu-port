<?php

namespace App\Filament\Student\Resources\ResultResource\Pages;

use App\Filament\Student\Resources\ResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListResults extends ListRecords
{
    protected static string $resource = ResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Override the default table view with a custom sections view
//    public function getContent(): View
//    {
//        dd('lll');
//        return view('filament.student.result.student-results', [
//            'sections' => ResultResource::getSectionsWithResults()
//        ]);
//    }
}
