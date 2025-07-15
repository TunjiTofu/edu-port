<?php

namespace App\Filament\Student\Resources\AvailableTrainingProgramResource\Pages;

use App\Filament\Student\Resources\AvailableTrainingProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAvailableTrainingProgram extends EditRecord
{
    protected static string $resource = AvailableTrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
