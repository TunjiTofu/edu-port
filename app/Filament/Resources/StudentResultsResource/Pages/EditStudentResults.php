<?php

namespace App\Filament\Resources\StudentResultsResource\Pages;

use App\Filament\Resources\StudentResultsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentResults extends EditRecord
{
    protected static string $resource = StudentResultsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
