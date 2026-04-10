<?php

namespace App\Filament\Student\Resources\TrainingProgramResource\Pages;

use App\Filament\Student\Resources\TrainingProgramResource;
use Filament\Resources\Pages\ListRecords;

class ListTrainingPrograms extends ListRecords
{
    protected static string $resource = TrainingProgramResource::class;

    // No header actions — candidates cannot create training programs
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'My Programs';
    }
}
