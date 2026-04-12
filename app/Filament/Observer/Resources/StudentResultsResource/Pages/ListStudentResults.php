<?php

namespace App\Filament\Observer\Resources\StudentResultsResource\Pages;

use App\Filament\Observer\Resources\StudentResultsResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentResults extends ListRecords
{
    protected static string $resource = StudentResultsResource::class;

    protected function getHeaderActions(): array
    {
        // Observers have NO header actions
        return [];
    }
}
