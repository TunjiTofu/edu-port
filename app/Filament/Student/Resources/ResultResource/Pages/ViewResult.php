<?php

namespace App\Filament\Student\Resources\ResultResource\Pages;

use App\Filament\Student\Resources\ResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewResult extends ViewRecord
{
    protected static string $resource = ResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
