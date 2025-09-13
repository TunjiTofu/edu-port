<?php

namespace App\Filament\Resources\RubricResource\Pages;

use App\Filament\Resources\RubricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRubric extends EditRecord
{
    protected static string $resource = RubricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
