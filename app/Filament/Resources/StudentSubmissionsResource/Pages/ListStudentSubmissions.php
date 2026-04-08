<?php

namespace App\Filament\Resources\StudentSubmissionsResource\Pages;

use App\Filament\Resources\StudentSubmissionsResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentSubmissions extends ListRecords
{
    protected static string $resource = StudentSubmissionsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
