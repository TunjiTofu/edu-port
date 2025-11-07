<?php

namespace App\Filament\Resources\StudentResultsResource\Pages;

use App\Filament\Resources\StudentResultsResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentResults extends ListRecords
{
    protected static string $resource = StudentResultsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
