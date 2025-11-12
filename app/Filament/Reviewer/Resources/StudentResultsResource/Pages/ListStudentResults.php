<?php

namespace App\Filament\Reviewer\Resources\StudentResultsResource\Pages;

use App\Filament\Reviewer\Resources\StudentResultsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentResults extends ListRecords
{
    protected static string $resource = StudentResultsResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
