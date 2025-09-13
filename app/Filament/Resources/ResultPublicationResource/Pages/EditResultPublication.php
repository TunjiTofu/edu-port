<?php

namespace App\Filament\Resources\ResultPublicationResource\Pages;

use App\Filament\Resources\ResultPublicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResultPublication extends EditRecord
{
    protected static string $resource = ResultPublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
