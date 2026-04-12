<?php

namespace App\Filament\Student\Resources\AvailableTrainingProgramResource\Pages;

use App\Filament\Student\Resources\AvailableTrainingProgramResource;
use Filament\Resources\Pages\ListRecords;

class ListAvailableTrainingPrograms extends ListRecords
{
    protected static string $resource = AvailableTrainingProgramResource::class;

    // No header actions — candidates cannot create training programs
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Available Programs';
    }
}
