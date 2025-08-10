<?php

namespace App\Filament\Resources\RubricResource\Pages;

use App\Filament\Resources\RubricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRubrics extends ListRecords
{
    protected static string $resource = RubricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
