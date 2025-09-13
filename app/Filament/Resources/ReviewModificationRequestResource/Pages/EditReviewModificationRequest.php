<?php

namespace App\Filament\Resources\ReviewModificationRequestResource\Pages;

use App\Filament\Resources\ReviewModificationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReviewModificationRequest extends EditRecord
{
    protected static string $resource = ReviewModificationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
