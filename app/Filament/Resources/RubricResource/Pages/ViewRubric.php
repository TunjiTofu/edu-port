<?php

namespace App\Filament\Resources\RubricResource\Pages;

use App\Filament\Resources\RubricResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;

class ViewRubric extends ViewRecord
{
    protected static string $resource = RubricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
