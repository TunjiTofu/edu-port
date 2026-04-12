<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Actions\DownloadTemplateAction;
use App\Filament\Resources\UserResource\Actions\ImportUsersAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            DownloadTemplateAction::make(),
            ImportUsersAction::make(),
        ];
    }
}
