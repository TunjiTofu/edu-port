<?php

namespace App\Filament\Student\Resources\AvailableTrainingProgramResource\Pages;

use App\Filament\Student\Resources\AvailableTrainingProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAvailableTrainingPrograms extends ListRecords
{
    protected static string $resource = AvailableTrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed since students can't create programs
        ];
    }
}
