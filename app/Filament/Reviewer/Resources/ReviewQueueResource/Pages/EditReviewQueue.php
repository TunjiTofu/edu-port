<?php

namespace App\Filament\Reviewer\Resources\ReviewQueueResource\Pages;

use App\Filament\Reviewer\Resources\ReviewQueueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReviewQueue extends EditRecord
{
    protected static string $resource = ReviewQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
